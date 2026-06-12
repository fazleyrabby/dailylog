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

class QuotesController extends Controller
{
    public function index(): View
    {
        $entries = Entry::query()
            ->quotes()
            ->active()
            ->with(['quoteDetails', 'tags'])
            ->orderByDesc('created_at')
            ->get();

        $formatted = $entries->map(fn (Entry $entry) => $this->formatQuote($entry))->toArray();

        return view('pages.quotes', [
            'quotes' => $formatted,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string'],
            'author' => ['nullable', 'string'],
            'source' => ['nullable', 'string'],
            'location' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],
        ]);

        DB::beginTransaction();

        try {
            $entry = Entry::create([
                'type' => EntryType::Quote,
                'body' => $validated['body'],
                'status' => 'active',
                'last_activity_at' => now(),
            ]);

            $entry->quoteDetails()->create([
                'author' => $validated['author'] ?: 'Unknown',
                'source' => $validated['source'] ?: null,
                'location' => $validated['location'] ?: null,
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
            return response()->json(['message' => 'Failed to create quote.'], 500);
        }

        return response()->json([
            'quote' => $this->formatQuote($entry->load(['quoteDetails', 'tags'])),
        ], 201);
    }

    public function update(Request $request, Entry $entry): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string'],
            'author' => ['nullable', 'string'],
            'source' => ['nullable', 'string'],
            'location' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],
        ]);

        DB::beginTransaction();

        try {
            $entry->update([
                'body' => $validated['body'],
                'last_activity_at' => now(),
            ]);

            $entry->quoteDetails()->updateOrCreate(
                ['entry_id' => $entry->id],
                [
                    'author' => $validated['author'] ?: 'Unknown',
                    'source' => $validated['source'] ?: null,
                    'location' => $validated['location'] ?: null,
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
            return response()->json(['message' => 'Failed to update quote.'], 500);
        }

        return response()->json([
            'quote' => $this->formatQuote($entry->load(['quoteDetails', 'tags'])),
        ]);
    }

    public function destroy(Entry $entry): JsonResponse
    {
        $entry->update(['archived_at' => now()]);

        return response()->json([
            'success' => true,
        ]);
    }

    private function formatQuote(Entry $entry): array
    {
        $details = $entry->quoteDetails;
        return [
            'id' => $entry->id,
            'body' => $entry->body ?? '',
            'author' => $details?->author ?? 'Unknown',
            'source' => $details?->source ?? '',
            'location' => $details?->location ?? '',
            'tags' => $entry->tags->pluck('name')->toArray(),
            'added' => $entry->created_at ? $entry->created_at->diffForHumans() : 'Just now',
        ];
    }
}
