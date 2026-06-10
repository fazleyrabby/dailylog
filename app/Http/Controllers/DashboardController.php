<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use App\Models\Project;
use App\Models\SlippingSnapshot;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $todayEnd = now()->endOfDay();
        $tomorrowStart = now()->addDay()->startOfDay();
        $sevenDaysEnd = now()->addDays(7)->endOfDay();

        // 1. Greeting row aggregates
        $todayTasksCount = Entry::query()
            ->tasks()
            ->active()
            ->whereHas('taskDetails', fn ($q) => $q->whereNull('completed_at')->where('due_at', '<=', $todayEnd))
            ->count();

        $slippingCount = SlippingSnapshot::query()
            ->whereNull('resolved_at')
            ->count();

        // 2. Pinned focus items
        $focusItems = Entry::query()
            ->where('pinned', true)
            ->whereIn('type', ['task', 'note'])
            ->active()
            ->with(['project'])
            ->get()
            ->map(fn (Entry $entry) => [
                'id' => $entry->id,
                'title' => $entry->title ?? 'Untitled Pinned Item',
                'type' => $entry->type->value,
                'project' => $entry->project?->name ?? 'None',
            ])->toArray();

        // 3. Today's Agenda tasks
        $activeTasks = Entry::query()
            ->tasks()
            ->active()
            ->whereHas('taskDetails', fn ($q) => $q->whereNull('completed_at')->where('due_at', '<=', $todayEnd))
            ->with(['taskDetails', 'project'])
            ->get()
            ->map(fn (Entry $entry) => [
                'id' => $entry->id,
                'title' => $entry->title,
                'priority' => match ($entry->taskDetails?->priority) {
                    0, 1 => 'low',
                    2 => 'medium',
                    3 => 'high',
                    default => 'medium',
                },
                'project' => $entry->project?->name ?? 'None',
                'completed' => false,
            ])->toArray();

        // 4. Upcoming Horizon tasks (next 7 days excluding today)
        $upcomingTasks = Entry::query()
            ->tasks()
            ->active()
            ->whereHas('taskDetails', fn ($q) => $q->whereNull('completed_at')->whereBetween('due_at', [$tomorrowStart, $sevenDaysEnd]))
            ->with(['taskDetails', 'project'])
            ->get()
            ->map(fn (Entry $entry) => [
                'id' => $entry->id,
                'title' => $entry->title,
                'due' => $entry->taskDetails?->due_at?->diffForHumans() ?? 'Upcoming',
                'project' => $entry->project?->name ?? 'None',
            ])->toArray();

        // 5. Slipping items
        $slipping = SlippingSnapshot::query()
            ->whereNull('resolved_at')
            ->with('subject')
            ->get()
            ->map(fn ($slip) => [
                'id' => $slip->id,
                'title' => $slip->subject?->title ?? $slip->subject?->name ?? 'Untitled Item',
                'days' => $slip->slipping_since ? now()->diffInDays($slip->slipping_since) : 0,
                'type' => $slip->subject_type === Entry::class ? ($slip->subject?->type?->value ?? 'item') : 'project',
            ])->toArray();

        // 6. Active projects
        $projects = Project::query()
            ->where('status', 'active')
            ->withCount(['entries as tasks_count' => fn ($q) => $q->tasks()->whereNull('archived_at')->whereHas('taskDetails', fn ($td) => $td->whereNull('completed_at'))])
            ->get()
            ->map(fn ($proj) => [
                'id' => $proj->id,
                'name' => $proj->name,
                'desc' => $proj->description ?? '',
                'tasks_count' => $proj->tasks_count,
                'color' => $proj->color ?? 'orange',
            ])->toArray();

        // 7. Recent activity (Modified notes)
        $recentNotes = Entry::query()
            ->notes()
            ->active()
            ->orderByDesc('updated_at')
            ->take(5)
            ->get()
            ->map(fn (Entry $entry) => [
                'id' => $entry->id,
                'title' => $entry->title ?? 'Untitled Note',
                'updated_at' => $entry->updated_at ? $entry->updated_at->diffForHumans() : 'Just now',
                'body' => strip_tags(str_replace("\n", " ", $entry->body ?? '')),
            ])->toArray();

        return view('pages.dashboard', [
            'greetingDate' => now()->format('l, F j'),
            'todayTasksCount' => $todayTasksCount,
            'slippingCount' => $slippingCount,
            'focusItems' => $focusItems,
            'activeTasks' => $activeTasks,
            'upcomingTasks' => $upcomingTasks,
            'slipping' => $slipping,
            'projects' => $projects,
            'recentNotes' => $recentNotes,
        ]);
    }

    public function togglePin(Entry $entry): JsonResponse
    {
        DB::beginTransaction();
        try {
            $entry->update(['pinned' => !$entry->pinned]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to toggle pin for entry {$entry->id}: " . $e->getMessage(), [
                'entry_id' => $entry->id,
                'exception' => $e
            ]);
            return response()->json(['message' => 'Failed to update focus status.'], 500);
        }

        return response()->json([
            'success' => true,
            'pinned' => $entry->pinned,
        ]);
    }
}
