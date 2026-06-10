<?php

namespace App\Http\Controllers;

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\LabItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LabController extends Controller
{
    public function index()
    {
        $boards = Entry::query()
            ->labs()
            ->active()
            ->orderByDesc('updated_at')
            ->get();

        if ($boards->isEmpty()) {
            $defaultBoard = Entry::create([
                'type' => EntryType::Lab,
                'title' => 'My First Canvas',
                'status' => 'active',
                'last_activity_at' => now(),
            ]);
            return redirect()->route('lab.show', $defaultBoard);
        }

        return redirect()->route('lab.show', $boards->first());
    }

    public function show(Entry $entry)
    {
        if ($entry->type !== EntryType::Lab) {
            abort(404);
        }

        $boards = Entry::query()
            ->labs()
            ->active()
            ->orderByDesc('updated_at')
            ->get();

        $items = $entry->labItems()->with('target')->get();

        $recentEntries = Entry::query()
            ->whereIn('type', [
                EntryType::Note,
                EntryType::Task,
                EntryType::Idea,
                EntryType::Learning,
                EntryType::Bookmark,
                EntryType::Resource,
            ])
            ->active()
            ->orderByDesc('updated_at')
            ->limit(30)
            ->get();

        return view('pages.lab', [
            'boards' => $boards,
            'activeBoard' => $entry,
            'items' => $items,
            'recentEntries' => $recentEntries,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $board = Entry::create([
            'type' => EntryType::Lab,
            'title' => $validated['title'],
            'status' => 'active',
            'last_activity_at' => now(),
        ]);

        return redirect()->route('lab.show', $board);
    }

    public function update(Request $request, Entry $entry): RedirectResponse
    {
        if ($entry->type !== EntryType::Lab) {
            abort(404);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $entry->update([
            'title' => $validated['title'],
            'last_activity_at' => now(),
        ]);

        return redirect()->route('lab.show', $entry);
    }

    public function destroy(Entry $entry): RedirectResponse
    {
        if ($entry->type !== EntryType::Lab) {
            abort(404);
        }

        $entry->delete();

        return redirect()->route('lab.index');
    }

    public function updateItems(Request $request, Entry $entry): JsonResponse
    {
        if ($entry->type !== EntryType::Lab) {
            return response()->json(['error' => 'Invalid board type'], 400);
        }

        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.type' => ['required', 'string', 'in:sticky,text,reference'],
            'items.*.title' => ['nullable', 'string'],
            'items.*.content' => ['nullable', 'string'],
            'items.*.x' => ['required', 'numeric'],
            'items.*.y' => ['required', 'numeric'],
            'items.*.width' => ['nullable', 'numeric'],
            'items.*.height' => ['nullable', 'numeric'],
            'items.*.color' => ['nullable', 'string'],
            'items.*.target_entry_id' => ['nullable', 'integer', 'exists:entries,id'],
        ]);

        $receivedIds = [];

        foreach ($validated['items'] as $itemData) {
            // Round coordinates to integers for DB storage
            $itemData['x'] = (int) round($itemData['x']);
            $itemData['y'] = (int) round($itemData['y']);
            if (isset($itemData['width'])) $itemData['width'] = (int) round($itemData['width']);
            if (isset($itemData['height'])) $itemData['height'] = (int) round($itemData['height']);

            $id = $itemData['id'] ?? null;
            if ($id) {
                $item = $entry->labItems()->find($id);
                if ($item) {
                    $item->update($itemData);
                    $receivedIds[] = $item->id;
                }
            } else {
                $item = $entry->labItems()->create($itemData);
                $receivedIds[] = $item->id;
            }
        }

        // Clean up items that were removed
        $entry->labItems()->whereNotIn('id', $receivedIds)->delete();

        return response()->json([
            'success' => true,
            'items' => $entry->labItems()->with('target')->get(),
        ]);
    }

    public function graduate(Request $request, LabItem $item): JsonResponse
    {
        $validated = $request->validate([
            'to_type' => ['required', 'string', 'in:note,task,idea'],
        ]);

        $toType = $validated['to_type'];
        $newEntryType = match ($toType) {
            'task' => EntryType::Task,
            'note' => EntryType::Note,
            'idea' => EntryType::Idea,
        };

        $newEntry = Entry::create([
            'type' => $newEntryType,
            'title' => $item->title ?? 'Graduated Note',
            'body' => $item->content ?? '',
            'status' => $newEntryType->defaultStatus(),
            'last_activity_at' => now(),
        ]);

        if ($newEntryType === EntryType::Task) {
            $newEntry->taskDetails()->create([
                'priority' => 1,
            ]);
        }

        $item->update([
            'type' => 'reference',
            'target_entry_id' => $newEntry->id,
            'title' => null,
            'content' => null,
        ]);

        return response()->json([
            'success' => true,
            'item' => $item->load('target'),
        ]);
    }
}
