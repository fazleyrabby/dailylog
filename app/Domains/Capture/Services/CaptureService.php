<?php

namespace App\Domains\Capture\Services;

use App\Domains\Capture\Parsers\CaptureGrammarParser;
use App\Enums\CapturedVia;
use App\Enums\BodyFormat;
use App\Enums\EntryType;
use App\Models\User;
use App\Models\Project;
use App\Models\Entry;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CaptureService
{
    public function __construct(private readonly CaptureGrammarParser $parser)
    {
    }

    public function capture(string $rawInput, CapturedVia $via = CapturedVia::Palette, ?User $user = null): Entry
    {
        $user ??= Auth::user() ?? User::first();
        
        $parsed = $this->parser->parse($rawInput);

        return DB::transaction(function () use ($user, $parsed, $via) {
            // 1. Resolve Project if slug is provided
            $projectId = null;
            if ($parsed->projectSlug) {
                $project = Project::firstOrCreate(
                    ['user_id' => $user->id, 'slug' => $parsed->projectSlug],
                    [
                        'name' => ucfirst($parsed->projectSlug),
                        'status' => 'active',
                        'last_activity_at' => now()
                    ]
                );
                $projectId = $project->id;
                
                // Bump project activity
                $project->update(['last_activity_at' => now()]);
            }

            // 2. Create Core Entry
            $entry = Entry::create([
                'user_id' => $user->id,
                'type' => $parsed->type,
                'title' => $parsed->title,
                'body' => $parsed->body,
                'body_format' => BodyFormat::Markdown,
                'status' => $parsed->type->defaultStatus(),
                'project_id' => $projectId,
                'pinned' => false,
                'occurred_on' => $parsed->type === EntryType::Journal ? now() : null,
                'last_activity_at' => now(),
                'captured_via' => $via,
            ]);

            // 3. Create Type-specific details if needed
            $this->createDetails($entry, $parsed);

            // 4. Resolve and Sync Tags
            if (!empty($parsed->tags)) {
                $tagIds = [];
                foreach ($parsed->tags as $tagName) {
                    $tag = Tag::firstOrCreate([
                        'user_id' => $user->id,
                        'name' => $tagName
                    ], [
                        'slug' => Str::slug($tagName),
                        'color' => null
                    ]);
                    $tagIds[] = $tag->id;
                }
                $entry->tags()->sync($tagIds);
            }

            return $entry;
        });
    }

    protected function createDetails(Entry $entry, $parsed): void
    {
        switch ($parsed->type) {
            case EntryType::Task:
                $entry->taskDetails()->create([
                    'due_at' => $parsed->dueAt,
                    'completed_at' => null,
                    'priority' => $parsed->priority ?? 1,
                    'recurrence' => null,
                ]);
                break;

            case EntryType::Bookmark:
                $url = $parsed->url ?? 'https://';
                $host = parse_url($url, PHP_URL_HOST);
                $site = $host ? preg_replace('/^www\./i', '', $host) : null;

                $entry->bookmarkDetails()->create([
                    'url' => $url,
                    'site' => $site,
                    'description' => null,
                    'favicon_url' => null,
                    'image_url' => null,
                    'fetched_at' => null,
                    'review_state' => 'unread',
                    'raw_meta' => null,
                ]);
                break;

            case EntryType::Resource:
                $entry->resourceDetails()->create([
                    'resource_type' => 'article',
                    'author' => null,
                    'url' => $parsed->url ?? null,
                    'consume_state' => 'to_consume',
                    'rating' => null,
                    'external_ref' => null,
                ]);
                break;

            case EntryType::Learning:
                $entry->learningDetails()->create([
                    'kind' => 'topic',
                    'provider' => null,
                    'progress' => 0,
                    'total_units' => 10,
                    'completed_units' => 0,
                    'status' => 'active',
                    'target_date' => null,
                ]);
                break;

            case EntryType::Quote:
                $entry->quoteDetails()->create([
                    'author' => 'Unknown',
                    'source' => null,
                    'location' => null,
                ]);
                break;
        }
    }
}
