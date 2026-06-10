<?php

namespace App\Http\Controllers;

use App\Domains\Entries\Actions\UpdateEntry;
use App\Enums\EntryType;
use App\Models\Entry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JournalController extends Controller
{
    public function __construct(private readonly UpdateEntry $updateEntry)
    {
    }

    public function index(): View
    {
        $entries = Entry::query()
            ->journals()
            ->active()
            ->orderByDesc('occurred_on')
            ->get();

        $formatted = $entries->mapWithKeys(function (Entry $entry) {
            $formattedEntry = $this->formatJournal($entry);
            return [$formattedEntry['occurred_on'] => $formattedEntry];
        })->toArray();

        return view('pages.journal', [
            'journalEntries' => $formatted,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'occurred_on' => ['required', 'date_format:Y-m-d'],
        ]);

        $entry = Entry::create([
            'type' => EntryType::Journal,
            'occurred_on' => $validated['occurred_on'],
            'title' => 'Journal Reflection ' . $validated['occurred_on'],
            'body' => json_encode([
                'learned' => '',
                'worked' => '',
                'wins' => '',
                'ideas' => '',
                'mood' => '',
            ]),
            'status' => 'active',
            'last_activity_at' => now(),
        ]);

        return response()->json([
            'entry' => $this->formatJournal($entry),
        ], 201);
    }

    public function update(Request $request, Entry $entry): JsonResponse
    {
        $validated = $request->validate([
            'learned' => ['nullable', 'string'],
            'worked' => ['nullable', 'string'],
            'wins' => ['nullable', 'string'],
            'ideas' => ['nullable', 'string'],
            'mood' => ['nullable', 'string'],
        ]);

        $bodyJson = json_encode([
            'learned' => $validated['learned'] ?? '',
            'worked' => $validated['worked'] ?? '',
            'wins' => $validated['wins'] ?? '',
            'ideas' => $validated['ideas'] ?? '',
            'mood' => $validated['mood'] ?? '',
        ]);

        $entry = $this->updateEntry->execute($entry, [
            'body' => $bodyJson,
        ]);

        return response()->json([
            'entry' => $this->formatJournal($entry),
        ]);
    }

    private function formatJournal(Entry $entry): array
    {
        $body = json_decode($entry->body ?? '', true) ?: [];
        return [
            'id' => $entry->id,
            'date' => $entry->occurred_on ? $entry->occurred_on->format('F j, Y') : '',
            'occurred_on' => $entry->occurred_on ? $entry->occurred_on->format('Y-m-d') : '',
            'learned' => $body['learned'] ?? '',
            'worked' => $body['worked'] ?? '',
            'wins' => $body['wins'] ?? '',
            'ideas' => $body['ideas'] ?? '',
            'mood' => $body['mood'] ?? '',
        ];
    }
}
