@extends('layouts.app')

@section('title', 'Dashboard')
@section('header_breadcrumbs', 'DAILYLOG // DASHBOARD')

@section('content')
<div x-data="dashboardComponent({{ json_encode($focusItems) }}, {{ json_encode($activeTasks) }}, {{ json_encode($upcomingTasks) }})" class="max-w-6xl mx-auto space-y-6">

    <!-- Today Page Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-4 border-b border-border">
        <div>
            <div class="text-[10px] font-bold text-accent font-mono uppercase tracking-widest">// what matters today?</div>
            <h1 class="text-xl font-bold tracking-tight text-text-main mt-1">Good morning, Developer.</h1>
            <p class="text-xs text-text-muted mt-0.5">{{ $greetingDate }} &middot; {{ $todayTasksCount }} tasks due today &middot; {{ $slippingCount }} items slipping</p>
        </div>
        <div class="mt-3 md:mt-0 flex space-x-2">
            <x-ui.button variant="primary" @click="$dispatch('open-palette')">
                <span class="mr-1">⚡</span> Quick Capture (⌘K)
            </x-ui.button>
        </div>
    </div>

    <!-- Main Workspace Grid (Bento Grid Layout) -->
    <!-- Main Workspace Grid (Two-Column Layout to prevent gaps) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Column: Tasks, Focus, Horizon, Bookmarks & Resources (2/3 width) -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- DAILY FOCUS -->
            <x-ui.card>
                <x-slot name="header">
                    <div class="flex items-center space-x-2">
                        <span class="text-accent text-sm font-mono">&#9673;</span>
                        <h4 class="font-bold text-xs uppercase tracking-wider text-text-main font-mono">Current Focus</h4>
                    </div>
                    <span class="text-xxs text-text-subtle font-mono">// pinned items</span>
                </x-slot>

                <div class="space-y-1.5">
                    <template x-if="focusItems.length === 0">
                        <div class="py-6 text-center text-xs text-text-muted border border-dashed border-border rounded-sm bg-surface-2/10">
                            No active focus items. Pin notes or tasks to keep them visible here.
                        </div>
                    </template>
                    <template x-for="(item, idx) in focusItems" :key="item.id">
                        <div class="flex items-center justify-between p-2.5 bg-surface-2/40 border border-border rounded-sm text-xs hover:bg-surface-2/80 transition-colors">
                            <div class="flex items-center space-x-2.5 min-w-0">
                                <span :class="{
                                    'bg-accent': item.type === 'note',
                                    'bg-type-task': item.type === 'task'
                                }" class="h-2 w-2 rounded-full flex-shrink-0"></span>
                                <span class="font-bold font-mono text-[9px] text-text-subtle uppercase px-1.5 py-0.2 bg-surface border border-border rounded-xs" x-text="item.type"></span>
                                <span class="font-semibold text-text-main truncate" x-text="item.title"></span>
                            </div>
                            <div class="flex items-center space-x-3 ml-2 flex-shrink-0">
                                <span class="text-[10px] font-mono text-text-muted" x-text="'@' + item.project"></span>
                                <button @click="unfocus(idx)" class="text-text-subtle hover:text-danger focus:outline-none cursor-pointer text-base font-semibold leading-none px-1" title="Remove focus">
                                    &times;
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </x-ui.card>

            <!-- TODAY'S AGENDA -->
            <x-ui.card>
                <x-slot name="header">
                    <div class="flex items-center space-x-2">
                        <span class="text-accent text-sm font-mono">&#9745;</span>
                        <h4 class="font-bold text-xs uppercase tracking-wider text-text-main font-mono">Today's Agenda</h4>
                    </div>
                    <a href="/tasks" class="text-xxs text-accent hover:underline font-mono">View all tasks &rarr;</a>
                </x-slot>

                <!-- Daily Reflective Prompt -->
                <div class="mb-4 p-3 bg-accent-subtle-bg/40 border border-accent/20 rounded-sm text-xs flex items-start space-x-3">
                    <span class="text-accent text-base leading-none">◷</span>
                    <div class="space-y-1">
                        <div class="font-bold text-accent font-mono text-[10px] uppercase tracking-wide">Journal Prompt of the Day</div>
                        <div class="text-text-muted italic font-serif-reading text-sm leading-relaxed">"What technical bottlenecks did you hit yesterday, and how will you bypass them today?"</div>
                        <a href="/journal" class="text-[10px] text-accent font-semibold hover:underline block mt-1">Start writing response &rarr;</a>
                    </div>
                </div>

                <!-- Active Tasks -->
                <div class="space-y-1">
                    <template x-if="activeTasks.length === 0">
                        <div class="py-6 text-center text-xs text-text-muted border border-dashed border-border rounded-sm bg-surface-2/10">
                            No tasks scheduled for today. Take it easy!
                        </div>
                    </template>
                    <template x-for="task in activeTasks" :key="task.id">
                        <div class="flex items-center justify-between p-2 hover:bg-surface-2/30 border-b border-border last:border-b-0 text-xs">
                            <div class="flex items-center space-x-3 min-w-0">
                                <input 
                                    type="checkbox" 
                                    :checked="task.completed"
                                    @click="toggleTask(task.id)"
                                    class="rounded-xs border-border bg-surface text-accent focus:ring-accent cursor-pointer"
                                />
                                <span 
                                    :class="task.completed ? 'line-through text-text-subtle' : 'text-text-main font-medium'"
                                    class="truncate" 
                                    x-text="task.title"
                                ></span>
                            </div>
                            <div class="flex items-center space-x-3 flex-shrink-0 ml-2">
                                <span class="bg-danger/5 text-danger border border-danger/20 px-1.5 py-0.2 rounded-xs text-[9px] uppercase font-bold font-mono" x-text="task.priority"></span>
                                <span class="text-[10px] font-mono text-text-muted" x-text="'@' + task.project"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </x-ui.card>

            <!-- UPCOMING HORIZON -->
            <x-ui.card>
                <x-slot name="header">
                    <div class="flex items-center space-x-2">
                        <span class="text-text-subtle text-sm font-mono">&#9639;</span>
                        <h4 class="font-bold text-xs uppercase tracking-wider text-text-main font-mono">Upcoming Horizon (7 Days)</h4>
                    </div>
                    <span class="text-xxs text-text-subtle font-mono">// schedule</span>
                </x-slot>

                <div class="space-y-1.5">
                    <template x-if="upcomingTasks.length === 0">
                        <div class="py-4 text-center text-xs text-text-muted border border-dashed border-border rounded-sm bg-surface-2/10">
                            No upcoming tasks due in the next 7 days.
                        </div>
                    </template>
                    <template x-for="task in upcomingTasks" :key="task.id">
                        <div class="flex items-center justify-between p-2.5 hover:bg-surface-2/30 border-border/60 border rounded-sm text-xs">
                            <div class="flex items-center space-x-3 min-w-0">
                                <span class="h-1.5 w-1.5 bg-type-task rounded-full flex-shrink-0"></span>
                                <span class="text-text-main font-medium truncate" x-text="task.title"></span>
                            </div>
                            <div class="flex items-center space-x-3 ml-2 flex-shrink-0">
                                <span class="text-[10px] text-text-muted font-mono" x-text="task.due"></span>
                                <span class="text-[10px] font-mono text-text-subtle" x-text="'@' + task.project"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </x-ui.card>

            <!-- Bookmarks & Resources Sub-Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- RECENT BOOKMARKS -->
                <x-ui.card>
                    <x-slot name="header">
                        <div class="flex items-center space-x-1.5">
                            <span class="text-type-bookmark text-sm font-mono">⚲</span>
                            <h4 class="font-bold text-xs uppercase tracking-wider text-text-main font-mono">Recent Bookmarks</h4>
                        </div>
                        <a href="/bookmarks" class="text-xxs text-accent hover:underline font-mono">All Bookmarks &rarr;</a>
                    </x-slot>

                    <div class="space-y-3">
                        @if(count($recentBookmarksList) === 0)
                            <div class="py-6 text-center text-xs text-text-muted border border-dashed border-border rounded-sm bg-surface">
                                No bookmarks captured yet.
                            </div>
                        @endif
                        @foreach($recentBookmarksList as $b)
                            <div class="p-2 border-b border-border last:border-b-0 text-xs space-y-1">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0 flex-grow">
                                        <a href="{{ $b['url'] }}" target="_blank" class="font-bold text-accent hover:underline truncate block" title="{{ $b['title'] }}">
                                            {{ $b['title'] }}
                                        </a>
                                        @if($b['site'])
                                            <span class="text-[10px] text-text-subtle font-mono block">{{ $b['site'] }}</span>
                                        @endif
                                    </div>
                                    <span class="text-[9px] text-text-subtle font-mono whitespace-nowrap">{{ $b['added'] }}</span>
                                </div>
                                @if($b['desc'])
                                    <p class="text-[10px] text-text-muted line-clamp-2">{{ $b['desc'] }}</p>
                                @endif
                                @if(!empty($b['tags']))
                                    <div class="flex flex-wrap gap-1 pt-1">
                                        @foreach($b['tags'] as $tag)
                                            <span class="bg-surface-2 border border-border px-1.5 py-0.2 rounded-full text-[9px] text-accent font-mono">#{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>

                <!-- RECENT RESOURCES -->
                <x-ui.card>
                    <x-slot name="header">
                        <div class="flex items-center space-x-1.5">
                            <span class="text-accent text-sm font-mono font-bold">❏</span>
                            <h4 class="font-bold text-xs uppercase tracking-wider text-text-main font-mono">Recent Resources</h4>
                        </div>
                        <a href="/resources" class="text-xxs text-accent hover:underline font-mono">All Resources &rarr;</a>
                    </x-slot>

                    <div class="space-y-3">
                        @if(count($recentResourcesList) === 0)
                            <div class="py-6 text-center text-xs text-text-muted border border-dashed border-border rounded-sm bg-surface">
                                No reference resources added yet.
                            </div>
                        @endif
                        @foreach($recentResourcesList as $r)
                            <div class="p-2 border-b border-border last:border-b-0 text-xs space-y-1">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0 flex-grow">
                                        <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                                            <span class="bg-surface-2 border border-border px-1.5 py-0.2 rounded-sm text-[8px] uppercase font-mono text-text-muted font-bold">{{ $r['type'] }}</span>
                                            @if($r['url'])
                                                <a href="{{ $r['url'] }}" target="_blank" class="font-bold text-text-main hover:text-accent truncate block" title="{{ $r['title'] }}">
                                                    {{ $r['title'] }}
                                                </a>
                                            @else
                                                <span class="font-bold text-text-main truncate" title="{{ $r['title'] }}">{{ $r['title'] }}</span>
                                            @endif
                                        </div>
                                        @if($r['author'])
                                            <span class="text-[10px] text-text-muted">by <span class="font-semibold">{{ $r['author'] }}</span></span>
                                        @endif
                                    </div>
                                    <span class="text-[9px] text-text-subtle font-mono whitespace-nowrap">{{ $r['added'] }}</span>
                                </div>
                                <div class="flex items-center justify-between pt-1 flex-wrap gap-y-1">
                                    <div class="flex items-center space-x-1">
                                        @if(!empty($r['tags']))
                                            @foreach($r['tags'] as $tag)
                                                <span class="bg-surface-2 border border-border px-1.5 py-0.2 rounded-full text-[9px] text-accent font-mono">#{{ $tag }}</span>
                                            @endforeach
                                        @endif
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        @if($r['rating'] > 0)
                                            <div class="flex items-center space-x-0.5 text-warning text-sm leading-none">
                                                @for($i = 0; $i < $r['rating']; $i++)
                                                    <span>★</span>
                                                @endfor
                                            </div>
                                        @endif
                                        <span class="border text-[8px] px-1.5 py-0.2 rounded-full font-mono font-bold uppercase tracking-wider bg-accent/5 text-accent border-accent/20">
                                            {{ str_replace('_', ' ', $r['state']) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>
            </div>

        </div>

        <!-- Right Column: Slipping, Projects, Notes, Network (1/3 width) -->
        <div class="lg:col-span-1 space-y-6">

            <!-- SLIPPING ITEMS -->
            <x-ui.card>
                <x-slot name="header">
                    <div class="flex items-center space-x-1.5">
                        <span class="text-warning text-sm font-mono">⚠</span>
                        <h4 class="font-bold text-xs uppercase tracking-wider text-text-main font-mono">Slipping ({{ count($slipping) }})</h4>
                    </div>
                    <a href="/slipping" class="text-xxs text-warning hover:underline font-mono">Triage &rarr;</a>
                </x-slot>

                <div class="space-y-2">
                    @if(count($slipping) === 0)
                        <div class="py-4 text-center text-xs text-text-muted border border-dashed border-border rounded-sm bg-surface">
                            All items are active. Nothing is slipping!
                        </div>
                    @endif
                    @foreach($slipping as $slip)
                        <div class="p-2.5 border border-border hover:border-warning/30 bg-surface rounded-sm text-xs flex items-center justify-between group transition-all">
                            <div class="min-w-0">
                                <div class="font-bold text-text-main truncate text-[11px]">{{ $slip['title'] }}</div>
                                <div class="text-[10px] text-text-muted mt-0.5">Untouched for <span class="font-mono text-warning font-bold">{{ $slip['days'] }} days</span></div>
                            </div>
                            <div class="flex items-center space-x-1 ml-2 flex-shrink-0">
                                <span class="bg-surface-2 text-[9px] font-mono px-1.5 py-0.2 rounded-xs text-text-subtle uppercase border border-border" style="line-height: 1.25;">{{ $slip['type'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>

            <!-- ACTIVE PROJECTS -->
            <x-ui.card>
                <x-slot name="header">
                    <div class="flex items-center space-x-1.5">
                        <span class="text-accent text-sm font-mono">❏</span>
                        <h4 class="font-bold text-xs uppercase tracking-wider text-text-main font-mono">Active Projects</h4>
                    </div>
                    <a href="/projects" class="text-xxs text-accent hover:underline font-mono">Manage &rarr;</a>
                </x-slot>

                <div class="space-y-2">
                    @if(count($projects) === 0)
                        <div class="py-4 text-center text-xs text-text-muted border border-dashed border-border rounded-sm bg-surface">
                            No active projects.
                        </div>
                    @endif
                    @foreach($projects as $proj)
                        <a href="/projects" class="block p-2.5 border border-border bg-surface-2/10 hover:bg-surface-2/30 rounded-sm text-xs transition-colors">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-text-main flex items-center">
                                    <span class="h-2 w-2 rounded-full mr-2" style="background-color: var(--color-type-{{ strtolower($proj['name']) }}, #EA580C)"></span>
                                    {{ $proj['name'] }}
                                </span>
                                <span class="text-[10px] text-text-subtle font-mono bg-surface px-1.5 py-0.2 border border-border rounded-xs">{{ $proj['tasks_count'] }} tasks</span>
                            </div>
                            <p class="text-[10px] text-text-muted mt-1.5 truncate">{{ $proj['desc'] }}</p>
                        </a>
                    @endforeach
                </div>
            </x-ui.card>

            <!-- RECENT NOTES -->
            <x-ui.card>
                <x-slot name="header">
                    <div class="flex items-center space-x-1.5">
                        <span class="text-accent text-sm font-mono">✍</span>
                        <h4 class="font-bold text-xs uppercase tracking-wider text-text-main font-mono">Recent Notes</h4>
                    </div>
                    <a href="/notes" class="text-xxs text-accent hover:underline font-mono">Open &rarr;</a>
                </x-slot>

                <div class="space-y-2">
                    @if(count($recentNotes) === 0)
                        <div class="py-4 text-center text-xs text-text-muted border border-dashed border-border rounded-sm bg-surface">
                            No recent activity.
                        </div>
                    @endif
                    @foreach($recentNotes as $n)
                        <div class="p-2 border-b border-border last:border-b-0 text-xs">
                            <div class="flex items-center justify-between">
                                <a href="/notes" class="font-bold text-accent hover:underline truncate">{{ $n['title'] }}</a>
                                <span class="text-[9px] text-text-subtle font-mono">{{ $n['updated_at'] }}</span>
                            </div>
                            <p class="text-[10px] text-text-muted mt-1 truncate">{{ $n['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>

            <!-- SPEED TEST DIAGNOSTICS (NETWORK HEALTH) -->
            <x-ui.card>
                <x-slot name="header">
                    <div class="flex items-center space-x-1.5">
                        <span class="text-accent text-sm font-mono">🛜</span>
                        <h4 class="font-bold text-xs uppercase tracking-wider text-text-main font-mono">Network Health</h4>
                    </div>
                    <a href="/speedtest" class="text-xxs text-accent hover:underline font-mono">Full Test &rarr;</a>
                </x-slot>

                <div class="space-y-3">
                    @if($lastSpeedtest)
                        <div class="p-2.5 bg-surface-2/20 border border-border rounded-sm text-xs space-y-2">
                            <div class="flex items-center justify-between text-[10px] font-mono text-text-subtle">
                                <span class="font-bold uppercase" x-text="'{{ $lastSpeedtest->server_name }} Server'"></span>
                                <span>{{ $lastSpeedtest->created_at->diffForHumans() }}</span>
                            </div>
                            <div class="grid grid-cols-3 gap-1 text-[11px] font-mono">
                                <div>
                                    <span class="text-accent font-bold">↓</span> {{ number_format($lastSpeedtest->download_speed, 1) }} <span class="text-[9px] text-text-muted">M</span>
                                </div>
                                <div>
                                    <span class="text-purple-400 font-bold">↑</span> {{ number_format($lastSpeedtest->upload_speed, 1) }} <span class="text-[9px] text-text-muted">M</span>
                                </div>
                                <div>
                                    <span class="text-blue-400 font-bold">~</span> {{ round($lastSpeedtest->latency_ms) }}<span class="text-[9px] text-text-muted">ms</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="py-4 text-center text-xs text-text-muted border border-dashed border-border rounded-sm bg-surface">
                            No speed tests run yet.
                        </div>
                    @endif

                    <a 
                        href="/speedtest" 
                        class="w-full py-2 bg-surface hover:bg-surface-2/30 border border-border hover:border-accent/30 rounded-sm flex items-center justify-center space-x-2 text-xs font-mono font-bold text-text-main transition-colors text-center"
                    >
                        <span>⚡</span>
                        <span>RUN NEW SPEED TEST</span>
                    </a>
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
