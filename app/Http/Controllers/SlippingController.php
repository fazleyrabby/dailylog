<?php

namespace App\Http\Controllers;

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\Project;
use App\Models\SlippingSnapshot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SlippingController extends Controller
{
    public function index(): View
    {
        $now = now();
        $snapshots = SlippingSnapshot::query()
            ->where('user_id', auth()->id())
            ->whereNull('resolved_at')
            ->where(function ($q) use ($now) {
                $q->whereNull('snoozed_until')
                  ->orWhere('snoozed_until', '<=', $now);
            })
            ->with('subject')
            ->get();

        $formatted = $snapshots->map(function (SlippingSnapshot $snap) {
            $title = $snap->subject?->title ?? $snap->subject?->name ?? 'Untitled Item';
            $type = $snap->subject_type === Entry::class ? ($snap->subject?->type?->value ?? 'item') : 'project';
            
            $sev = 'low';
            if ($snap->severity === 2) {
                $sev = 'medium';
            } elseif ($snap->severity >= 3) {
                $sev = 'high';
            }

            return [
                'id' => $snap->id,
                'title' => $title,
                'type' => $type,
                'days' => $snap->slipping_since ? now()->diffInDays($snap->slipping_since) : 0,
                'severity' => $sev,
            ];
        })->toArray();

        return view('pages.slipping', [
            'slippingItems' => $formatted,
        ]);
    }

    public function resume(SlippingSnapshot $snapshot): JsonResponse
    {
        DB::transaction(function () use ($snapshot) {
            $snapshot->update(['resolved_at' => now()]);
            if ($snapshot->subject) {
                $snapshot->subject->update(['last_activity_at' => now()]);
            }
        });

        return response()->json(['success' => true]);
    }

    public function schedule(SlippingSnapshot $snapshot): JsonResponse
    {
        DB::transaction(function () use ($snapshot) {
            $snapshot->update(['resolved_at' => now()]);
            
            $subjectTitle = $snapshot->subject?->title ?? $snapshot->subject?->name ?? 'Slipping Item';
            $projectId = $snapshot->subject_type === Project::class 
                ? $snapshot->subject_id 
                : ($snapshot->subject?->project_id ?? null);

            $task = Entry::create([
                'user_id' => auth()->id(),
                'type' => EntryType::Task,
                'title' => 'Resume work on: ' . $subjectTitle,
                'status' => 'open',
                'project_id' => $projectId,
                'last_activity_at' => now(),
            ]);

            $task->taskDetails()->create([
                'due_at' => now()->addDay()->endOfDay(),
                'priority' => 2, // medium
            ]);
        });

        return response()->json(['success' => true]);
    }

    public function snooze(SlippingSnapshot $snapshot): JsonResponse
    {
        $snapshot->update([
            'snoozed_until' => now()->addDays(7),
        ]);

        return response()->json(['success' => true]);
    }

    public function letGo(SlippingSnapshot $snapshot): JsonResponse
    {
        DB::transaction(function () use ($snapshot) {
            $snapshot->update(['resolved_at' => now()]);
            
            if ($snapshot->subject) {
                if ($snapshot->subject_type === Project::class) {
                    $snapshot->subject->update([
                        'status' => 'paused',
                        'archived_at' => now(),
                    ]);
                } else {
                    $snapshot->subject->update([
                        'archived_at' => now(),
                    ]);
                }
            }
        });

        return response()->json(['success' => true]);
    }
}
