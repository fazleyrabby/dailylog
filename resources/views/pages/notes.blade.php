@extends('layouts.app')

@section('title', 'Notes')
@section('header_breadcrumbs', 'DAILYLOG // NOTES')
@section('content_padding', 'p-0')

@section('content')
<!-- EasyMDE editor assets -->
<link rel="stylesheet" href="https://unpkg.com/easymde/dist/easymde.min.css">
<script src="https://unpkg.com/easymde/dist/easymde.min.js"></script>

<style>
/* Theme overrides for EasyMDE to match DailyLOG modern dark/light system */
.EasyMDEContainer {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    border: none !important;
    height: calc(100% - 40px);
}
.editor-toolbar {
    background: var(--color-surface-2) !important;
    border: 1px solid var(--color-border) !important;
    border-radius: 4px 4px 0 0 !important;
    opacity: 0.95;
    padding: 4px 8px !important;
}
.editor-toolbar button {
    color: var(--color-text-muted) !important;
    border-radius: 4px !important;
    transition: all 0.15s ease !important;
    background: transparent !important;
    border: none !important;
}
.editor-toolbar button:hover, .editor-toolbar button.active {
    background: var(--color-surface-2) !important;
    color: var(--color-accent) !important;
}
.editor-toolbar i.separator {
    border-left: 1px solid var(--color-border) !important;
}
.CodeMirror {
    flex-grow: 1;
    background: var(--color-surface) !important;
    color: var(--color-text-main) !important;
    border: 1px solid var(--color-border) !important;
    border-top: none !important;
    border-radius: 0 0 4px 4px !important;
    font-family: var(--font-reading) !important;
    font-size: 14px !important;
    line-height: 1.6 !important;
}
.CodeMirror-scroll {
    min-height: 250px;
}
.CodeMirror-cursor {
    border-left: 2px solid var(--color-accent) !important;
}
.CodeMirror-selected {
    background: rgba(94, 106, 210, 0.3) !important;
}
.editor-statusbar {
    display: none !important;
}
/* Enable text selection inside the editor and preview panels, overriding parent select-none */
.CodeMirror, .CodeMirror *, .CodeMirror-scroll, .CodeMirror-lines, .CodeMirror-line, .CodeMirror-line *,
.prose, .prose * {
    user-select: text !important;
    -webkit-user-select: text !important;
}
</style>
<div
    x-data="notesComponent({{ json_encode($notes) }}, {{ json_encode($folders) }})"
    x-init="initMobile()"
    class="h-[calc(100vh-48px)] flex flex-row overflow-hidden bg-surface"
    :class="(resizingFolder || resizingNotes) ? 'cursor-col-resize' : ''"
>
    <!-- PANEL 1: FOLDERS & TAGS SIDEBAR -->
    <div 
        x-show="!isMobile || mobileView === 'folders'"
        :style="isMobile ? 'width: 100%' : 'width:' + folderPanelWidth + 'px'"
        class="flex-shrink-0 flex flex-col bg-surface-2/10 h-full"
    >
        <!-- Folders title and action -->
        <div class="px-4 pt-3.5 pb-1 flex items-center justify-between">
            <span class="text-[9px] font-bold uppercase tracking-wider text-text-subtle">Folders</span>
            <button @click="createFolder()" class="text-text-subtle hover:text-accent text-xs cursor-pointer select-none font-semibold" title="New folder">+ Folder</button>
        </div>
        
        <!-- Folders List -->
        <div class="flex-grow overflow-y-auto pb-4">
            <button
                @click="selectedFolderId = null; if (isMobile) mobileView = 'notes';"
                @dragover.prevent
                @drop="handleDrop($event, 'none')"
                :class="selectedFolderId === null ? 'bg-accent/10 text-accent font-semibold' : 'text-text-muted hover:bg-surface-2/30'"
                class="w-full flex items-center justify-between px-3 py-1.5 text-xxs cursor-pointer select-none"
            >
                <span class="flex items-center min-w-0">
                    <span class="mr-1 w-3.5 inline-block"></span>
                    <svg class="h-3.5 w-3.5 mr-1.5 flex-shrink-0 text-text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                    </svg>
                    <span>All Notes</span>
                </span>
                <span class="font-mono text-text-subtle" x-text="notes.length"></span>
            </button>
            <button
                @click="selectedFolderId = 'none'; if (isMobile) mobileView = 'notes';"
                @dragover.prevent
                @drop="handleDrop($event, 'none')"
                :class="selectedFolderId === 'none' ? 'bg-accent/10 text-accent font-semibold' : 'text-text-muted hover:bg-surface-2/30'"
                class="w-full flex items-center justify-between px-3 py-1.5 text-xxs cursor-pointer select-none"
            >
                <span class="flex items-center min-w-0">
                    <span class="mr-1 w-3.5 inline-block"></span>
                    <svg class="h-3.5 w-3.5 mr-1.5 flex-shrink-0 text-text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0l-2.586 2.586a1 1 0 01-.707.293H7.293a1 1 0 01-.707-.293L4 13" />
                    </svg>
                    <span>Unfiled</span>
                </span>
                <span class="font-mono text-text-subtle" x-text="notes.filter(n => !n.folder_id).length"></span>
            </button>
            <template x-for="folder in folderTree" :key="folder.id">
                <div
                    draggable="true"
                    @dragstart="handleDragStart($event, 'folder', folder.id)"
                    @dragend="handleDragEnd($event)"
                    @dragover.prevent
                    @drop="handleDrop($event, folder.id)"
                    @click="folder.hasChildren ? toggleFolder(folder.id) : (selectedFolderId = folder.id; if (isMobile) mobileView = 'notes';)"
                    :class="selectedFolderId === folder.id ? 'bg-accent/10 text-accent font-semibold' : 'text-text-muted hover:bg-surface-2/30'"
                    class="group w-full flex items-center justify-between pr-3 py-1.5 text-xxs cursor-pointer select-none border-b border-border/10"
                    :style="'padding-left:' + (12 + folder.depth * 14) + 'px'"
                >
                    <span class="flex items-center min-w-0">
                        <button
                            x-show="folder.hasChildren"
                            @click.stop="toggleFolder(folder.id)"
                            class="mr-1 w-3.5 h-3.5 flex items-center justify-center text-text-subtle hover:text-accent transition-transform duration-150"
                            :class="expanded.includes(folder.id) ? 'rotate-90' : ''"
                            title="Toggle"
                        >
                            <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                        <span x-show="!folder.hasChildren" class="mr-1 w-3.5 inline-block"></span>
                        <span class="truncate flex items-center" @click.stop="selectedFolderId = folder.id; if (isMobile) mobileView = 'notes';">
                            <!-- Closed Folder SVG (when not expanded) -->
                            <svg class="h-3.5 w-3.5 mr-1.5 flex-shrink-0 text-text-subtle group-hover:text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-show="!expanded.includes(folder.id)">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                            </svg>
                            <!-- Open Folder SVG (when expanded) -->
                            <svg class="h-3.5 w-3.5 mr-1.5 flex-shrink-0 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-show="expanded.includes(folder.id)">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2v-7a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2zm0 3h16" />
                            </svg>
                            <span x-text="folder.name"></span>
                        </span>
                    </span>
                    <span class="flex items-center space-x-1.5 flex-shrink-0">
                        <span class="font-mono text-text-subtle" x-text="notesInFolder(folder.id)"></span>
                        <button @click.stop="createFolder(folder.id)" class="opacity-0 group-hover:opacity-100 hover:text-accent text-[11px]" title="New subfolder">＋</button>
                        <button @click.stop="renameFolder(folder)" class="opacity-0 group-hover:opacity-100 hover:text-accent text-[11px]" title="Rename">✎</button>
                        <button @click.stop="deleteFolder(folder)" class="opacity-0 group-hover:opacity-100 hover:text-danger text-[11px]" title="Delete">✕</button>
                    </span>
                </div>
            </template>
        </div>

        <!-- Tags list -->
        <div class="border-t border-border bg-surface-2/10 p-3">
            <span class="text-[9px] font-bold uppercase tracking-wider text-text-subtle block mb-2 font-mono">Tags</span>
            <div class="flex flex-wrap gap-1 max-h-32 overflow-y-auto">
                <button 
                    @click="selectedTag = ''; if (isMobile) mobileView = 'notes';" 
                    :class="selectedTag === '' ? 'bg-accent/15 border-accent/20 text-accent font-semibold' : 'bg-surface border-border text-text-muted hover:text-text-main'"
                    class="px-2 py-0.5 rounded-full text-xxs border cursor-pointer select-none"
                >
                    All
                </button>
                <template x-for="tag in allTags" :key="tag">
                    <button 
                        @click="selectedTag = tag; if (isMobile) mobileView = 'notes';" 
                        :class="selectedTag === tag ? 'bg-accent/15 border-accent/20 text-accent font-semibold' : 'bg-surface border-border text-text-muted hover:text-text-main'"
                        class="px-2 py-0.5 rounded-full text-xxs border cursor-pointer select-none"
                    >
                        <span class="text-text-subtle font-mono mr-0.5">#</span><span x-text="tag"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>

    <!-- DRAG HANDLE RESIZER FOR FOLDER SIDEBAR -->
    <div
        x-show="!isMobile"
        @mousedown="startFolderResize($event)"
        class="hidden md:flex w-2.5 flex-shrink-0 h-full z-10 cursor-col-resize items-center justify-center group relative"
    >
        <div class="w-[1px] h-full bg-border group-hover:bg-accent transition-colors duration-150"></div>
        <div class="absolute top-1/2 -translate-y-1/2 w-1 h-7 rounded-full bg-border/60 group-hover:bg-accent transition-colors duration-150 shadow-xs"></div>
    </div>

    <!-- PANEL 2: NOTES LIST -->
    <div
        x-show="!isMobile || mobileView === 'notes'"
        :style="isMobile ? 'width: 100%' : 'width:' + notesPanelWidth + 'px'"
        class="flex-shrink-0 flex flex-col bg-surface select-text h-full"
    >
        <!-- Search bar -->
        <div class="p-3 border-b border-border flex items-center space-x-2 bg-surface">
            <!-- Mobile back button to Folders -->
            <button 
                x-show="isMobile" 
                @click="mobileView = 'folders'" 
                class="mr-1 text-text-subtle hover:text-accent focus:outline-none flex items-center space-x-0.5 text-xxs font-semibold"
            >
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                <span>Folders</span>
            </button>
            <div class="flex-grow">
                <x-ui.search-input x-model="searchQuery" placeholder="Search notes..." />
            </div>
            <x-ui.button variant="secondary" @click="createNote()" class="h-8 font-semibold select-none cursor-pointer">
                + New
            </x-ui.button>
        </div>

        <!-- Notes Scroll List grouped by month -->
        <div class="flex-grow overflow-y-auto divide-y divide-border/40">
            <template x-for="group in groupedNotes" :key="group.name">
                <div>
                    <!-- Group Header -->
                    <div class="px-3 py-1 bg-surface-2/30 border-b border-border/40 text-[9px] font-bold uppercase tracking-wider text-text-subtle font-mono" x-text="group.name"></div>
                    <!-- Notes in Group -->
                    <div class="divide-y divide-border/20">
                        <template x-for="note in group.notes" :key="note.id">
                            <div 
                                draggable="true"
                                @dragstart="handleDragStart($event, 'note', note.id)"
                                @dragend="handleDragEnd($event)"
                                @click="selectedNoteId = note.id; editMode = false; if (isMobile) mobileView = 'editor';"
                                :class="selectedNoteId === note.id ? 'bg-accent/10 text-text-main border-l-2 border-l-accent' : 'text-text-muted hover:bg-surface-2/20 border-l-2 border-l-transparent'"
                                class="p-3 cursor-pointer flex flex-col transition-all"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-xs uppercase tracking-wide text-text-main truncate pr-2" x-text="note.title"></span>
                                </div>
                                <div class="flex items-center space-x-1.5 mt-1 text-[10px] text-text-subtle">
                                    <span class="font-mono flex-shrink-0" x-text="note.updated"></span>
                                    <span class="truncate" x-text="note.body ? note.body.replace(/[#*`]/g, '').substring(0, 60) : ''"></span>
                                </div>
                                <div class="flex items-center space-x-1 mt-2 flex-wrap">
                                    <template x-for="t in note.tags" :key="t">
                                        <span class="bg-surface-2 border border-border text-[9px] px-1 rounded-sm text-text-subtle font-mono">#<span x-text="t"></span></span>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- DRAG HANDLE RESIZER -->
    <div
        x-show="!isMobile"
        @mousedown="startNotesResize($event)"
        class="hidden md:flex w-2.5 flex-shrink-0 h-full z-10 cursor-col-resize items-center justify-center group relative"
    >
        <div class="w-[1px] h-full bg-border group-hover:bg-accent transition-colors duration-150"></div>
        <div class="absolute top-1/2 -translate-y-1/2 w-1 h-7 rounded-full bg-border/60 group-hover:bg-accent transition-colors duration-150 shadow-xs"></div>
    </div>

    <!-- PANEL 3: EDITOR / PREVIEW CANVAS -->
    <div 
        x-show="!isMobile || mobileView === 'editor'"
        class="flex-grow flex flex-col h-full bg-surface overflow-hidden select-text min-w-0"
    >
        <!-- Editor Controls Header -->
        <div class="px-4 py-2.5 border-b border-border bg-surface-2/10 flex flex-wrap items-center justify-between gap-y-2 gap-x-3">
            <div class="flex items-center gap-2 min-w-0 flex-shrink">
                <!-- Mobile back button to Notes -->
                <button 
                    x-show="isMobile" 
                    @click="mobileView = 'notes'" 
                    class="mr-1 text-text-subtle hover:text-accent focus:outline-none flex items-center space-x-0.5 text-xxs font-semibold"
                >
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span>Notes</span>
                </button>
                
                <span class="text-[10px] font-mono bg-surface border border-border text-text-muted px-1.5 py-0.5 rounded-sm" x-text="'@' + activeNote.project"></span>
                <template x-if="activeNote.id">
                    <select
                        @change="moveNote(activeNote.id, $event.target.value)"
                        class="text-[10px] font-mono bg-surface border border-border text-text-muted px-1.5 py-0.5 rounded-sm focus:ring-0 focus:outline-none cursor-pointer min-w-0 max-w-[130px] md:max-w-none truncate"
                    >
                        <option value="" :selected="!activeNote.folder_id">Unfiled</option>
                        <template x-for="folder in folderTree" :key="folder.id">
                            <option :value="folder.id" :selected="activeNote.folder_id === folder.id" x-text="' '.repeat(folder.depth * 2) + '├─ ' + folder.name"></option>
                        </template>
                    </select>
                </template>
                <span class="text-xxs text-text-subtle" x-show="activeNote.id">Saved</span>
            </div>
            
            <div class="flex items-center gap-2 flex-shrink-0">
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
            <div x-show="editMode" class="flex-grow p-6 flex flex-col">
                <input 
                    type="text" 
                    x-model="activeNote.title" 
                    class="w-full text-xl font-bold bg-transparent border-0 border-b border-border pb-2 focus:ring-0 focus:border-accent focus:outline-none mb-3 text-text-main font-serif-reading"
                    placeholder="Note title"
                />
                <textarea 
                    x-ref="noteBodyEditor"
                    class="hidden"
                ></textarea>
            </div>

            <!-- PREVIEW MODE (Editorial read mode) -->
            <div x-show="!editMode" class="flex-grow overflow-y-auto w-full select-text">
                <div class="max-w-2xl mx-auto px-6 py-6 font-serif-reading">
                    <h1 class="text-2xl font-bold text-text-main border-b border-border pb-3 mb-1 font-serif-reading" x-text="activeNote.title"></h1>
                    <p class="text-xxs text-text-subtle font-mono mb-4 mt-2">Last updated: <span x-text="activeNote.updated"></span></p>
                    <div class="prose prose-sm dark:prose-invert max-w-none font-serif-reading text-text-main select-text
                                prose-headings:font-bold prose-headings:text-text-main
                                prose-h1:text-xl prose-h2:text-lg prose-h3:text-base
                                prose-p:text-base prose-p:leading-relaxed
                                prose-li:text-base prose-li:leading-relaxed
                                prose-code:font-mono prose-code:text-xs prose-pre:text-xs
                                prose-a:text-accent" 
                         x-html="window.marked.parse(activeNote.body || '')"></div>
                    
                    <!-- BACKLINKS DRAWER PANEL -->
                    <div class="mt-8 border-t border-border pt-4 select-none font-sans">
                        <h4 class="text-xxs font-bold text-text-subtle uppercase tracking-wider mb-2.5">Linked Backlinks</h4>
                        <template x-if="activeNote.backlinks && activeNote.backlinks.length > 0">
                            <div class="flex flex-wrap gap-2">
                                <template x-for="backlink in activeNote.backlinks" :key="backlink">
                                    <a :href="'/search?q=' + encodeURIComponent(backlink)" class="inline-flex items-center px-2 py-1 rounded-xs border border-border bg-surface-2/40 text-xxs font-mono text-accent hover:bg-surface-2 hover:border-accent/40 transition-colors">
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
window.notesComponent = function(initialNotes, initialFolders) {
    return {
        searchQuery: '',
        selectedTag: '',
        selectedFolderId: null,
        selectedNoteId: (() => {
            const params = new URLSearchParams(window.location.search);
            const noteId = parseInt(params.get('note'), 10);
            if (noteId && initialNotes.some(n => n.id === noteId)) {
                return noteId;
            }
            return initialNotes.length > 0 ? initialNotes[0].id : null;
        })(),
        editMode: false,

        folderPanelWidth: (() => {
            const stored = parseInt(localStorage.getItem('panel-w-notes_folders'), 10);
            return !isNaN(stored) ? Math.max(180, Math.min(400, stored)) : 240;
        })(),
        notesPanelWidth: (() => {
            const stored = parseInt(localStorage.getItem('panel-w-notes_list'), 10);
            return !isNaN(stored) ? Math.max(200, Math.min(500, stored)) : 280;
        })(),
        resizingFolder: false,
        resizingNotes: false,
        easymde: null,

        notes: initialNotes,
        folders: initialFolders || [],
        expanded: [],

        // Mobile Responsive state
        isMobile: window.innerWidth < 768,
        mobileView: 'folders', // 'folders', 'notes', 'editor'

        startFolderResize(event) {
            if (this.isMobile) return;
            event.preventDefault();

            this.resizingFolder = true;
            document.body.style.cursor    = 'col-resize';
            document.body.style.userSelect = 'none';

            const startX     = event.clientX;
            const startWidth = this.folderPanelWidth;

            const move = (e) => {
                if (!this.resizingFolder) return;
                const cap = Math.min(400, Math.floor(window.innerWidth * 0.4));
                this.folderPanelWidth = Math.max(180, Math.min(cap, startWidth + (e.clientX - startX)));
            };

            const up = () => {
                this.resizingFolder = false;
                document.body.style.cursor    = '';
                document.body.style.userSelect = '';
                try { localStorage.setItem('panel-w-notes_folders', this.folderPanelWidth); } catch (_) {}
                document.removeEventListener('mousemove', move);
                document.removeEventListener('mouseup',   up);
            };

            document.addEventListener('mousemove', move);
            document.addEventListener('mouseup',   up);
        },

        startNotesResize(event) {
            if (this.isMobile) return;
            event.preventDefault();

            this.resizingNotes = true;
            document.body.style.cursor    = 'col-resize';
            document.body.style.userSelect = 'none';

            const startX     = event.clientX;
            const startWidth = this.notesPanelWidth;

            const move = (e) => {
                if (!this.resizingNotes) return;
                const cap = Math.min(500, Math.floor(window.innerWidth * 0.5));
                this.notesPanelWidth = Math.max(200, Math.min(cap, startWidth + (e.clientX - startX)));
            };

            const up = () => {
                this.resizingNotes = false;
                document.body.style.cursor    = '';
                document.body.style.userSelect = '';
                try { localStorage.setItem('panel-w-notes_list', this.notesPanelWidth); } catch (_) {}
                document.removeEventListener('mousemove', move);
                document.removeEventListener('mouseup',   up);
            };

            document.addEventListener('mousemove', move);
            document.addEventListener('mouseup',   up);
        },

        get csrfHeaders() {
            return {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            };
        },

        initMobile() {
            window.addEventListener('resize', () => {
                this.isMobile = window.innerWidth < 768;
            });

            // If selectedNoteId was passed via query parameter, auto-select its folder
            if (this.selectedNoteId) {
                const note = this.notes.find(n => n.id === this.selectedNoteId);
                if (note) {
                    this.selectedFolderId = note.folder_id || 'none';
                    if (this.isMobile) {
                        this.mobileView = 'editor';
                    }
                }
            } else if (!this.isMobile && this.notes.length > 0) {
                // Auto open first note on desktop
                this.selectedNoteId = this.notes[0].id;
            }

            // Initialize EasyMDE Editor
            this.$nextTick(() => {
                this.easymde = new EasyMDE({
                    element: this.$refs.noteBodyEditor,
                    spellChecker: false,
                    autosave: { enabled: false },
                    status: false,
                    placeholder: "Write in markdown... (supports bold, headings, links, lists, etc.)",
                    toolbar: ["bold", "italic", "heading", "|", "quote", "unordered-list", "ordered-list", "|", "link", "image", "|", "preview", "side-by-side", "fullscreen"],
                    nativeSpellcheck: true
                });

                // Disable CodeMirror's text drag-and-drop. With the app-wide
                // `select-none`, dragging text would move it instead of
                // selecting; turning this off restores normal drag-to-select.
                this.easymde.codemirror.setOption('dragDrop', false);

                // Sync EasyMDE changes to Alpine state
                this.easymde.codemirror.on("change", () => {
                    this.activeNote.body = this.easymde.value();
                });

                // Set initial value
                if (this.activeNote && this.activeNote.id) {
                    this.easymde.value(this.activeNote.body || "");
                }
            });

            // Watch for note selection changes
            this.$watch('selectedNoteId', (newId) => {
                if (this.easymde) {
                    const note = this.notes.find(n => n.id === newId);
                    this.easymde.value(note ? (note.body || "") : "");
                }
            });

            // Watch for editMode toggles to refresh layout
            this.$watch('editMode', (val) => {
                if (val && this.easymde) {
                    this.$nextTick(() => {
                        this.easymde.codemirror.refresh();
                        this.easymde.codemirror.focus();
                    });
                }
            });
        },

        // HTML5 Drag & Drop handlers
        handleDragStart(e, type, id) {
            e.dataTransfer.setData('text/plain', `${type}:${id}`);
            e.target.style.opacity = '0.4';
        },

        handleDragEnd(e) {
            e.target.style.opacity = '1';
        },

        handleDrop(e, targetFolderId) {
            e.preventDefault();
            const data = e.dataTransfer.getData('text/plain');
            if (!data) return;

            const [type, idStr] = data.split(':');
            const id = parseInt(idStr, 10);
            
            const folderIdVal = targetFolderId === 'none' ? null : parseInt(targetFolderId, 10);

            if (type === 'note') {
                this.moveNote(id, folderIdVal);
            } else if (type === 'folder') {
                if (id === folderIdVal) return;
                // Avoid recursive loops: can't move parent into its own child
                if (folderIdVal && this.descendantIds(id).includes(folderIdVal)) {
                    alert('Cannot move a folder into its own subfolder.');
                    return;
                }
                this.moveFolder(id, folderIdVal);
            }
        },

        moveFolder(id, parentId) {
            const folder = this.folders.find(f => f.id === id);
            if (!folder) return;

            fetch(`/folders/${id}`, {
                method: 'PUT',
                headers: this.csrfHeaders,
                body: JSON.stringify({
                    name: folder.name,
                    parent_id: parentId
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.folder) {
                    let index = this.folders.findIndex(f => f.id === data.folder.id);
                    if (index !== -1) {
                        this.folders[index] = data.folder;
                    }
                    this.folders.sort((a, b) => a.name.localeCompare(b.name));
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: 'Folder structure updated' }
                    }));
                }
            });
        },

        // Group notes in list by month
        get groupedNotes() {
            const groups = [];
            const map = {};
            this.filteredNotes.forEach(n => {
                const key = n.month || 'Other';
                if (!map[key]) {
                    map[key] = [];
                    groups.push({ name: key, notes: map[key] });
                }
                map[key].push(n);
            });
            return groups;
        },

        // Folders grouped by parent, each child list name-sorted.
        get foldersByParent() {
            const byParent = {};
            this.folders.forEach(f => {
                const key = (f.parent_id == null) ? 'root' : f.parent_id;
                (byParent[key] = byParent[key] || []).push(f);
            });
            Object.values(byParent).forEach(arr => arr.sort((a, b) => a.name.localeCompare(b.name)));
            return byParent;
        },

        // Flattened tree in display order, skipping descendants of collapsed parents.
        get folderTree() {
            const byParent = this.foldersByParent;
            const out = [];
            const walk = (key, depth) => {
                (byParent[key] || []).forEach(f => {
                    const hasChildren = !!(byParent[f.id] && byParent[f.id].length);
                    out.push({ ...f, depth, hasChildren });
                    if (hasChildren && this.expanded.includes(f.id)) walk(f.id, depth + 1);
                });
            };
            walk('root', 0);
            return out;
        },

        descendantIds(id) {
            const byParent = this.foldersByParent;
            const ids = [];
            const walk = (k) => (byParent[k] || []).forEach(f => { ids.push(f.id); walk(f.id); });
            walk(id);
            return ids;
        },

        // Note count for a folder including all its subfolders.
        notesInFolder(id) {
            const ids = new Set([id, ...this.descendantIds(id)]);
            return this.notes.filter(n => ids.has(n.folder_id)).length;
        },

        toggleFolder(id) {
            const i = this.expanded.indexOf(id);
            if (i === -1) this.expanded.push(id);
            else this.expanded.splice(i, 1);
        },

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
                let matchesFolder;
                if (this.selectedFolderId === null) {
                    matchesFolder = true;
                } else if (this.selectedFolderId === 'none') {
                    matchesFolder = !n.folder_id;
                } else {
                    const ids = new Set([this.selectedFolderId, ...this.descendantIds(this.selectedFolderId)]);
                    matchesFolder = ids.has(n.folder_id);
                }
                return matchesSearch && matchesTag && matchesFolder;
            });
        },

        get activeNote() {
            return this.notes.find(n => n.id === this.selectedNoteId) || {
                id: null,
                folder_id: null,
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
            const folderId = (typeof this.selectedFolderId === 'number') ? this.selectedFolderId : null;

            fetch('/notes', {
                method: 'POST',
                headers: this.csrfHeaders,
                body: JSON.stringify({ folder_id: folderId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.note) {
                    this.notes.unshift(data.note);
                    this.selectedNoteId = data.note.id;
                    this.editMode = true;
                    if (this.isMobile) this.mobileView = 'editor';
                }
            });
        },

        moveNote(id, folderId) {
            fetch(`/notes/${id}/move`, {
                method: 'PATCH',
                headers: this.csrfHeaders,
                body: JSON.stringify({ folder_id: folderId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.note) {
                    let index = this.notes.findIndex(n => n.id === data.note.id);
                    if (index !== -1) this.notes[index] = data.note;
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: 'Note moved' }
                    }));
                }
            });
        },

        createFolder(parentId = null) {
            const name = prompt(parentId ? 'Subfolder name:' : 'Folder name:');
            if (!name || !name.trim()) return;

            fetch('/folders', {
                method: 'POST',
                headers: this.csrfHeaders,
                body: JSON.stringify({ name: name.trim(), parent_id: parentId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.folder) {
                    this.folders.push(data.folder);
                    if (parentId && !this.expanded.includes(parentId)) this.expanded.push(parentId);
                    this.selectedFolderId = data.folder.id;
                }
            });
        },

        renameFolder(folder) {
            const name = prompt('Rename folder:', folder.name);
            if (!name || !name.trim() || name.trim() === folder.name) return;

            fetch(`/folders/${folder.id}`, {
                method: 'PUT',
                headers: this.csrfHeaders,
                body: JSON.stringify({ name: name.trim(), parent_id: folder.parent_id })
            })
            .then(res => res.json())
            .then(data => {
                if (data.folder) {
                    let index = this.folders.findIndex(f => f.id === data.folder.id);
                    if (index !== -1) this.folders[index] = data.folder;
                    this.folders.sort((a, b) => a.name.localeCompare(b.name));
                }
            });
        },

        deleteFolder(folder) {
            const kids = this.descendantIds(folder.id);
            const msg = kids.length
                ? `Delete "${folder.name}" and its ${kids.length} subfolder(s)? Notes inside become Unfiled.`
                : `Delete folder "${folder.name}"? Notes inside become Unfiled.`;
            if (!confirm(msg)) return;

            fetch(`/folders/${folder.id}`, {
                method: 'DELETE',
                headers: this.csrfHeaders
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const gone = new Set([folder.id, ...kids]);
                    this.folders = this.folders.filter(f => !gone.has(f.id));
                    this.expanded = this.expanded.filter(id => !gone.has(id));
                    this.notes.forEach(n => { if (gone.has(n.folder_id)) n.folder_id = null; });
                    if (gone.has(this.selectedFolderId)) this.selectedFolderId = null;
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: 'Folder deleted' }
                    }));
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
                    if (this.isMobile) this.mobileView = 'notes';
                }
            });
        }
    };
};
</script>
@endsection
