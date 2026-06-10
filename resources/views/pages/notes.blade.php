@extends('layouts.app')

@section('title', 'Notes')
@section('header_breadcrumbs', 'DAILYLOG // NOTES')

@section('content')
<div
    x-data="Object.assign(notesComponent({{ json_encode($notes) }}), panelResizer({key:'notes', initial:320, min:240, max:600}))"
    x-init="initPanelResizer()"
    class="h-[calc(100vh-100px)] flex flex-col md:flex-row overflow-hidden border border-border rounded-sm bg-surface select-none"
    :class="resizing ? 'cursor-col-resize' : ''"
>
    <!-- LEFT SIDEBAR: Lists & Search -->
    <div
        :style="isMobile ? '' : 'width:' + panelWidth + 'px'"
        class="w-full md:flex-shrink-0 flex flex-col bg-surface-2/10 select-text border-b md:border-b-0 md:border-r border-border max-h-[45vh] md:max-h-full"
    >
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
            <template x-for="tag in allTags" :key="tag">
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
                    :class="selectedNoteId === note.id ? 'bg-accent-subtle-bg/30 text-text-main border-l-2 border-accent' : 'text-text-muted hover:bg-surface-2/30 border-l-2 border-transparent'"
                    class="p-3.5 cursor-pointer flex flex-col transition-all"
                >
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-xs uppercase tracking-wide text-text-main" x-text="note.title"></span>
                        <span class="text-[10px] text-text-subtle font-mono" x-text="note.updated"></span>
                    </div>
                    <p class="text-xxs text-text-muted mt-1 truncate" x-text="note.body ? note.body.replace(/[#*`]/g, '') : ''"></p>
                    <div class="flex items-center space-x-1 mt-2 flex-wrap">
                        <template x-for="t in note.tags" :key="t">
                            <span class="bg-surface-2 border border-border text-[9px] px-1 rounded-sm text-text-subtle font-mono">#<span x-text="t"></span></span>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- DRAG HANDLE RESIZER -->
    <div
        @mousedown="startPanelResize($event)"
        class="hidden md:flex w-3 flex-shrink-0 h-full z-10 cursor-col-resize items-center justify-center group"
    >
        <div class="w-[2px] h-full bg-border group-hover:bg-accent transition-colors duration-150"></div>
    </div>

    <!-- RIGHT SECTION: Editor / Read Mode (Fluid width) -->
    <div class="flex-grow flex flex-col h-full bg-surface overflow-hidden select-text min-w-0">
        
        <!-- Editor Controls Header -->
        <div class="px-4 py-2.5 border-b border-border bg-surface-2/10 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="text-[10px] font-mono bg-surface border border-border text-text-muted px-1.5 py-0.5 rounded-sm" x-text="'@' + activeNote.project"></span>
                <span class="text-xxs text-text-subtle" x-show="activeNote.id">Saved</span>
            </div>
            
            <div class="flex items-center space-x-2">
                <template x-if="activeNote.id">
                    <x-ui.button variant="danger" size="sm" @click="deleteNote(activeNote.id)">
                        Delete
                    </x-ui.button>
                </template>
 
                <!-- Mode Toggle -->
                <x-ui.button variant="secondary" size="sm" @click="editMode = !editMode">
                    <span x-text="editMode ? '👁 Preview' : '✎ Edit'"></span>
                </x-ui.button>
                
                <template x-if="editMode && activeNote.id">
                    <x-ui.button variant="primary" size="sm" @click="saveNote()">
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
            <div x-show="!editMode" class="flex-grow overflow-y-auto w-full select-text">
                <div class="max-w-2xl mx-auto px-6 py-5 font-sans-ui">
                    <h1 class="text-lg font-bold text-text-main border-b border-border pb-3 mb-1" x-text="activeNote.title"></h1>
                    <p class="text-xxs text-text-subtle font-mono mb-4">Last updated: <span x-text="activeNote.updated"></span></p>
                    <div class="prose prose-sm dark:prose-invert max-w-none font-sans-ui text-text-main select-text
                                prose-headings:font-semibold prose-headings:text-text-main
                                prose-h1:text-base prose-h2:text-sm prose-h3:text-sm
                                prose-p:text-sm prose-p:leading-relaxed
                                prose-li:text-sm prose-li:leading-relaxed
                                prose-code:text-xs prose-pre:text-xs
                                prose-a:text-accent" 
                         x-html="window.marked.parse(activeNote.body || '')"></div>
                    
                    <!-- BACKLINKS DRAWER PANEL -->
                    <div class="mt-8 border-t border-border pt-4 select-none">
                        <h4 class="text-xxs font-bold text-text-subtle uppercase tracking-wider mb-2.5">Linked Backlinks</h4>
                        <template x-if="activeNote.backlinks && activeNote.backlinks.length > 0">
                            <div class="flex flex-wrap gap-2">
                                <template x-for="backlink in activeNote.backlinks" :key="backlink">
                                    <a href="/notes" class="inline-flex items-center px-2 py-1 rounded-sm border border-border bg-surface-2/40 text-xxs font-mono text-accent hover:bg-surface-2 hover:border-accent/40 transition-colors">
                                        <span class="mr-1">🔗</span><span x-text="backlink"></span>
                                    </a>
                                </template>
                            </div>
                        </template>
                        <template x-if="!activeNote.backlinks || activeNote.backlinks.length === 0">
                            <p class="text-xxs text-text-subtle">No backlinks reference this note.</p>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.notesComponent = function(initialNotes) {
    return {
        searchQuery: '',
        selectedTag: '',
        selectedNoteId: initialNotes.length > 0 ? initialNotes[0].id : null,
        editMode: false,

        notes: initialNotes,

        get allTags() {
            const tags = new Set();
            this.notes.forEach(n => {
                if (n.tags) n.tags.forEach(t => tags.add(t));
            });
            return Array.from(tags);
        },

        get filteredNotes() {
            return this.notes.filter(n => {
                let matchesSearch = n.title.toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                                    (n.body && n.body.toLowerCase().includes(this.searchQuery.toLowerCase()));
                let matchesTag = this.selectedTag === '' || (n.tags && n.tags.includes(this.selectedTag));
                return matchesSearch && matchesTag;
            });
        },

        get activeNote() {
            return this.notes.find(n => n.id === this.selectedNoteId) || {
                id: null,
                title: 'No note selected',
                body: 'Start creating notes...',
                tags: [],
                project: 'None',
                updated: '',
                backlinks: []
            };
        },

        saveNote() {
            if (!this.activeNote.id) return;
            
            fetch(`/notes/${this.activeNote.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    title: this.activeNote.title,
                    body: this.activeNote.body
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.note) {
                    let index = this.notes.findIndex(n => n.id === data.note.id);
                    if (index !== -1) {
                        this.notes[index] = data.note;
                    }
                    window.dispatchEvent(new CustomEvent('show-toast', { 
                        detail: { message: 'Note saved successfully' }
                    }));
                    this.editMode = false;
                }
            });
        },

        createNote() {
            fetch('/notes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.note) {
                    this.notes.unshift(data.note);
                    this.selectedNoteId = data.note.id;
                    this.editMode = true;
                }
            });
        },

        deleteNote(id) {
            if (!confirm('Are you sure you want to archive this note?')) return;

            fetch(`/notes/${id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.notes = this.notes.filter(n => n.id !== id);
                    this.selectedNoteId = this.notes.length > 0 ? this.notes[0].id : null;
                    this.editMode = false;
                    window.dispatchEvent(new CustomEvent('show-toast', { 
                        detail: { message: 'Note archived successfully' }
                    }));
                }
            });
        }
    };
};
</script>
@endsection
