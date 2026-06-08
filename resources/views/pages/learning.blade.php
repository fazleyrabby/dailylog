@extends('layouts.app')

@section('title', 'Learning Hub')
@section('header_breadcrumbs', 'DAILYLOG // LEARNING')

@section('content')
<div 
    x-data="{
        selectedPathId: 19,
        paths: [
            {
                id: 19,
                title: 'AWS ECS Container Deployments',
                kind: 'course',
                provider: 'Acme Cloud Academy',
                completedUnits: 4,
                totalUnits: 10,
                status: 'active',
                tags: ['aws', 'devops'],
                lastActive: '34 days ago (Slipping)',
                slipping: true,
                tasks: [
                    { title: 'Setup task definitions for Laravel Octane sandbox', completed: false },
                    { title: 'Deploy staging container task instance', completed: false }
                ],
                notes: [
                    { title: 'AWS ECS Deployment Guide', updated: '5 days ago' }
                ]
            },
            {
                id: 20,
                title: 'PostgreSQL Advanced Indexing',
                kind: 'topic',
                provider: 'PG Mastery',
                completedUnits: 7,
                totalUnits: 10,
                status: 'active',
                tags: ['postgres', 'db'],
                lastActive: 'Today',
                slipping: false,
                tasks: [
                    { title: 'Read PG manual on GIN and GiST indexes', completed: true }
                ],
                notes: [
                    { title: 'PostgreSQL Full Text Search Configuration', updated: 'Yesterday' }
                ]
            },
            {
                id: 21,
                title: 'Laravel Octane Monolith Optimization',
                kind: 'course',
                provider: 'Laracasts',
                completedUnits: 1,
                totalUnits: 8,
                status: 'active',
                tags: ['laravel', 'performance'],
                lastActive: '2 days ago',
                slipping: false,
                tasks: [],
                notes: [
                    { title: 'Laravel Optimization Notes', updated: '2 hours ago' }
                ]
            }
        ],

        get activePath() {
            return this.paths.find(p => p.id === this.selectedPathId) || this.paths[0];
        },

        get activeProgress() {
            let path = this.activePath;
            return Math.round((path.completedUnits / path.totalUnits) * 100);
        },

        completeUnit() {
            let path = this.activePath;
            if (path.completedUnits < path.totalUnits) {
                path.completedUnits++;
                path.lastActive = 'Just now';
                path.slipping = false;
                window.dispatchEvent(new CustomEvent('show-toast', { 
                    detail: { message: `Progress updated: Completed Unit ${path.completedUnits}! Heartbeat bumped.`, action: 'Dismiss' }
                }));
            }
        }
    }"
    class="h-[calc(100vh-100px)] flex overflow-hidden border border-border rounded-sm bg-surface"
>
    <!-- LEFT COLUMN: Learning list (Width 2/5) -->
    <div class="w-2/5 border-r border-border flex flex-col bg-surface-2/10">
        <div class="p-3 border-b border-border bg-surface">
            <h3 class="text-xs font-bold text-text-main uppercase tracking-wider">Active Paths</h3>
        </div>

        <div class="flex-grow overflow-y-auto divide-y divide-border">
            <template x-for="p in paths" :key="p.id">
                <div 
                    @click="selectedPathId = p.id"
                    :class="selectedPathId === p.id ? 'bg-accent-subtle-bg/30 text-text-main' : 'text-text-muted hover:bg-surface-2/30'"
                    class="p-4 cursor-pointer flex flex-col transition-colors"
                >
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-xs uppercase tracking-wide text-text-main" x-text="p.title"></span>
                        <template x-if="p.slipping">
                            <span class="bg-warning/10 text-warning border border-warning/20 px-1 rounded-sm text-[8px] font-mono font-bold uppercase tracking-wider">Slipping</span>
                        </template>
                    </div>
                    
                    <div class="flex items-center justify-between text-xxs text-text-muted mt-2">
                        <span x-text="p.provider"></span>
                        <span class="font-mono" x-text="Math.round((p.completedUnits / p.totalUnits) * 100) + '%'"></span>
                    </div>

                    <!-- Small progress bar -->
                    <div class="w-full bg-surface-2 h-1 rounded-full overflow-hidden mt-1 border border-border">
                        <div class="bg-accent h-full" :style="'width: ' + Math.round((p.completedUnits / p.totalUnits) * 100) + '%'"></div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- RIGHT COLUMN: Detail & Progress Controls (Width 3/5) -->
    <div class="w-3/5 flex flex-col h-full bg-surface">
        <div class="px-4 py-2.5 border-b border-border bg-surface-2/10 flex items-center justify-between">
            <span class="text-xs font-semibold text-text-main">Path Dashboard</span>
            <span class="text-xxs text-text-subtle font-mono" x-text="'Last activity: ' + activePath.lastActive"></span>
        </div>

        <div class="flex-grow p-6 overflow-y-auto max-w-2xl mx-auto w-full space-y-6">
            <!-- Course Title -->
            <div class="border-b border-border pb-4">
                <div class="flex items-center space-x-2">
                    <span class="bg-surface-2 border border-border px-1.5 py-0.5 rounded-sm text-[10px] font-mono text-text-muted uppercase" x-text="activePath.kind"></span>
                    <h2 class="text-base font-bold text-text-main" x-text="activePath.title"></h2>
                </div>
                <p class="text-xs text-text-muted mt-1" x-text="'Provider: ' + activePath.provider"></p>
            </div>

            <!-- Interactive Progress Widget -->
            <div class="p-4 border border-border rounded-sm bg-surface-2/10 flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-bold text-text-subtle uppercase tracking-wider">Progress Monitor</h3>
                    <div class="flex items-baseline space-x-2 mt-2">
                        <span class="text-2xl font-bold font-mono text-text-main" x-text="activeProgress + '%'"></span>
                        <span class="text-xxs text-text-muted" x-text="'(' + activePath.completedUnits + ' of ' + activePath.totalUnits + ' units completed)'"></span>
                    </div>
                    
                    <!-- Progress bar -->
                    <div class="w-48 bg-surface-2 h-1.5 rounded-full overflow-hidden mt-2 border border-border">
                        <div class="bg-accent h-full transition-all duration-300" :style="'width: ' + activeProgress + '%'"></div>
                    </div>
                </div>
                
                <x-ui.button variant="primary" @click="completeUnit()" ::disabled="activePath.completedUnits === activePath.totalUnits" class="font-bold cursor-pointer select-none">
                    Complete Unit
                </x-ui.button>
            </div>

            <!-- Connected Hub lists -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Related Tasks -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold text-text-subtle uppercase tracking-wider border-b border-border/60 pb-1">Connected Study Tasks</h4>
                    <div class="space-y-1.5">
                        <template x-if="activePath.tasks.length === 0">
                            <p class="text-xxs text-text-subtle italic">No related study tasks.</p>
                        </template>
                        <template x-for="t in activePath.tasks">
                            <div class="flex items-center space-x-2 text-xs py-1">
                                <span class="text-accent">•</span>
                                <span class="text-text-main" x-text="t.title"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Related Notes -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold text-text-subtle uppercase tracking-wider border-b border-border/60 pb-1">Linked Notes</h4>
                    <div class="space-y-2">
                        <template x-if="activePath.notes.length === 0">
                            <p class="text-xxs text-text-subtle italic">No linked notes.</p>
                        </template>
                        <template x-for="n in activePath.notes">
                            <a href="/notes" class="block p-2 bg-surface-2/30 border border-border/60 rounded-sm text-xs flex justify-between items-center hover:bg-surface-2">
                                <span class="font-medium text-text-main truncate" x-text="n.title"></span>
                                <span class="text-[9px] text-text-subtle font-mono flex-shrink-0" x-text="n.updated"></span>
                            </a>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
