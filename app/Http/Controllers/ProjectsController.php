<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use App\Models\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ProjectsController extends Controller
{
    public function index(): View
    {
        try {
            $projects = Project::query()
                ->where('status', 'active')
                ->get();

            $formatted = $projects->map(function (Project $project) {
                // Get all tasks
                $tasks = Entry::query()
                    ->tasks()
                    ->where('project_id', $project->id)
                    ->active()
                    ->with('taskDetails')
                    ->get();

                $totalTasks = $tasks->count();
                $completedTasks = $tasks->filter(fn ($t) => !is_null($t->taskDetails?->completed_at))->count();
                $openTasks = $tasks->filter(fn ($t) => is_null($t->taskDetails?->completed_at));

                $progress = $totalTasks > 0 ? (int) round(($completedTasks / $totalTasks) * 100) : 0;

                // Open tasks formatted
                $openTasksFormatted = $openTasks->map(fn ($t) => [
                    'id' => $t->id,
                    'title' => $t->title,
                    'priority' => match ($t->taskDetails?->priority) {
                        0, 1 => 'low',
                        2 => 'medium',
                        3 => 'high',
                        default => 'medium',
                    },
                ])->values()->toArray();

                // Linked notes
                $notes = Entry::query()
                    ->notes()
                    ->where('project_id', $project->id)
                    ->active()
                    ->orderByDesc('updated_at')
                    ->get()
                    ->map(fn ($n) => [
                        'id' => $n->id,
                        'title' => $n->title ?? 'Untitled Note',
                        'updated' => $n->updated_at ? $n->updated_at->diffForHumans() : 'Just now',
                        'updated_timestamp' => $n->updated_at,
                    ]);

                // Construct activity timeline dynamically
                $activity = [];

                // 1. Completed tasks
                $recentlyCompleted = $tasks->filter(fn ($t) => !is_null($t->taskDetails?->completed_at))
                    ->sortByDesc(fn ($t) => $t->taskDetails->completed_at)
                    ->take(5);

                foreach ($recentlyCompleted as $t) {
                    $activity[] = [
                        'event' => 'Task completed: ' . $t->title,
                        'date' => $t->taskDetails->completed_at->diffForHumans(),
                        'timestamp' => $t->taskDetails->completed_at,
                    ];
                }

                // 2. Created notes
                $recentlyCreatedNotes = $notes->sortByDesc('updated_timestamp')->take(5);
                foreach ($recentlyCreatedNotes as $n) {
                    $activity[] = [
                        'event' => 'Note added: ' . $n['title'],
                        'date' => $n['updated_timestamp'] ? $n['updated_timestamp']->diffForHumans() : 'Just now',
                        'timestamp' => $n['updated_timestamp'],
                    ];
                }

                // Sort activity by timestamp desc
                usort($activity, function ($a, $b) {
                    if (!$a['timestamp'] || !$b['timestamp']) return 0;
                    return $b['timestamp']->getTimestamp() <=> $a['timestamp']->getTimestamp();
                });
                $activity = array_slice($activity, 0, 5);

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'desc' => $project->description ?? '',
                    'status' => $project->status,
                    'color' => $project->color ?? 'orange',
                    'progress' => $progress,
                    'tasks' => $openTasksFormatted,
                    'notes' => $notes->map(fn ($n) => [
                        'id' => $n['id'],
                        'title' => $n['title'],
                        'updated' => $n['updated'],
                    ])->toArray(),
                    'activity' => $activity,
                ];
            })->toArray();

            return view('pages.projects', [
                'projects' => $formatted,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to load projects index: " . $e->getMessage(), [
                'exception' => $e
            ]);
            abort(500, "Failed to load projects page.");
        }
    }
}
