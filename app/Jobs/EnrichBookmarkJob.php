<?php

namespace App\Jobs;

use App\Models\Entry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnrichBookmarkJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Entry $entry)
    {
    }

    public function handle(): void
    {
        $details = $this->entry->bookmarkDetails;
        if (!$details) {
            return;
        }

        $url = $details->url;
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'DailyLOG-Bot/1.0 (+https://github.com/fazleyrabby/dailylog)',
            ])->timeout(10)->get($url);

            if (!$response->successful()) {
                Log::warning("EnrichBookmarkJob failed to fetch URL: {$url}. Status: " . $response->status());
                return;
            }

            $html = $response->body();
            if (empty($html)) {
                return;
            }

            // Parse HTML metadata
            $doc = new \DOMDocument();
            // Suppress warnings due to malformed HTML
            @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            $xpath = new \DOMXPath($doc);

            // Extract Title
            $title = null;
            $ogTitleNode = $xpath->query('//meta[@property="og:title"]/@content');
            if ($ogTitleNode->length > 0) {
                $title = $ogTitleNode->item(0)->nodeValue;
            }
            if (empty($title)) {
                $titleNode = $xpath->query('//title');
                if ($titleNode->length > 0) {
                    $title = $titleNode->item(0)->nodeValue;
                }
            }
            $title = trim($title ?? '');

            // Extract Description
            $description = null;
            $ogDescNode = $xpath->query('//meta[@property="og:description"]/@content');
            if ($ogDescNode->length > 0) {
                $description = $ogDescNode->item(0)->nodeValue;
            }
            if (empty($description)) {
                $metaDescNode = $xpath->query('//meta[@name="description"]/@content');
                if ($metaDescNode->length > 0) {
                    $description = $metaDescNode->item(0)->nodeValue;
                }
            }
            $description = trim($description ?? '');

            // Extract Image URL
            $imageUrl = null;
            $ogImageNode = $xpath->query('//meta[@property="og:image"]/@content');
            if ($ogImageNode->length > 0) {
                $imageUrl = $ogImageNode->item(0)->nodeValue;
            }

            // Extract Favicon URL
            $faviconUrl = null;
            $iconNode = $xpath->query('//link[@rel="icon" or @rel="shortcut icon"]/@href');
            if ($iconNode->length > 0) {
                $faviconUrl = $iconNode->item(0)->nodeValue;
                // Resolve relative paths
                if (!empty($faviconUrl) && !str_starts_with($faviconUrl, 'http')) {
                    $parsedUrl = parse_url($url);
                    $base = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                    if (str_starts_with($faviconUrl, '/')) {
                        $faviconUrl = $base . $faviconUrl;
                    } else {
                        $faviconUrl = $base . '/' . $faviconUrl;
                    }
                }
            }

            // Resolve project/site name
            $host = parse_url($url, PHP_URL_HOST);
            $site = $host ? preg_replace('/^www\./i', '', $host) : null;

            // Update details
            $details->update([
                'site' => $site,
                'description' => empty($description) ? $details->description : $description,
                'favicon_url' => $faviconUrl,
                'image_url' => $imageUrl,
                'fetched_at' => now(),
                'raw_meta' => [
                    'og_title' => $title,
                    'og_description' => $description,
                    'og_image' => $imageUrl,
                ],
            ]);

            // Update core entry title if it is currently a placeholder or URL-like
            if (!empty($title) && ($this->entry->title === 'Untitled Bookmark' || str_starts_with($this->entry->title, 'http'))) {
                $this->entry->update(['title' => $title]);
            }
        } catch (\Exception $e) {
            Log::error("EnrichBookmarkJob failed: " . $e->getMessage());
        }
    }
}
