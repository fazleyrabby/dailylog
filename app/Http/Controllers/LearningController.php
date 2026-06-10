<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class LearningController extends Controller
{
    public function index(): View
    {
        $entries = Entry::query()
            ->learnings()
            ->active()
            ->with(['learningDetails', 'tags', 'links'])
            ->orderByDesc('updated_at')
            ->get();

        $formatted = $entries->map(fn (Entry $entry) => $this->formatLearning($entry))->toArray();

        return view('pages.learning', [
            'learningPaths' => $formatted,
        ]);
    }

    public function completeUnit(Entry $entry): JsonResponse
    {
        $details = $entry->learningDetails;
        if ($details && $details->completed_units < $details->total_units) {
            DB::beginTransaction();
            try {
                $newCompleted = $details->completed_units + 1;
                $newProgress = (int) round(($newCompleted / $details->total_units) * 100);

                $details->update([
                    'completed_units' => $newCompleted,
                    'progress' => $newProgress,
                ]);

                $entry->update(['last_activity_at' => now()]);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Failed to complete learning unit for entry {$entry->id}: " . $e->getMessage(), [
                    'entry_id' => $entry->id,
                    'exception' => $e
                ]);
                return response()->json(['message' => 'Failed to update unit progress.'], 500);
            }
        }

        return response()->json([
            'path' => $this->formatLearning($entry->load(['learningDetails', 'tags', 'links'])),
        ]);
    }

    private function formatLearning(Entry $entry): array
    {
        $details = $entry->learningDetails;
        
        // Eager-loaded links
        $linkedTasks = $entry->links->where('type', 'task')->map(fn ($e) => [
            'title' => $e->title,
            'completed' => $e->status === 'done',
        ])->values()->toArray();

        $linkedNotes = $entry->links->where('type', 'note')->map(fn ($e) => [
            'title' => $e->title,
            'updated' => $e->updated_at ? $e->updated_at->diffForHumans() : 'Just now',
        ])->values()->toArray();

        // Check if slipping: last activity > 30 days ago and progress < 100%
        $slipping = false;
        if ($entry->last_activity_at && $entry->last_activity_at->lt(now()->subDays(30)) && ($details?->progress ?? 0) < 100) {
            $slipping = true;
        }

        return [
            'id' => $entry->id,
            'title' => $entry->title ?? 'Untitled Path',
            'kind' => $details?->kind ?? 'topic',
            'provider' => $details?->provider ?? 'Unknown',
            'completedUnits' => $details?->completed_units ?? 0,
            'totalUnits' => $details?->total_units ?? 10,
            'progress' => $details?->progress ?? 0,
            'status' => $details?->status ?? 'active',
            'tags' => $entry->tags->pluck('name')->toArray(),
            'lastActive' => $entry->last_activity_at 
                ? $entry->last_activity_at->diffForHumans() . ($slipping ? ' (Slipping)' : '') 
                : 'Never',
            'slipping' => $slipping,
            'tasks' => $linkedTasks,
            'notes' => $linkedNotes,
        ];
    }
}
