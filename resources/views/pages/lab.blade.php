@extends('layouts.app')

@section('title', 'Lab Whiteboard')
@section('header_breadcrumbs', 'DAILYLOG // LAB')

@section('content')
<div 
    x-data="labCanvasComponent({
        boardId: {{ $activeBoard->id }},
        initialItems: {{ json_encode($items) }},
        recentEntries: {{ json_encode($recentEntries) }}
    })"
    class="flex bg-surface-2 overflow-hidden relative select-none -m-3 md:-m-6"
    style="height: calc(100vh - 4rem);"
    @mouseup="isPanning = false"
    @mouseleave="isPanning = false"
>
    <!-- Left Sidebar: Lab Boards (collapsible) -->
    <div x-show="showBoardsSidebar" x-transition:enter="transition-all duration-200" x-transition:enter-start="-ml-64 opacity-0" x-transition:enter-end="ml-0 opacity-100" x-transition:leave="transition-all duration-200" x-transition:leave-start="ml-0 opacity-100" x-transition:leave-end="-ml-64 opacity-0" class="w-64 border-r border-border bg-surface flex flex-col z-20 flex-shrink-0">
        <div class="p-4 border-b border-border flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-text-subtle font-mono">Boards</span>
            <div class="flex items-center space-x-2">
                <button 
                    @click="showCreateModal = true"
                    class="hover:text-accent text-text-muted text-sm font-bold cursor-pointer transition-colors"
                    title="New Board"
                >
                    ＋
                </button>
                <button 
                    @click="showBoardsSidebar = false; localStorage.setItem('lab-boards-visible', 'false')"
                    class="hover:text-accent text-text-muted text-xs cursor-pointer transition-colors font-mono"
                    title="Hide Boards Panel"
                >
                    ◂
                </button>
            </div>
        </div>
        <div class="flex-grow overflow-y-auto p-2 space-y-1">
            @foreach($boards as $b)
                <div class="group relative rounded-sm transition-colors {{ $b->id === $activeBoard->id ? 'bg-accent-subtle-bg/40 font-semibold' : 'hover:bg-surface-2/50' }}">
                    <a
                        href="{{ route('lab.show', $b) }}"
                        class="block px-3 py-2 pr-14 text-xs {{ $b->id === $activeBoard->id ? 'text-accent' : 'text-text-muted' }}"
                    >
                        <div class="truncate font-mono">{{ $b->title }}</div>
                        <div class="text-[10px] text-text-subtle mt-0.5" style="font-size: 9px;">
                            Updated: {{ $b->updated_at->diffForHumans() }}
                        </div>
                    </a>
                    <div class="absolute right-1.5 top-1.5 flex items-center space-x-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button
                            type="button"
                            @click.stop.prevent="renameBoard({{ $b->id }}, @js($b->title))"
                            class="px-1 py-0.5 text-[10px] text-text-subtle hover:text-accent hover:bg-accent/10 rounded-xs cursor-pointer"
                            title="Rename board"
                        >✎</button>
                        <form
                            action="{{ route('lab.destroy', $b) }}"
                            method="POST"
                            class="inline"
                            @submit="return confirm('Delete board “{{ addslashes($b->title) }}”? All sticky notes and links on this board will be permanently removed. This cannot be undone.')"
                        >
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                @click.stop
                                class="px-1 py-0.5 text-[10px] text-text-subtle hover:text-danger hover:bg-danger/10 rounded-xs cursor-pointer"
                                title="Delete board"
                            >✕</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Floating Show-Boards Toggle (visible when sidebar hidden) -->
    <button
        x-show="!showBoardsSidebar"
        x-transition
        @click="showBoardsSidebar = true; localStorage.setItem('lab-boards-visible', 'true')"
        class="absolute left-3 top-3 z-30 bg-surface/90 border border-border backdrop-blur-md rounded-sm px-2.5 py-1.5 text-[10px] font-mono font-bold uppercase tracking-wider text-text-muted hover:text-accent hover:border-accent cursor-pointer transition-colors shadow-md flex items-center space-x-1.5"
        title="Show Boards Panel"
    >
        <span>▸</span>
        <span>Boards</span>
    </button>

    <!-- Main Viewport Canvas -->
    <div
        class="flex-grow h-full overflow-hidden relative cursor-grab active:cursor-grabbing"
        :class="isPanning ? 'cursor-grabbing' : ''"
        @mousedown="startPan($event)"
        @mousemove="pan($event)"
        @wheel.prevent="zoom($event)"
    >
        <!-- Right Drawer: Linkable Entries (inside canvas viewport) -->
        <div 
            x-show="showRightDrawer"
            x-cloak
            x-transition
            class="absolute right-0 top-0 bottom-0 w-80 border-l border-border bg-surface flex flex-col z-40 shadow-lg"
        >
            <div class="p-4 border-b border-border flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-text-subtle font-mono">Link Entries</span>
                <button @click="showRightDrawer = false" class="text-text-muted hover:text-text-main text-xs cursor-pointer">✕</button>
            </div>
            <div class="p-3 border-b border-border">
                <input 
                    type="text" 
                    x-model="searchQuery" 
                    placeholder="Filter entries..." 
                    class="w-full text-xs bg-surface-2 border border-border rounded-sm px-2.5 py-1.5 focus:outline-none focus:border-accent text-text-main font-mono"
                />
            </div>
            <div class="flex-grow overflow-y-auto p-3 space-y-2">
                <template x-for="entry in filteredEntries" :key="entry.id">
                    <div 
                        class="p-2 border border-border rounded-xs bg-surface-2/50 hover:border-accent cursor-pointer transition-all"
                        @click="addReferenceItem(entry)"
                    >
                        <div class="flex items-center space-x-1.5">
                            <span class="text-[9px] uppercase font-bold font-mono px-1 rounded-xs"
                                  :class="{
                                      'bg-type-task/10 text-type-task border border-type-task/20': entry.type === 'task',
                                      'bg-type-note/10 text-type-note border border-type-note/20': entry.type === 'note',
                                      'bg-type-bookmark/10 text-type-bookmark border border-type-bookmark/20': entry.type === 'bookmark',
                                      'bg-type-learning/10 text-type-learning border border-type-learning/20': entry.type === 'learning',
                                      'bg-type-idea/10 text-type-idea border border-type-idea/20': entry.type === 'idea',
                                      'bg-type-resource/10 text-type-resource border border-type-resource/20': entry.type === 'resource'
                                  }"
                                  x-text="entry.type"></span>
                            <span class="text-xs font-bold text-text-main truncate flex-grow" x-text="entry.title || '(Untitled)'"></span>
                        </div>
                        <p class="text-[10px] text-text-muted mt-1 truncate" x-text="entry.body || ''"></p>
                    </div>
                </template>
            </div>
        </div>
        <!-- Canvas Grid Background Container -->
        <div 
            class="absolute inset-0 origin-top-left canvas-grid"
            :style="`transform: translate(${panX}px, ${panY}px) scale(${scale});`"
            style="width: 5000px; height: 5000px; background-size: 30px 30px; background-image: linear-gradient(to right, color-mix(in srgb, var(--color-border-app) 40%, transparent) 1px, transparent 1px), linear-gradient(to bottom, color-mix(in srgb, var(--color-border-app) 40%, transparent) 1px, transparent 1px);"
        >
            <!-- Render Canvas Items -->
            <template x-for="(item, index) in items" :key="item.id || index">
                <div 
                    class="absolute select-none rounded-xs border transition-shadow bg-surface interact-item"
                    :class="{
                        'border-accent': item.type === 'sticky' && item.color === 'purple',
                        'border-warning': item.type === 'sticky' && item.color === 'yellow',
                        'border-success': item.type === 'sticky' && item.color === 'green',
                        'border-border hover:border-accent': !item.color,
                        'shadow-lg': activeItemId === item.id
                    }"
                    :style="`left: ${item.x}px; top: ${item.y}px; width: ${item.width}px; height: ${item.height}px;`"
                    :data-id="item.id || index"
                    @mousedown.stop
                >
                    <!-- Header Actions -->
                    <div class="h-7 border-b border-border/60 bg-surface-2/65 px-2 flex items-center justify-between text-[9px] font-mono text-text-subtle handle cursor-move overflow-hidden whitespace-nowrap">
                        <span class="uppercase tracking-wider font-bold flex-shrink-0" x-text="item.type"></span>
                        <div class="flex items-center space-x-1 flex-shrink-0 ml-1">
                            <!-- Sticky Color Picker -->
                            <template x-if="item.type === 'sticky'">
                                <div class="flex items-center space-x-1 mr-1 border-r border-border/40 pr-1">
                                    <span @click.stop="setItemColor(item, 'purple')" 
                                          :class="item.color === 'purple' ? 'ring-1 ring-white ring-offset-1 ring-offset-surface-2' : ''"
                                          class="w-2.5 h-2.5 rounded-full bg-accent cursor-pointer border border-white/20 hover:scale-110 transition-transform flex-shrink-0"></span>
                                    <span @click.stop="setItemColor(item, 'yellow')" 
                                          :class="item.color === 'yellow' ? 'ring-1 ring-white ring-offset-1 ring-offset-surface-2' : ''"
                                          class="w-2.5 h-2.5 rounded-full bg-warning cursor-pointer border border-white/20 hover:scale-110 transition-transform flex-shrink-0"></span>
                                    <span @click.stop="setItemColor(item, 'green')" 
                                          :class="item.color === 'green' ? 'ring-1 ring-white ring-offset-1 ring-offset-surface-2' : ''"
                                          class="w-2.5 h-2.5 rounded-full bg-success cursor-pointer border border-white/20 hover:scale-110 transition-transform flex-shrink-0"></span>
                                </div>
                            </template>
                            <!-- Promote Button -->
                            <template x-if="item.type === 'sticky'">
                                <button @click.stop="promptGraduate(item)" 
                                        class="px-1 py-0.5 text-[8px] font-bold uppercase text-text-muted hover:text-accent hover:bg-accent/10 rounded-xs cursor-pointer transition-colors flex-shrink-0"
                                        title="Promote to Note, Task, or Idea">
                                    ↗
                                </button>
                            </template>
                            <!-- Delete Button -->
                            <button @click.stop="deleteItem(item)" 
                                    class="px-1 py-0.5 text-[8px] font-bold uppercase text-text-muted hover:text-danger hover:bg-danger/10 rounded-xs cursor-pointer transition-colors flex-shrink-0"
                                    title="Delete this item">
                                ✕
                            </button>
                        </div>
                    </div>

                    <!-- Item Body content -->
                    <div class="p-3 h-[calc(100%-1.5rem)] overflow-hidden select-text relative">
                        <!-- Sticky / Text Editor -->
                        <template x-if="item.type === 'sticky' || item.type === 'text'">
                            <div class="w-full h-full flex flex-col">
                                <input 
                                    type="text" 
                                    x-model="item.title" 
                                    placeholder="Title..." 
                                    @input="queueSave()"
                                    class="w-full bg-transparent border-0 font-bold text-xs text-text-main focus:ring-0 focus:outline-none mb-1 font-sans"
                                />
                                <textarea 
                                    x-model="item.content" 
                                    placeholder="Take thoughts..." 
                                    @input="queueSave()"
                                    class="w-full flex-grow bg-transparent border-0 text-[11px] text-text-muted leading-relaxed focus:ring-0 focus:outline-none resize-none font-sans"
                                ></textarea>
                            </div>
                        </template>

                        <!-- Reference Card view -->
                        <template x-if="item.type === 'reference' && item.target">
                            <div class="w-full h-full flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center space-x-1 mb-1">
                                        <span class="text-[9px] uppercase font-mono px-1 rounded-xs font-bold"
                                              :class="{
                                                  'bg-type-task/10 text-type-task border border-type-task/20': item.target.type === 'task',
                                                  'bg-type-note/10 text-type-note border border-type-note/20': item.target.type === 'note',
                                                  'bg-type-bookmark/10 text-type-bookmark border border-type-bookmark/20': item.target.type === 'bookmark',
                                                  'bg-type-learning/10 text-type-learning border border-type-learning/20': item.target.type === 'learning',
                                                  'bg-type-idea/10 text-type-idea border border-type-idea/20': item.target.type === 'idea',
                                                  'bg-type-resource/10 text-type-resource border border-type-resource/20': item.target.type === 'resource'
                                              }"
                                              x-text="item.target.type"></span>
                                        <span class="text-[9px] text-text-subtle font-mono" x-text="'#' + item.target.id"></span>
                                    </div>
                                    <h5 class="text-xs font-bold text-accent truncate" x-text="item.target.title || '(Untitled)'"></h5>
                                    <p class="text-[10px] text-text-muted leading-tight mt-1 line-clamp-3" x-text="item.target.body || ''"></p>
                                </div>
                                <a :href="getTargetUrl(item.target)" target="_blank" class="text-[9px] text-text-subtle hover:text-accent font-mono block mt-2">Open Details →</a>
                            </div>
                        </template>
                    </div>

                    <!-- Drag Resize Handle -->
                    <div class="absolute right-0 bottom-0 w-3.5 h-3.5 cursor-se-resize flex items-end justify-end p-0.5 select-none text-[8px] text-text-subtle/40 resize-handle">
                        ◢
                    </div>
                </div>
            </template>
        </div>

        <!-- Interactive Canvas Minimap -->
        <div 
            x-show="items.length > 0 && !isMobile"
            class="absolute bottom-6 right-6 w-[162px] h-[122px] bg-surface/85 border border-border backdrop-blur-md rounded shadow-xl z-30 select-none overflow-hidden hidden md:block"
        >
            <div 
                @click="teleportToMinimap($event)"
                class="w-full h-full bg-surface-2/30 cursor-pointer relative"
            >
                <!-- Draw items -->
                <template x-for="mi in minimapData.items" :key="mi.id">
                    <div 
                        class="absolute rounded-[2px]"
                        :style="{
                            left: mi.x + 'px',
                            top: mi.y + 'px',
                            width: mi.w + 'px',
                            height: mi.h + 'px',
                            backgroundColor: (mi.color === 'purple' || mi.type === 'reference') ? 'color-mix(in srgb, var(--color-accent-app) 40%, transparent)' :
                                             mi.color === 'yellow' ? 'color-mix(in srgb, var(--color-warning-app) 40%, transparent)' :
                                             mi.color === 'green' ? 'color-mix(in srgb, var(--color-success-app) 40%, transparent)' :
                                             'color-mix(in srgb, var(--color-text-subtle-app) 15%, transparent)',
                            border: '0.75px solid ' + (
                                (mi.color === 'purple' || mi.type === 'reference') ? 'var(--color-accent-app)' :
                                mi.color === 'yellow' ? 'var(--color-warning-app)' :
                                mi.color === 'green' ? 'var(--color-success-app)' :
                                'var(--color-border-strong-app)'
                            )
                        }"
                    ></div>
                </template>
                <!-- Draw viewport -->
                <template x-if="minimapData.viewport">
                    <div 
                        class="absolute border border-dashed"
                        :style="{
                            left: (minimapData.viewport?.x || 0) + 'px',
                            top: (minimapData.viewport?.y || 0) + 'px',
                            width: (minimapData.viewport?.w || 0) + 'px',
                            height: (minimapData.viewport?.h || 0) + 'px',
                            borderColor: 'var(--color-accent-app)',
                            borderWidth: '1.25px'
                        }"
                    ></div>
                </template>
            </div>
        </div>

        <!-- Floating Canvas Toolbar (inside canvas viewport for correct centering) -->
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 bg-surface/90 border border-border backdrop-blur-md rounded-full shadow-xl px-5 py-2.5 flex items-center space-x-4 z-30 text-xs">
            <button 
                @click="addStickyItem()"
                class="flex items-center space-x-1.5 text-text-muted hover:text-text-main font-semibold cursor-pointer"
            >
                <span>📝</span>
                <span class="font-mono">Sticky</span>
            </button>
            <div class="h-4 w-px bg-border"></div>
            <button 
                @click="addTextItem()"
                class="flex items-center space-x-1.5 text-text-muted hover:text-text-main font-semibold cursor-pointer"
            >
                <span>✍️</span>
                <span class="font-mono">Text</span>
            </button>
            <div class="h-4 w-px bg-border"></div>
            <button 
                @click="showRightDrawer = !showRightDrawer"
                class="flex items-center space-x-1.5 text-text-muted hover:text-text-main font-semibold cursor-pointer"
            >
                <span>🔗</span>
                <span class="font-mono">Link Entry</span>
            </button>
            <div class="h-4 w-px bg-border"></div>
            <button 
                @click="resetView()"
                class="flex items-center space-x-1 text-text-muted hover:text-text-main cursor-pointer"
                title="Reset Camera Viewport"
            >
                <span>🏠</span>
            </button>
            <div x-show="isSaving" class="text-[9px] font-mono text-accent animate-pulse px-1">Saving...</div>
        </div>
    </div>

    <!-- Modal: Create New Board -->
    <div 
        x-show="showCreateModal"
        class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 backdrop-blur-xs"
        @keydown.escape.window="showCreateModal = false"
    >
        <form 
            action="{{ route('lab.store') }}" 
            method="POST"
            class="bg-surface border border-border rounded-sm p-5 w-full max-w-sm space-y-4"
        >
            @csrf
            <div class="flex items-center justify-between border-b border-border pb-2.5">
                <h4 class="text-xs font-bold uppercase tracking-wider text-text-subtle font-mono">Create New Board</h4>
                <button type="button" @click="showCreateModal = false" class="text-text-muted hover:text-text-main text-xs">✕</button>
            </div>
            <div>
                <label class="text-[10px] font-bold uppercase tracking-wider text-text-subtle block mb-1.5 font-mono">Board Title</label>
                <input 
                    type="text" 
                    name="title" 
                    required 
                    placeholder="e.g. Brain Dump, Milestone Roadmap..."
                    class="w-full text-xs bg-surface-2 border border-border rounded-sm px-3 py-2 focus:outline-none focus:border-accent text-text-main font-mono"
                />
            </div>
            <div class="flex justify-end space-x-2 pt-2">
                <x-ui.button type="button" variant="secondary" size="sm" @click="showCreateModal = false">
                    Cancel
                </x-ui.button>
                <x-ui.button type="submit" variant="primary" size="sm">
                    Create Board
                </x-ui.button>
            </div>
        </form>
    </div>

    <!-- Modal: Graduate Sticky -->
    <div 
        x-show="showGraduateModal"
        class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 backdrop-blur-xs"
        @keydown.escape.window="showGraduateModal = false"
    >
        <div class="bg-surface border border-border rounded-sm p-5 w-full max-w-xs space-y-4">
            <div class="flex items-center justify-between border-b border-border pb-2.5">
                <h4 class="text-xs font-bold uppercase tracking-wider text-text-subtle font-mono">Graduate Sticky Note</h4>
                <button type="button" @click="showGraduateModal = false" class="text-text-muted hover:text-text-main text-xs">✕</button>
            </div>
            <p class="text-xs text-text-muted font-sans leading-relaxed">
                Graduate this sticky note into a real DailyLOG entry. It will convert to a permanent database card and reference link.
            </p>
            <div class="space-y-1.5 pt-2">
                <button 
                    @click="graduateItem('note')"
                    class="w-full text-left text-xs bg-surface-2 hover:bg-accent hover:text-white border border-border px-3 py-2 rounded-xs font-mono transition-colors cursor-pointer"
                >
                    📓 Convert to Note
                </button>
                <button 
                    @click="graduateItem('task')"
                    class="w-full text-left text-xs bg-surface-2 hover:bg-accent hover:text-white border border-border px-3 py-2 rounded-xs font-mono transition-colors cursor-pointer"
                >
                    ✅ Convert to Task
                </button>
                <button 
                    @click="graduateItem('idea')"
                    class="w-full text-left text-xs bg-surface-2 hover:bg-accent hover:text-white border border-border px-3 py-2 rounded-xs font-mono transition-colors cursor-pointer"
                >
                    💡 Convert to Idea
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Load Interact.js for dragging and resizing -->
<script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>

<script>
window.labCanvasComponent = function(config) {
    return {
        boardId: config.boardId,
        items: config.initialItems,
        recentEntries: config.recentEntries,
        
        // Navigation Viewport state
        panX: 100,
        panY: 100,
        scale: 1.0,
        isPanning: false,
        startX: 0,
        startY: 0,

        // Toolbar/UI states
        showBoardsSidebar: localStorage.getItem('lab-boards-visible') !== 'false',
        showRightDrawer: false,
        showCreateModal: false,
        showGraduateModal: false,
        searchQuery: '',
        activeItemId: null,
        graduatingItem: null,
        isSaving: false,
        saveTimeout: null,

        init() {
            this.$nextTick(() => {
                this.initDragAndResize();
            });
        },

        get filteredEntries() {
            if (!this.searchQuery) return this.recentEntries;
            const query = this.searchQuery.toLowerCase();
            return this.recentEntries.filter(e => 
                (e.title && e.title.toLowerCase().includes(query)) ||
                (e.body && e.body.toLowerCase().includes(query))
            );
        },

        get minimapData() {
            if (this.items.length === 0) return { items: [], viewport: null };

            let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
            this.items.forEach(i => {
                const x = parseFloat(i.x) || 0;
                const y = parseFloat(i.y) || 0;
                const w = parseFloat(i.width) || 200;
                const h = parseFloat(i.height) || 180;
                if (x < minX) minX = x;
                if (y < minY) minY = y;
                if (x + w > maxX) maxX = x + w;
                if (y + h > maxY) maxY = y + h;
            });

            const vpLeft = -parseFloat(this.panX) / this.scale;
            const vpTop = -parseFloat(this.panY) / this.scale;
            const vpWidth = window.innerWidth / this.scale;
            const vpHeight = window.innerHeight / this.scale;

            if (vpLeft < minX) minX = vpLeft;
            if (vpTop < minY) minY = vpTop;
            if (vpLeft + vpWidth > maxX) maxX = vpLeft + vpWidth;
            if (vpTop + vpHeight > maxY) maxY = vpTop + vpHeight;

            minX -= 200;
            minY -= 200;
            maxX += 200;
            maxY += 200;

            const boundsWidth = maxX - minX;
            const boundsHeight = maxY - minY;

            const mapW = 160;
            const mapH = 120;

            const scaleX = mapW / boundsWidth;
            const scaleY = mapH / boundsHeight;
            const mapScale = Math.min(scaleX, scaleY) || 1;

            const offsetX = (mapW - boundsWidth * mapScale) / 2;
            const offsetY = (mapH - boundsHeight * mapScale) / 2;

            const transformX = (val) => (parseFloat(val) - minX) * mapScale + offsetX;
            const transformY = (val) => (parseFloat(val) - minY) * mapScale + offsetY;

            const mappedItems = this.items.map(i => ({
                id: i.id || Math.random(),
                x: transformX(i.x || 0),
                y: transformY(i.y || 0),
                w: (parseFloat(i.width) || 200) * mapScale,
                h: (parseFloat(i.height) || 180) * mapScale,
                color: i.color || 'gray',
                type: i.type
            }));

            const mappedViewport = {
                x: transformX(vpLeft),
                y: transformY(vpTop),
                w: vpWidth * mapScale,
                h: vpHeight * mapScale
            };

            return {
                items: mappedItems,
                viewport: mappedViewport,
                bounds: { minX, minY, mapScale, offsetX, offsetY }
            };
        },

        teleportToMinimap(event) {
            const rect = event.currentTarget.getBoundingClientRect();
            const clickX = event.clientX - rect.left;
            const clickY = event.clientY - rect.top;
            
            const data = this.minimapData;
            if (!data || !data.bounds) return;
            
            const { minX, minY, mapScale, offsetX, offsetY } = data.bounds;
            
            const canvasX = (clickX - offsetX) / mapScale + minX;
            const canvasY = (clickY - offsetY) / mapScale + minY;
            
            this.panX = window.innerWidth / 2 - this.scale * canvasX;
            this.panY = window.innerHeight / 2 - this.scale * canvasY;
        },

        // Panning/Zooming
        startPan(e) {
            // Only pan if we click on the grid background, not inside cards
            if (e.target.closest('.interact-item') || e.target.closest('button') || e.target.closest('input') || e.target.closest('textarea')) return;
            this.isPanning = true;
            this.startX = e.clientX - this.panX;
            this.startY = e.clientY - this.panY;
        },

        pan(e) {
            if (!this.isPanning) return;
            this.panX = e.clientX - this.startX;
            this.panY = e.clientY - this.startY;
        },

        zoom(e) {
            const zoomFactor = 0.08;
            let newScale = this.scale + (e.deltaY < 0 ? zoomFactor : -zoomFactor);
            // clamp scale between 0.2 and 2.5
            this.scale = Math.min(Math.max(newScale, 0.2), 2.5);
        },

        resetView() {
            this.panX = 100;
            this.panY = 100;
            this.scale = 1.0;
        },

        // CRUD functions
        addStickyItem() {
            const newItem = {
                type: 'sticky',
                title: 'New Sticky',
                content: '',
                x: 150 - this.panX,
                y: 150 - this.panY,
                width: 200,
                height: 180,
                color: 'purple',
                target_entry_id: null
            };
            this.items.push(newItem);
            this.queueSave();
        },

        addTextItem() {
            const newItem = {
                type: 'text',
                title: 'Label',
                content: '',
                x: 150 - this.panX,
                y: 150 - this.panY,
                width: 180,
                height: 100,
                color: null,
                target_entry_id: null
            };
            this.items.push(newItem);
            this.queueSave();
        },

        addReferenceItem(entry) {
            const newItem = {
                type: 'reference',
                title: null,
                content: null,
                x: 200 - this.panX,
                y: 200 - this.panY,
                width: 220,
                height: 140,
                color: null,
                target_entry_id: entry.id,
                target: entry
            };
            this.items.push(newItem);
            this.queueSave();
            this.showRightDrawer = false;
        },

        setItemColor(item, color) {
            item.color = color;
            this.queueSave();
        },

        deleteItem(item) {
            this.items = this.items.filter(i => i !== item);
            this.queueSave();
        },

        getTargetUrl(target) {
            if (!target) return '#';
            if (target.type === 'note') return '/notes?note=' + target.id;
            if (target.type === 'task') return '/tasks?task=' + target.id;
            if (target.type === 'project') return '/projects?project=' + target.id;
            if (target.type === 'learning') return '/learning?learning=' + target.id;
            if (target.type === 'bookmark') return '/bookmarks?bookmark=' + target.id;
            if (target.type === 'resource') return '/resources?resource=' + target.id;
            return '/' + target.type + 's/' + target.id;
        },

        renameBoard(boardId, currentTitle) {
            const next = window.prompt('Rename board:', currentTitle);
            if (next === null) return;
            const trimmed = next.trim();
            if (!trimmed || trimmed === currentTitle) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/lab/${boardId}`;

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            form.appendChild(csrf);

            const method = document.createElement('input');
            method.type = 'hidden';
            method.name = '_method';
            method.value = 'PATCH';
            form.appendChild(method);

            const title = document.createElement('input');
            title.type = 'hidden';
            title.name = 'title';
            title.value = trimmed;
            form.appendChild(title);

            document.body.appendChild(form);
            form.submit();
        },

        promptGraduate(item) {
            this.graduatingItem = item;
            this.showGraduateModal = true;
        },

        graduateItem(toType) {
            if (!this.graduatingItem) return;
            const item = this.graduatingItem;
            this.showGraduateModal = false;

            fetch(`/lab/items/${item.id}/graduate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ to_type: toType })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Replace the sticky element on canvas with graduated reference card
                    const index = this.items.findIndex(i => i.id === item.id);
                    if (index !== -1) {
                        this.items[index] = data.item;
                    }
                    window.dispatchEvent(new CustomEvent('show-toast', { 
                        detail: { message: `Sticky note graduated to ${toType}!` }
                    }));
                }
            });
        },

        // Auto Save debouncer
        queueSave() {
            this.isSaving = true;
            if (this.saveTimeout) clearTimeout(this.saveTimeout);
            this.saveTimeout = setTimeout(() => {
                this.saveBoardItems();
            }, 1000);
        },

        saveBoardItems() {
            fetch(`/lab/${this.boardId}/items`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ items: this.items })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.items = data.items;
                    this.isSaving = false;
                    this.initDragAndResize();
                }
            })
            .catch(() => {
                this.isSaving = false;
            });
        },

        // Drag/Resize handling
        initDragAndResize() {
            const self = this;
            interact('.interact-item')
                .draggable({
                    allowFrom: '.handle',
                    listeners: {
                        move(event) {
                            const target = event.target;
                            const itemId = target.getAttribute('data-id');
                            const item = self.items.find(i => (i.id == itemId || self.items.indexOf(i) == itemId));
                            if (item) {
                                item.x = (item.x || 0) + event.dx / self.scale;
                                item.y = (item.y || 0) + event.dy / self.scale;
                                self.queueSave();
                            }
                        }
                    }
                })
                .resizable({
                    edges: { left: false, right: true, bottom: true, top: false },
                    listeners: {
                        move(event) {
                            const target = event.target;
                            const itemId = target.getAttribute('data-id');
                            const item = self.items.find(i => (i.id == itemId || self.items.indexOf(i) == itemId));
                            if (item) {
                                item.width = Math.max(200, event.rect.width / self.scale);
                                item.height = Math.max(100, event.rect.height / self.scale);
                                self.queueSave();
                            }
                        }
                    }
                });
        }
    };
};
</script>
@endsection
