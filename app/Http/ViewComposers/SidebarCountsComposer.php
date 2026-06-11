<?php

namespace App\Http\ViewComposers;

use App\Models\Entry;
use App\Models\Project;
use App\Models\SlippingSnapshot;
use Illuminate\View\View;

class SidebarCountsComposer
{
    public function compose(View $view): void
    {
        if (! auth()->check()) {
            $view->with('sidebarCounts', ['tasks' => 0, 'learning' => 0, 'projects' => 0, 'slipping' => 0, 'wallets' => 0]);
            return;
        }

        $tasksToday = Entry::query()
            ->where('type', 'task')
            ->where('status', 'open')
            ->whereNull('archived_at')
            ->whereHas('taskDetails', fn ($q) => $q
                ->whereNull('completed_at')
                ->whereDate('due_at', '<=', now()->endOfDay())
            )
            ->count();

        $learningActive = Entry::query()
            ->where('type', 'learning')
            ->where('status', 'active')
            ->whereNull('archived_at')
            ->count();

        $projectsActive = Project::query()
            ->where('status', 'active')
            ->whereNull('archived_at')
            ->count();

        $slipping = SlippingSnapshot::query()
            ->whereNull('resolved_at')
            ->where(fn ($q) => $q->whereNull('snoozed_until')->orWhere('snoozed_until', '<=', now()))
            ->count();

        $walletsCount = Entry::query()
            ->where('type', 'wallet')
            ->where('status', 'active')
            ->whereNull('archived_at')
            ->count();

        $view->with('sidebarCounts', [
            'tasks' => $tasksToday,
            'learning' => $learningActive,
            'projects' => $projectsActive,
            'slipping' => $slipping,
            'wallets' => $walletsCount,
        ]);
    }
}
