@extends('layouts.app')

@section('title', 'Learning Hub')
@section('header_breadcrumbs', 'DAILYLOG // LEARNING')

@section('content_padding', 'p-0')

@section('content')
<div
    x-data="Object.assign(learningComponent({{ json_encode($learningPaths) }}), panelResizer({key:'learning', initial:380, min:280, max:600}))"
    x-init="initPanelResizer()"
    class="h-[calc(100vh-48px)] flex flex-col md:flex-row overflow-hidden bg-surface"
    :class="resizing ? 'cursor-col-resize' : ''"
>
    <!-- BACKDROP FOR MOBILE SIDEBAR -->
    <div 
        x-show="isMobile && showLeftPanel" 
        x-transition.opacity 
        @click="showLeftPanel = false" 
        class="fixed inset-0 bg-black/50 z-20"
        style="display: none;"
    ></div>

    <!-- LEFT COLUMN: Learning list -->
    <div 
        :class="[
            isMobile ? 'fixed inset-y-0 left-0 z-30 w-72 bg-surface shadow-2xl transform transition-transform duration-200 ease-in-out' : 'relative md:translate-x-0 md:shadow-none md:flex-shrink-0 border-r border-border md:max-h-full transition-all duration-200 ease-in-out',
            isMobile && (showLeftPanel ? 'translate-x-0' : '-translate-x-full')
        ]"
        :style="isMobile ? '' : 'width:' + (showLeftPanel ? panelWidth + 'px' : '0px')" 
        class="flex flex-col bg-surface md:bg-surface-2/10 h-full overflow-hidden"
    >
        <div class="p-3 border-b border-border bg-surface">
            <h3 class="text-[10px] font-bold text-text-muted uppercase tracking-widest">Active Paths</h3>
        </div>
 
        <div class="flex-grow overflow-y-auto divide-y divide-border">
            <template x-if="paths.length === 0">
                <div class="p-4 text-xs text-text-muted italic text-center">
                    No active learning paths found.
                </div>
            </template>
            <template x-for="p in paths" :key="p.id">
                <div 
                    @click="selectedPathId = p.id; if (isMobile) { showLeftPanel = false; }"
                    :class="selectedPathId === p.id ? 'bg-accent-subtle-bg/30 text-text-main border-l-2 border-accent' : 'text-text-muted hover:bg-surface-2/30 border-l-2 border-transparent'"
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
 
    <!-- DRAG HANDLE RESIZER -->
    <div
        x-show="showLeftPanel"
        @mousedown="startPanelResize($event)"
        class="hidden md:flex w-2.5 flex-shrink-0 h-full z-10 cursor-col-resize items-center justify-center group relative"
    >
        <div class="w-[1px] h-full bg-border group-hover:bg-accent transition-colors duration-150"></div>
        <div class="absolute top-1/2 -translate-y-1/2 w-1 h-7 rounded-full bg-border/60 group-hover:bg-accent transition-colors duration-150 shadow-xs"></div>
    </div>

    <!-- RIGHT COLUMN: Detail & Progress Controls -->
    <div class="flex-grow flex flex-col h-full bg-surface overflow-hidden min-w-0">
        <div class="px-4 py-2.5 border-b border-border bg-surface-2/10 flex items-center justify-between font-mono">
            <div class="flex items-center space-x-2">
                <button 
                    @click="toggleLeftPanel()" 
                    class="mr-1.5 p-1 bg-surface hover:bg-surface-2 border border-border rounded-xs cursor-pointer select-none text-[10px] font-mono leading-none flex items-center space-x-1 text-text-muted hover:text-text-main"
                    title="Toggle Learning Panel"
                >
                    <span x-text="showLeftPanel ? '◂' : '▸'"></span>
                    <span x-text="showLeftPanel ? 'Hide Paths' : 'Paths'"></span>
                </button>
                <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-text-subtle">Path Dashboard</span>
            </div>
            <span class="text-[10px] text-text-subtle font-mono uppercase tracking-wider" x-text="'Last activity: ' + activePath.lastActive"></span>
        </div>
 
        <div class="flex-grow p-6 overflow-y-auto max-w-2xl mx-auto w-full space-y-6">
            <template x-if="paths.length === 0">
                <x-ui.empty-state 
                    title="No Learning Path Selected"
                    description="Capture a new learning topic or course via the Cmd-K command palette to begin."
                />
            </template>
            
            <template x-if="paths.length > 0">
                <div class="space-y-6">
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
                            <h3 class="text-[9px] font-bold text-text-subtle uppercase tracking-widest">Progress Monitor</h3>
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
                            <h4 class="text-[9px] font-bold text-text-subtle uppercase tracking-widest border-b border-border/60 pb-1">Connected Study Tasks</h4>
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
                            <h4 class="text-[9px] font-bold text-text-subtle uppercase tracking-widest border-b border-border/60 pb-1">Linked Notes</h4>
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
            </template>
        </div>
    </div>
</div>

<script>
window.learningComponent = function(initialPaths) {
    return {
        selectedPathId: initialPaths.length > 0 ? initialPaths[0].id : null,
        paths: initialPaths,

        get activePath() {
            return this.paths.find(p => p.id === this.selectedPathId) || {
                id: null,
                title: 'No active path',
                kind: 'topic',
                provider: '',
                completedUnits: 0,
                totalUnits: 10,
                status: 'active',
                tags: [],
                lastActive: 'Never',
                slipping: false,
                tasks: [],
                notes: []
            };
        },

        get activeProgress() {
            let path = this.activePath;
            if (!path.id || path.totalUnits === 0) return 0;
            return Math.round((path.completedUnits / path.totalUnits) * 100);
        },

        completeUnit() {
            let path = this.activePath;
            if (path.id && path.completedUnits < path.totalUnits) {
                fetch(`/learning/${path.id}/complete-unit`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.path) {
                        let idx = this.paths.findIndex(p => p.id === data.path.id);
                        if (idx !== -1) {
                            this.paths[idx] = data.path;
                        }
                        window.dispatchEvent(new CustomEvent('show-toast', { 
                            detail: { message: `Progress updated: Completed Unit ${data.path.completedUnits}! Heartbeat bumped.` }
                        }));
                    }
                });
            }
        }
    };
};
</script>
@endsection
