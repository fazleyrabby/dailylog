@extends('layouts.app')

@section('title', 'Notes')
@section('header_breadcrumbs', 'DAILYLOG // NOTES')

@section('content')
<div 
    x-data="notesComponent()"
    class="h-[calc(100vh-100px)] flex overflow-hidden border border-border rounded-sm bg-surface"
>
    <!-- LEFT SIDEBAR: Lists & Search (Width 320px) -->
    <div class="w-80 flex-shrink-0 border-r border-border flex flex-col bg-surface-2/10">
        <!-- Search bar -->
        <div class="p-3 border-b border-border bg-surface flex items-center space-x-2">
            <div class="flex-grow">
                <x-ui.search-input x-model="searchQuery" placeholder="Search notes..." />
            </div>
            <x-ui.button variant="secondary" @click="createNote()" class="h-8 font-semibold select-none cursor-pointer">
                + New
            </x-ui.button>
        </div>

        <!-- Tag chips filter list -->
        <div class="px-3 py-2 border-b border-border bg-surface flex items-center space-x-1.5 overflow-x-auto whitespace-nowrap scrollbar-none">
            <button 
                @click="selectedTag = ''" 
                :class="selectedTag === '' ? 'bg-accent/15 border-accent/20 text-accent font-semibold' : 'bg-surface border-border text-text-muted hover:text-text-main'"
                class="px-2 py-0.5 rounded-full text-xxs border cursor-pointer select-none"
            >
                All
            </button>
            <template x-for="tag in ['laravel', 'postgres', 'redis', 'docker', 'security']">
                <button 
                    @click="selectedTag = tag" 
                    :class="selectedTag === tag ? 'bg-accent/15 border-accent/20 text-accent font-semibold' : 'bg-surface border-border text-text-muted hover:text-text-main'"
                    class="px-2 py-0.5 rounded-full text-xxs border cursor-pointer select-none"
                >
                    <span class="text-text-subtle font-mono mr-0.5">#</span><span x-text="tag"></span>
                </button>
            </template>
        </div>

        <!-- Notes Scroll List -->
        <div class="flex-grow overflow-y-auto divide-y divide-border">
            <template x-for="note in filteredNotes" :key="note.id">
                <div 
                    @click="selectedNoteId = note.id; editMode = false"
                    :class="selectedNoteId === note.id ? 'bg-accent-subtle-bg/30 text-text-main' : 'text-text-muted hover:bg-surface-2/30'"
                    class="p-3.5 cursor-pointer flex flex-col transition-all"
                >
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-xs uppercase tracking-wide text-text-main" x-text="note.title"></span>
                        <span class="text-[10px] text-text-subtle font-mono" x-text="note.updated"></span>
                    </div>
                    <p class="text-xxs text-text-muted mt-1 truncate" x-text="note.body.replace(/[#*`]/g, '')"></p>
                    <div class="flex items-center space-x-1 mt-2 flex-wrap">
                        <template x-for="t in note.tags">
                            <span class="bg-surface-2 border border-border text-[9px] px-1 rounded-sm text-text-subtle">#<span x-text="t"></span></span>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- RIGHT SECTION: Editor / Read Mode (Fluid width) -->
    <div class="flex-grow flex flex-col h-full bg-surface overflow-hidden">
        
        <!-- Editor Controls Header -->
        <div class="px-4 py-2.5 border-b border-border bg-surface-2/10 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="text-[10px] font-mono bg-surface border border-border text-text-muted px-1.5 py-0.5 rounded-sm" x-text="'@' + activeNote.project"></span>
                <span class="text-xxs text-text-subtle">Auto-saved</span>
            </div>
            
            <div class="flex items-center space-x-2">
                <!-- Mode Toggle -->
                <button 
                    @click="editMode = !editMode"
                    class="h-7 px-2.5 bg-surface border border-border hover:bg-surface-2 text-xxs font-medium rounded-sm flex items-center space-x-1 cursor-pointer select-none text-text-main"
                >
                    <span x-text="editMode ? '👁 Preview' : '✎ Edit'"></span>
                </button>
                
                <template x-if="editMode">
                    <x-ui.button variant="primary" size="sm" @click="saveNote()" class="font-semibold cursor-pointer">
                        Save
                    </x-ui.button>
                </template>
            </div>
        </div>

        <!-- Note Canvas -->
        <div class="flex-grow flex flex-col overflow-hidden">
            <!-- EDIT MODE (Plain Markdown textarea) -->
            <div x-show="editMode" class="flex-grow p-5 flex flex-col">
                <input 
                    type="text" 
                    x-model="activeNote.title" 
                    class="w-full text-base font-bold bg-transparent border-0 border-b border-border pb-2 focus:ring-0 focus:border-accent focus:outline-none mb-3 text-text-main"
                    placeholder="Note title"
                />
                <textarea 
                    x-model="activeNote.body" 
                    class="w-full flex-grow bg-transparent border-0 text-sm font-mono focus:ring-0 focus:outline-none resize-none text-text-main"
                    placeholder="Write in markdown... [[link-note]] or #tag"
                ></textarea>
            </div>

            <!-- PREVIEW MODE (Editorial read mode) -->
            <div x-show="!editMode" class="flex-grow p-6 overflow-y-auto font-serif-reading max-w-2xl mx-auto w-full leading-relaxed select-text">
                <h1 class="text-2xl font-bold font-sans-ui text-text-main border-b border-border pb-3 mb-4" x-text="activeNote.title"></h1>
                
                <div class="text-sm md:text-base text-text-main space-y-4">
                    <p class="italic text-xs text-text-subtle font-mono">Last updated: <span x-text="activeNote.updated"></span></p>
                    <div class="prose dark:prose-invert max-w-none text-xs font-mono bg-surface-2 p-3 border border-border rounded-sm select-text whitespace-pre-line" x-text="activeNote.body"></div>
                </div>
                
                <!-- BACKLINKS DRAWER PANEL -->
                <div class="mt-8 border-t border-border pt-4 select-none">
                    <h4 class="text-xxs font-bold text-text-subtle uppercase tracking-wider mb-2.5">Linked Backlinks</h4>
                    <template x-if="activeNote.backlinks && activeNote.backlinks.length > 0">
                        <div class="flex flex-wrap gap-2">
                            <template x-for="backlink in activeNote.backlinks">
                                <a href="/notes" class="inline-flex items-center px-2 py-1 rounded-sm border border-border bg-surface-2/40 text-xxs font-mono text-accent hover:bg-surface-2 hover:border-accent/40 transition-colors">
                                    <span class="mr-1">🔗</span><span x-text="backlink"></span>
                                </a>
                            </template>
                        </div>
                    </template>
                    <template x-if="!activeNote.backlinks || activeNote.backlinks.length === 0">
                        <p class="text-xxs text-text-subtle italic">No backlinks reference this note.</p>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.notesComponent = function() {
    return {
        searchQuery: '',
        selectedTag: '',
        selectedNoteId: 10,
        editMode: false,
        
        notes: [
            {
                id: 10, 
                title: 'Laravel Optimization Notes', 
                body: '## Production Configs\n\nOptimizing Laravel 12 monoliths at personal scale. Ensure the following configurations are set:\n\n- Enable **OPcache** in `php.ini`.\n- Run `php artisan config:cache`, `route:cache`, and `view:cache`.\n- Configure **Redis** as cache and session driver.\n- Use **Octane** with FrankenPHP for maximum response efficiency.',
                tags: ['laravel', 'performance'], 
                project: 'DailyLOG', 
                updated: '2 hours ago',
                backlinks: ['Dashboard widget', 'Redis Streams Research']
            },
            {
                id: 11, 
                title: 'PostgreSQL Full Text Search Configuration', 
                body: '## Postgres FTS setup\n\nUsing Postgres instead of Elasticsearch for simple local projects:\n\n- Map a tsvector column for matching keywords.\n- Build a custom database trigger to refresh vectors on save.\n- Run matches with dynamic `tsquery` input variables.',
                tags: ['postgres', 'db', 'search'], 
                project: 'DailyLOG', 
                updated: 'Yesterday',
                backlinks: ['AWS ECS Learning Path']
            },
            {
                id: 12, 
                title: 'Redis Streams Pub/Sub Architecture', 
                body: 'Detailed research on how Redis Streams can act as a message broker for async jobs without overhead. Discuss consumer group logic, stream trimming, and XACK acknowledge logic.',
                tags: ['redis', 'architecture'], 
                project: 'DailyLOG', 
                updated: '5 hours ago',
                backlinks: ['Laravel Optimization Notes']
            },
            {
                id: 13, 
                title: 'Docker Container Security Checklist', 
                body: 'Security guidelines for production containers:\n- Use non-root user execution\n- Read-only file system configurations\n- Block port scans using strict firewalls.',
                tags: ['docker', 'security'], 
                project: 'DevOps', 
                updated: '3 days ago',
                backlinks: []
            }
        ],

        get filteredNotes() {
            return this.notes.filter(n => {
                let matchesSearch = n.title.toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                                    n.body.toLowerCase().includes(this.searchQuery.toLowerCase());
                let matchesTag = this.selectedTag === '' || n.tags.includes(this.selectedTag);
                return matchesSearch && matchesTag;
            });
        },

        get activeNote() {
            return this.notes.find(n => n.id === this.selectedNoteId) || this.notes[0];
        },

        saveNote() {
            window.dispatchEvent(new CustomEvent('show-toast', { 
                detail: { message: 'Note auto-saved successfully', action: 'Dismiss' }
            }));
            this.editMode = false;
        },

        createNote() {
            let id = Date.now();
            let newNote = {
                id: id,
                title: 'Untitled Note',
                body: '# Untitled Note\n\nStart writing notes in markdown here...',
                tags: ['draft'],
                project: 'DailyLOG',
                updated: 'Just now',
                backlinks: []
            };
            this.notes.unshift(newNote);
            this.selectedNoteId = id;
            this.editMode = true;
        }
    };
};
</script>
@endsection
