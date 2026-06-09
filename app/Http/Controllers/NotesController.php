<?php

namespace App\Http\Controllers;

use App\Domains\Entries\Actions\UpdateEntry;
use App\Enums\BodyFormat;
use App\Enums\EntryType;
use App\Models\Entry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotesController extends Controller
{
    public function __construct(private readonly UpdateEntry $updateEntry)
    {
    }

    public function index(): View
    {
        $entries = Entry::query()
            ->notes()
            ->active()
            ->with(['tags', 'project', 'backlinks'])
            ->orderByDesc('updated_at')
            ->get();

        $formatted = $entries->map(fn (Entry $entry) => $this->formatNote($entry))->toArray();

        return view('pages.notes', [
            'notes' => $formatted,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $entry = Entry::create([
            'type' => EntryType::Note,
            'title' => 'Untitled Note',
            'body' => '# Untitled Note\n\nStart writing notes in markdown here...',
            'body_format' => BodyFormat::Markdown,
            'status' => 'active',
            'last_activity_at' => now(),
        ]);

        return response()->json([
            'note' => $this->formatNote($entry->load(['tags', 'project', 'backlinks'])),
        ], 201);
    }

    public function update(Request $request, Entry $entry): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string'],
            'body' => ['required', 'string'],
        ]);

        $entry = $this->updateEntry->execute($entry, $validated);

        return response()->json([
            'note' => $this->formatNote($entry->load(['tags', 'project', 'backlinks'])),
        ]);
    }

    public function destroy(Entry $entry): JsonResponse
    {
        $entry->update(['archived_at' => now()]);

        return response()->json([
            'success' => true,
        ]);
    }

    private function formatNote(Entry $entry): array
    {
        return [
            'id' => $entry->id,
            'title' => $entry->title ?? 'Untitled Note',
            'body' => $entry->body ?? '',
            'tags' => $entry->tags->pluck('name')->toArray(),
            'project' => $entry->project?->name ?? 'None',
            'updated' => $entry->updated_at ? $entry->updated_at->diffForHumans() : 'Just now',
            'backlinks' => $entry->backlinks->pluck('title')->toArray(),
        ];
    }
}
