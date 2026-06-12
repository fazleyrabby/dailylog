<?php

namespace App\Http\Controllers;

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ResourcesController extends Controller
{
    public function index(): View
    {
        $entries = Entry::query()
            ->resources()
            ->active()
            ->with(['resourceDetails', 'tags'])
            ->orderByDesc('created_at')
            ->get();

        $formatted = $entries->map(fn (Entry $entry) => $this->formatResource($entry))->toArray();

        return view('pages.resources', [
            'resources' => $formatted,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string'],
            'resource_type' => ['required', 'string', 'in:book,video,article,tool,repo,doc'],
            'consume_state' => ['required', 'string', 'in:to_consume,consuming,done'],
            'author' => ['nullable', 'string'],
            'url' => ['nullable', 'string'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'external_ref' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],
        ]);

        DB::beginTransaction();

        try {
            $entry = Entry::create([
                'type' => EntryType::Resource,
                'title' => $validated['title'],
                'status' => $validated['consume_state'],
                'last_activity_at' => now(),
            ]);

            $entry->resourceDetails()->create([
                'resource_type' => $validated['resource_type'],
                'author' => $validated['author'] ?? null,
                'url' => $validated['url'] ?? null,
                'consume_state' => $validated['consume_state'],
                'rating' => $validated['rating'] ?? null,
                'external_ref' => $validated['external_ref'] ?? null,
            ]);

            $tagIds = [];
            if (!empty($validated['tags'])) {
                foreach ($validated['tags'] as $tagName) {
                    $tag = Tag::firstOrCreate([
                        'user_id' => auth()->id(),
                        'name' => $tagName
                    ], [
                        'slug' => Str::slug($tagName),
                    ]);
                    $tagIds[] = $tag->id;
                }
            }
            $entry->tags()->sync($tagIds);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create resource.'], 500);
        }

        return response()->json([
            'resource' => $this->formatResource($entry->load(['resourceDetails', 'tags'])),
        ], 201);
    }

    public function update(Request $request, Entry $entry): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string'],
            'resource_type' => ['required', 'string', 'in:book,video,article,tool,repo,doc'],
            'consume_state' => ['required', 'string', 'in:to_consume,consuming,done'],
            'author' => ['nullable', 'string'],
            'url' => ['nullable', 'string'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'external_ref' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],
        ]);

        DB::beginTransaction();

        try {
            $entry->update([
                'title' => $validated['title'],
                'status' => $validated['consume_state'],
                'last_activity_at' => now(),
            ]);

            $entry->resourceDetails()->updateOrCreate(
                ['entry_id' => $entry->id],
                [
                    'resource_type' => $validated['resource_type'],
                    'author' => $validated['author'] ?? null,
                    'url' => $validated['url'] ?? null,
                    'consume_state' => $validated['consume_state'],
                    'rating' => $validated['rating'] ?? null,
                    'external_ref' => $validated['external_ref'] ?? null,
                ]
            );

            $tagIds = [];
            if (!empty($validated['tags'])) {
                foreach ($validated['tags'] as $tagName) {
                    $tag = Tag::firstOrCreate([
                        'user_id' => auth()->id(),
                        'name' => $tagName
                    ], [
                        'slug' => Str::slug($tagName),
                    ]);
                    $tagIds[] = $tag->id;
                }
            }
            $entry->tags()->sync($tagIds);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update resource.'], 500);
        }

        return response()->json([
            'resource' => $this->formatResource($entry->load(['resourceDetails', 'tags'])),
        ]);
    }

    public function destroy(Entry $entry): JsonResponse
    {
        $entry->update(['archived_at' => now()]);

        return response()->json([
            'success' => true,
        ]);
    }

    private function formatResource(Entry $entry): array
    {
        $details = $entry->resourceDetails;
        return [
            'id' => $entry->id,
            'title' => $entry->title ?? 'Untitled Resource',
            'type' => $details?->resource_type ?? 'article',
            'author' => $details?->author ?? '',
            'url' => $details?->url ?? '',
            'state' => $details?->consume_state ?? 'to_consume',
            'rating' => $details?->rating ?? 0,
            'external_ref' => $details?->external_ref ?? '',
            'tags' => $entry->tags->pluck('name')->toArray(),
            'added' => $entry->created_at ? $entry->created_at->diffForHumans() : 'Just now',
        ];
    }
}
