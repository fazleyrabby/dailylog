<?php

namespace App\Http\Controllers;

use App\Enums\EntryType;
use App\Jobs\EnrichBookmarkJob;
use App\Models\Entry;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BookmarksController extends Controller
{
    public function index(): View
    {
        $entries = Entry::query()
            ->bookmarks()
            ->active()
            ->with(['bookmarkDetails', 'tags'])
            ->orderByDesc('created_at')
            ->get();

        $formatted = $entries->map(fn (Entry $entry) => $this->formatBookmark($entry))->toArray();

        return view('pages.bookmarks', [
            'bookmarks' => $formatted,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],
        ]);

        $host = parse_url($validated['url'], PHP_URL_HOST);
        $site = $host ? preg_replace('/^www\./i', '', $host) : null;

        DB::beginTransaction();

        try {
            $entry = Entry::create([
                'type' => EntryType::Bookmark,
                'title' => $validated['url'],
                'status' => 'active',
                'last_activity_at' => now(),
            ]);

            $entry->bookmarkDetails()->create([
                'url' => $validated['url'],
                'site' => $site,
                'review_state' => 'unread',
            ]);

            if (!empty($validated['tags'])) {
                $tagIds = [];
                foreach ($validated['tags'] as $tagName) {
                    $tag = Tag::firstOrCreate([
                        'user_id' => auth()->id(),
                        'name' => $tagName
                    ], [
                        'slug' => Str::slug($tagName),
                    ]);
                    $tagIds[] = $tag->id;
                }
                $entry->tags()->sync($tagIds);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to store bookmark: " . $e->getMessage(), [
                'url' => $validated['url'],
                'exception' => $e
            ]);
            return response()->json(['message' => 'Failed to create bookmark.'], 500);
        }

        EnrichBookmarkJob::dispatch($entry);

        return response()->json([
            'bookmark' => $this->formatBookmark($entry->load(['bookmarkDetails', 'tags'])),
        ], 201);
    }

    public function markReviewed(Entry $entry): JsonResponse
    {
        $entry->bookmarkDetails()->update([
            'review_state' => 'reviewed',
        ]);

        return response()->json([
            'success' => true,
            'bookmark' => $this->formatBookmark($entry->load(['bookmarkDetails', 'tags'])),
        ]);
    }

    public function destroy(Entry $entry): JsonResponse
    {
        $entry->update(['archived_at' => now()]);

        return response()->json([
            'success' => true,
        ]);
    }

    private function formatBookmark(Entry $entry): array
    {
        $details = $entry->bookmarkDetails;
        return [
            'id' => $entry->id,
            'title' => $entry->title ?? 'Untitled Bookmark',
            'url' => $details?->url ?? '',
            'site' => $details?->site ?? '',
            'desc' => $details?->description ?? '',
            'tags' => $entry->tags->pluck('name')->toArray(),
            'added' => $entry->created_at ? $entry->created_at->diffForHumans() : 'Just now',
            'state' => $details?->review_state ?? 'unread',
        ];
    }
}
