<?php

namespace App\Http\Controllers;

use App\Domains\Capture\Services\CaptureService;
use App\Enums\CapturedVia;
use App\Models\Entry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TasksController extends Controller
{
    public function __construct(private readonly CaptureService $capture)
    {
    }

    public function index(): View
    {
        $allTasks = Entry::query()
            ->tasks()
            ->active()
            ->with(['taskDetails', 'project', 'tags'])
            ->get();

        $formatted = [
            'inbox' => [],
            'today' => [],
            'upcoming' => [],
            'completed' => [],
        ];

        $todayEnd = now()->endOfDay();

        foreach ($allTasks as $entry) {
            $details = $entry->taskDetails;
            if (!$details) {
                continue;
            }

            $formattedTask = $this->formatTask($entry);

            if ($details->completed_at !== null) {
                $formatted['completed'][] = $formattedTask;
            } elseif ($details->due_at === null) {
                $formatted['inbox'][] = $formattedTask;
            } elseif ($details->due_at->lte($todayEnd)) {
                $formatted['today'][] = $formattedTask;
            } else {
                $formatted['upcoming'][] = $formattedTask;
            }
        }

        return view('pages.tasks', [
            'tasks' => $formatted,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => ['required', 'string'],
        ]);

        $title = $request->input('title');
        
        // Ensure "task " prefix exists if no other verb matches, or just use the title as-is.
        // CaptureService parses verb-first, default to note. If we want it to be a task,
        // we prepend "task " if not starting with a known verb.
        $verbs = ['task', 'note', 'journal', 'bookmark', 'quote', 'idea', 'learning', 'resource'];
        $firstWord = strtolower(explode(' ', trim($title))[0]);
        if (!in_array($firstWord, $verbs)) {
            $title = 'task ' . $title;
        }

        $entry = $this->capture->capture($title, CapturedVia::Web);

        return response()->json([
            'task' => $this->formatTask($entry),
        ], 201);
    }

    public function toggle(Entry $entry): JsonResponse
    {
        $details = $entry->taskDetails;
        if (!$details) {
            return response()->json(['message' => 'Task details not found.'], 404);
        }

        DB::transaction(function () use ($details) {
            if ($details->completed_at === null) {
                $details->update(['completed_at' => now()]);
            } else {
                $details->update(['completed_at' => null]);
            }
        });

        return response()->json([
            'task' => $this->formatTask($entry->fresh(['taskDetails', 'project', 'tags'])),
        ]);
    }

    public function update(Request $request, Entry $entry): JsonResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string'],
            'priority' => ['sometimes', 'required', 'string', 'in:low,medium,high'],
        ]);

        if ($request->has('title')) {
            $entry->update([
                'title' => $data['title'],
            ]);
        }

        if ($request->has('priority')) {
            $priorityVal = match ($data['priority']) {
                'low' => 1,
                'medium' => 2,
                'high' => 3,
                default => 1,
            };
            $entry->taskDetails()->update([
                'priority' => $priorityVal,
            ]);
        }

        return response()->json([
            'task' => $this->formatTask($entry->fresh(['taskDetails', 'project', 'tags'])),
        ]);
    }

    public function destroy(Entry $entry): JsonResponse
    {
        // Archive the task by setting archived_at = now()
        $entry->update(['archived_at' => now()]);

        return response()->json([
            'success' => true,
        ]);
    }

    private function formatTask(Entry $entry): array
    {
        $priorityVal = $entry->taskDetails?->priority ?? 1;
        $priority = match ($priorityVal) {
            0 => 'low',
            1 => 'low',
            2 => 'medium',
            3 => 'high',
            default => 'medium',
        };

        $dueAt = $entry->taskDetails?->due_at;
        $dueText = null;
        if ($dueAt) {
            if ($dueAt->isToday()) {
                $dueText = 'Today';
            } elseif ($dueAt->isTomorrow()) {
                $dueText = 'Tomorrow';
            } elseif ($dueAt->isYesterday()) {
                $dueText = 'Yesterday';
            } else {
                $dueText = $dueAt->diffForHumans();
            }
        }

        return [
            'id' => $entry->id,
            'title' => $entry->title,
            'priority' => $priority,
            'project' => $entry->project?->name ?? 'None',
            'tags' => $entry->tags->pluck('name')->toArray(),
            'due' => $dueText,
            'completed' => !is_null($entry->taskDetails?->completed_at),
        ];
    }
}
