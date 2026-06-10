@extends('layouts.app')

@section('title', 'Dashboard')
@section('header_breadcrumbs', 'DAILYLOG // DASHBOARD')

@section('content')
<div x-data="dashboardComponent({{ json_encode($focusItems) }}, {{ json_encode($activeTasks) }}, {{ json_encode($upcomingTasks) }})" class="max-w-6xl mx-auto space-y-6">

    <!-- Dashboard Greeting Row -->
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-4 border-b border-border">
        <div>
            <div class="flex items-center space-x-3">
                <h1 class="text-xl font-bold tracking-tight text-text-main">Good morning, Developer.</h1>
                @if($streak > 0)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-accent/15 text-accent border border-accent/25 font-mono select-none">
                        🔥 {{ $streak }} day streak
                    </span>
                @endif
            </div>
            <p class="text-xs text-text-muted mt-1">{{ $greetingDate }} · {{ $todayTasksCount }} tasks due today · {{ $slippingCount }} items slipping</p>
        </div>
        <div class="mt-3 md:mt-0 flex space-x-2">
            <x-ui.button variant="primary" @click="$dispatch('open-palette')">
                <span class="mr-1">⚡</span> Quick Capture
            </x-ui.button>
        </div>
    </div>

    <!-- Main Dashboard Two-Column Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- LEFT COLUMN: Primary Workspace (Takes 2/3 space) -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- DAILY FOCUS -->
            <x-ui.card>
                <x-slot name="header">
                    <div class="flex items-center space-x-1.5">
                        <span class="text-accent">◉</span>
                        <h4 class="font-bold text-xs uppercase tracking-wider text-text-main">Daily Focus</h4>
                    </div>
                    <span class="text-xxs text-text-subtle font-mono">Pinned today</span>
                </x-slot>

                <div class="space-y-2">
                    <template x-if="focusItems.length === 0">
                        <div class="py-4 text-center text-xs text-text-muted border border-dashed border-border rounded-sm">
                            No active focus items. Pin notes or tasks to keep them visible here.
                        </div>
                    </template>
                    <template x-for="(item, idx) in focusItems" :key="item.id">
                        <div class="flex items-center justify-between p-2.5 bg-surface-2/40 border border-border rounded-sm text-xs group hover:bg-surface-2 transition-colors">
                            <div class="flex items-center space-x-2.5 truncate">
                                <span :class="{
                                    'bg-type-note': item.type === 'note',
                                    'bg-type-task': item.type === 'task'
                                }" class="h-2 w-2 rounded-full flex-shrink-0"></span>
                                <span class="font-semibold font-mono text-[10px] text-text-subtle uppercase px-1 bg-surface border border-border" x-text="item.type"></span>
                                <span class="font-medium text-text-main truncate" x-text="item.title"></span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-[10px] font-mono text-text-muted" x-text="'@' + item.project"></span>
                                <button @click="unfocus(idx)" class="text-text-subtle hover:text-danger focus:outline-none cursor-pointer text-sm font-bold" title="Remove focus">
                                    &times;
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </x-ui.card>

            <!-- IN-PAGE QUICK CAPTURE WIDGET -->
            <x-ui.card>
                <x-slot name="header">
                    <div class="flex items-center space-x-1.5">
                        <span class="text-accent">⚡</span>
                        <h4 class="font-bold text-xs uppercase tracking-wider text-text-main">Quick Capture Widget</h4>
                    </div>
                    <span class="text-xxs text-text-subtle font-mono">Type grammar (e.g. task Buy milk @Self)</span>
                </x-slot>
                <form @submit.prevent="submitQuickCapture()" class="flex space-x-2">
                    <div class="flex-grow">
                        <input 
                            type="text" 
                            x-model="quickCaptureInput"
                            placeholder="Capture a thought... (e.g. task Review PR @DailyLOG #ops)"
                            class="w-full bg-surface border border-border rounded-sm px-3 py-1.5 text-xs focus:outline-none focus:border-accent text-text-main placeholder-text-muted font-mono"
                        />
                    </div>
                    <button type="submit" class="bg-accent hover:bg-accent-hover text-white px-4 py-1.5 rounded-sm text-xs font-semibold cursor-pointer transition-colors">
                        Capture
                    </button>
                </form>
            </x-ui.card>

            <!-- TODAY'S TASKS -->
            <x-ui.card>
                <x-slot name="header">
                    <div class="flex items-center space-x-1.5">
                        <span class="text-type-task">☑</span>
                        <h4 class="font-bold text-xs uppercase tracking-wider text-text-main">Today's Agenda</h4>
                    </div>
                    <a href="/tasks" class="text-xxs text-accent hover:underline">View all tasks &rarr;</a>
                </x-slot>

                <!-- Journal prompt helper -->
                <div class="mb-4 p-2.5 bg-accent-subtle-bg/60 border border-accent/20 rounded-sm text-xs flex items-start space-x-3">
                    <span class="text-accent text-sm mt-0.5">◷</span>
                    <div class="space-y-1">
                        <div class="font-semibold text-accent">Journal prompt of the day</div>
                        <div class="text-text-muted italic">"What technical bottlenecks did you hit yesterday, and how will you bypass them today?"</div>
                        <a href="/journal" class="text-[10px] text-accent font-semibold hover:underline block mt-1">Start writing response &rarr;</a>
                    </div>
                </div>

                <!-- Task list rows -->
                <div class="space-y-1.5">
                    <template x-if="activeTasks.length === 0">
                        <div class="py-4 text-center text-xs text-text-muted border border-dashed border-border rounded-sm">
                            No tasks scheduled for today. Take it easy!
                        </div>
                    </template>
                    <template x-for="task in activeTasks" :key="task.id">
                        <div class="flex items-center justify-between p-2 hover:bg-surface-2/40 border-b border-border last:border-b-0 text-xs">
                            <div class="flex items-center space-x-3 min-w-0">
                                <input 
                                    type="checkbox" 
                                    :checked="task.completed"
                                    @click="toggleTask(task.id)"
                                    class="rounded-sm border-border bg-surface text-accent focus:ring-accent cursor-pointer"
                                />
                                <span 
                                    :class="task.completed ? 'line-through text-text-subtle' : 'text-text-main font-medium'"
                                    class="truncate" 
                                    x-text="task.title"
                                ></span>
                            </div>
                            <div class="flex items-center space-x-3 flex-shrink-0">
                                <span class="bg-danger/5 text-danger border border-danger/20 px-1.5 py-0.2 rounded-full text-[9px] uppercase font-semibold" x-text="task.priority"></span>
                                <span class="text-[10px] font-mono text-text-muted" x-text="'@' + task.project"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </x-ui.card>

            <!-- UPCOMING -->
            <x-ui.card title="Upcoming Horizon (7 Days)">
                <div class="space-y-3">
                    <template x-if="upcomingTasks.length === 0">
                        <div class="py-4 text-center text-xs text-text-muted border border-dashed border-border rounded-sm">
                            No upcoming tasks due in the next 7 days.
                        </div>
                    </template>
                    <template x-for="task in upcomingTasks" :key="task.id">
                        <div class="flex items-center justify-between p-2 hover:bg-surface-2/40 border border-border/60 rounded-sm text-xs">
                            <div class="flex items-center space-x-3">
                                <span class="h-1.5 w-1.5 bg-type-task rounded-full"></span>
                                <span class="text-text-main font-medium" x-text="task.title"></span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="text-[10px] text-text-muted font-mono" x-text="task.due"></span>
                                <span class="text-[10px] font-mono text-text-subtle" x-text="'@' + task.project"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </x-ui.card>

        </div>

        <!-- RIGHT COLUMN: Context & Awareness (Takes 1/3 space) -->
        <div class="space-y-6">
            
            <!-- SLIPPING ITEMS -->
            <x-ui.card>
                <x-slot name="header">
                    <div class="flex items-center space-x-1.5">
                        <span class="text-warning">⚠</span>
                        <h4 class="font-bold text-xs uppercase tracking-wider text-text-main">Slipping ({{ count($slipping) }})</h4>
                    </div>
                    <a href="/slipping" class="text-xxs text-warning hover:underline">Review all &rarr;</a>
                </x-slot>

                <div class="space-y-2.5">
                    @if(count($slipping) === 0)
                        <div class="py-4 text-center text-xs text-text-muted border border-dashed border-border rounded-sm bg-surface">
                            All items are active. Nothing is slipping!
                        </div>
                    @endif
                    @foreach($slipping as $slip)
                        <div class="p-2 border border-border hover:border-warning/30 bg-surface rounded-sm text-xs flex items-center justify-between group transition-all">
                            <div class="min-w-0">
                                <div class="font-semibold text-text-main truncate text-[11px]">{{ $slip['title'] }}</div>
                                <div class="text-[10px] text-text-muted mt-0.5">Untouched for <span class="font-mono text-warning font-semibold">{{ $slip['days'] }} days</span></div>
                            </div>
                            <div class="flex items-center space-x-1">
                                <span class="bg-surface-2 text-[9px] font-mono px-1 rounded-sm text-text-subtle uppercase border border-border">{{ $slip['type'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>

            <!-- ACTIVE PROJECTS -->
            <x-ui.card>
                <x-slot name="header">
                    <div class="flex items-center space-x-1.5">
                        <span class="text-type-project">❏</span>
                        <h4 class="font-bold text-xs uppercase tracking-wider text-text-main">Active Projects</h4>
                    </div>
                    <a href="/projects" class="text-xxs text-accent hover:underline">Manage &rarr;</a>
                </x-slot>

                <div class="space-y-2">
                    @if(count($projects) === 0)
                        <div class="py-4 text-center text-xs text-text-muted border border-dashed border-border rounded-sm bg-surface">
                            No active projects.
                        </div>
                    @endif
                    @foreach($projects as $proj)
                        <a href="/projects" class="block p-2 border border-border bg-surface-2/10 hover:bg-surface-2/40 rounded-sm text-xs transition-colors">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-text-main flex items-center">
                                    <span class="h-2 w-2 rounded-full mr-2" style="background-color: var(--color-type-{{ strtolower($proj['name']) }}, #EA580C)"></span>
                                    {{ $proj['name'] }}
                                </span>
                                <span class="text-[10px] text-text-subtle font-mono">{{ $proj['tasks_count'] }} tasks</span>
                            </div>
                            <p class="text-[10px] text-text-muted mt-1 truncate">{{ $proj['desc'] }}</p>
                        </a>
                    @endforeach
                </div>
            </x-ui.card>

            <!-- UNIFIED ACTIVITY TIMELINE -->
            <x-ui.card title="Recent Activity Feed">
                <div class="space-y-3">
                    @if(count($timeline) === 0)
                        <div class="py-4 text-center text-xs text-text-muted border border-dashed border-border rounded-sm bg-surface">
                            No recent activity.
                        </div>
                    @endif
                    @foreach($timeline as $item)
                        <div class="p-2 bg-surface border border-border/80 rounded-sm text-xs space-y-1">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-1.5 min-w-0">
                                    <span class="text-[10px]" title="{{ ucfirst($item['type']) }}">
                                        @if($item['type'] === 'note') ✎
                                        @elseif($item['type'] === 'task') ✓
                                        @else ⚲
                                        @endif
                                    </span>
                                    <span class="font-semibold text-text-main truncate text-[11px]">{{ $item['title'] }}</span>
                                </div>
                                <span class="text-[9px] text-text-subtle font-mono flex-shrink-0 ml-2">{{ $item['time_ago'] }}</span>
                            </div>
                            <p class="text-[10px] text-text-muted truncate select-text leading-relaxed">{{ $item['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>

        </div>

    </div>

</div>

<script>
window.dashboardComponent = function(initialFocus, initialTasks, initialUpcoming) {
    return {
        focusItems: initialFocus,
        activeTasks: initialTasks,
        upcomingTasks: initialUpcoming,
        quickCaptureInput: '',
        
        submitQuickCapture() {
            if (!this.quickCaptureInput) return;
            fetch('/capture', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ input: this.quickCaptureInput })
            })
            .then(res => res.json())
            .then(data => {
                if (data.id) {
                    window.dispatchEvent(new CustomEvent('show-toast', { 
                        detail: { message: `Captured thought successfully.` }
                    }));
                    this.quickCaptureInput = '';
                    
                    // Reload the dashboard after capture so new tasks/notes immediately populate
                    setTimeout(() => window.location.reload(), 600);
                }
            });
        },
        
        unfocus(idx) {
            let item = this.focusItems[idx];
            fetch(`/entries/${item.id}/toggle-pin`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.focusItems.splice(idx, 1);
                    window.dispatchEvent(new CustomEvent('show-toast', { 
                        detail: { message: 'Unpinned from Daily Focus: ' + item.title.substring(0, 20) + '...' }
                    }));
                }
            });
        },
        
        toggleTask(id) {
            fetch(`/tasks/${id}/toggle`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.task) {
                    let t = this.activeTasks.find(x => x.id === id);
                    if (t) {
                        t.completed = data.task.completed;
                        window.dispatchEvent(new CustomEvent('show-toast', { 
                            detail: { message: t.completed ? 'Task completed' : 'Task marked active' }
                        }));
                        if (t.completed) {
                            setTimeout(() => {
                                this.activeTasks = this.activeTasks.filter(x => x.id !== id);
                            }, 1000);
                        }
                    }
                }
            });
        }
    };
};
</script>
@endsection
