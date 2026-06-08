<div 
    x-data="commandPaletteComponent()"
    x-show="open"
    class="fixed inset-0 z-50 flex items-start justify-center pt-[15vh] px-4"
    style="display: none;"
>
    <!-- Dim Backdrop -->
    <div 
        x-show="open" 
        x-transition:enter="ease-out duration-100"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-75"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="close()" 
        class="fixed inset-0 bg-stone-900/60 dark:bg-stone-950/80 backdrop-blur-xs"
    ></div>

    <!-- Palette Box -->
    <div 
        x-show="open"
        x-transition:enter="ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-98 -translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-98 -translate-y-2"
        class="w-full max-w-xl bg-surface border border-border rounded-sm shadow-xl overflow-hidden z-10 flex flex-col h-[480px]"
    >
        <!-- Search Row -->
        <div class="flex items-center px-4 py-3 border-b border-border bg-surface">
            <svg class="h-5 w-5 text-text-subtle mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input 
                x-ref="paletteInput"
                x-model="input"
                @keydown.down.prevent="selectNext()"
                @keydown.up.prevent="selectPrev()"
                @keydown.enter.prevent="confirmSelection()"
                @keydown.cmd.enter.prevent="confirmSelection()"
                type="text" 
                placeholder="Type a command or capture something (e.g. task Buy milk due:tomorrow #personal)..."
                class="w-full bg-transparent border-0 text-sm text-text-main placeholder-text-subtle focus:ring-0 focus:outline-none"
            />
            <kbd class="text-[10px] font-mono border border-border bg-surface-2 text-text-subtle px-1 rounded-xs">ESC</kbd>
        </div>

        <!-- Real-time Grammar Parsing Visualizer (Wow Factor) -->
        <template x-if="parsedGrammar">
            <div class="px-4 py-2 bg-accent-subtle-bg/60 border-b border-border text-xs flex flex-wrap items-center gap-1.5">
                <span class="text-text-muted font-medium">Parser Preview:</span>
                <span class="bg-accent/10 border border-accent/20 text-accent font-mono px-1 rounded-sm text-[10px]">
                    <span x-text="parsedGrammar.verb"></span>
                </span>
                <span class="text-text-main font-medium truncate max-w-[150px]" x-text="parsedGrammar.title"></span>
                
                <template x-for="tag in parsedGrammar.tags">
                    <span class="bg-surface-2 text-text-muted border border-border px-1.5 py-0.2 rounded-full text-[10px]">#<span x-text="tag"></span></span>
                </template>
                <template x-for="proj in parsedGrammar.projects">
                    <span class="bg-orange-500/10 text-orange-600 border border-orange-500/20 px-1.5 py-0.2 rounded-full text-[10px]">@<span x-text="proj"></span></span>
                </template>
                <template x-if="parsedGrammar.due">
                    <span class="bg-warning/10 text-warning border border-warning/20 px-1.5 py-0.2 rounded-full text-[10px] flex items-center">
                        📅 <span x-text="parsedGrammar.due" class="ml-0.5"></span>
                    </span>
                </template>
                <template x-if="parsedGrammar.priority">
                    <span class="bg-danger/10 text-danger border border-danger/20 px-1.5 py-0.2 rounded-full text-[10px]">
                        ⚠️ Priority: <span x-text="parsedGrammar.priority"></span>
                    </span>
                </template>
            </div>
        </template>

        <!-- Results List -->
        <div x-ref="resultsList" class="flex-grow overflow-y-auto divide-y divide-border/40 bg-surface-2/10">
            <div class="px-3 py-1.5 text-[10px] uppercase font-semibold text-text-subtle tracking-wider bg-surface-2/30 border-b border-border/20">
                Commands & Suggestions
            </div>
            <template x-for="(item, idx) in results" :key="idx">
                <div 
                    @mouseenter="activeIndex = idx"
                    @click="confirmSelection()"
                    :class="{ 
                        'active-palette-item bg-accent-subtle-bg/50 text-text-main': activeIndex === idx,
                        'text-text-muted': activeIndex !== idx
                    }"
                    class="px-4 py-2.5 flex items-center justify-between cursor-pointer transition-colors text-sm hover:bg-surface-2/40"
                >
                    <div class="flex items-center min-w-0">
                        <!-- Type badge/icon -->
                        <span class="mr-3 flex-shrink-0 text-text-subtle">
                            <template x-if="item.type === 'verb'">
                                <span class="bg-surface-2 border border-border px-1 rounded-sm text-[10px] font-mono">verb</span>
                            </template>
                            <template x-if="item.type === 'nav'">
                                <span class="text-accent">◉</span>
                            </template>
                            <template x-if="item.type === 'recent'">
                                <span>✎</span>
                            </template>
                            <template x-if="item.type === 'capture'">
                                <span class="text-success">⚡</span>
                            </template>
                        </span>
                        <div class="truncate">
                            <div class="font-medium text-text-main text-xs uppercase tracking-wide" x-text="item.title"></div>
                            <div class="text-xxs text-text-subtle truncate" x-text="item.desc"></div>
                        </div>
                    </div>
                    
                    <!-- Action badge -->
                    <span x-show="activeIndex === idx" class="text-[10px] font-mono text-text-subtle bg-surface border border-border px-1.5 py-0.2 rounded-xs">
                        ENTER
                    </span>
                </div>
            </template>
        </div>

        <!-- Footer Cheatsheet -->
        <div class="bg-surface border-t border-border px-4 py-2 flex items-center justify-between text-xxs text-text-subtle">
            <div class="flex items-center space-x-3">
                <span><kbd class="bg-surface-2 px-1 border border-border rounded-xs">↑↓</kbd> Navigate</span>
                <span><kbd class="bg-surface-2 px-1 border border-border rounded-xs">Enter</kbd> Action</span>
                <span><kbd class="bg-surface-2 px-1 border border-border rounded-xs">⌘↵</kbd> Quick Capture</span>
            </div>
            <div>
                <span>Press <kbd class="bg-surface-2 px-1 border border-border rounded-xs">?</kbd> for shortcut list</span>
            </div>
        </div>
    </div>
</div>

<script>
window.commandPaletteComponent = function() {
    return {
        open: false,
        input: '',
        activeIndex: 0,
        
        pages: [
            { title: 'Go to Dashboard', url: '/dashboard', category: 'Navigation', icon: '◉' },
            { title: 'Go to Tasks', url: '/tasks', category: 'Navigation', icon: '☑' },
            { title: 'Go to Notes', url: '/notes', category: 'Navigation', icon: '✎' },
            { title: 'Go to Journal', url: '/journal', category: 'Navigation', icon: '◷' },
            { title: 'Go to Bookmarks', url: '/bookmarks', category: 'Navigation', icon: '⚲' },
            { title: 'Go to Learning Hub', url: '/learning', category: 'Navigation', icon: '▤' },
            { title: 'Go to Projects', url: '/projects', category: 'Navigation', icon: '❏' },
            { title: 'Go to Quotes', url: '/quotes', category: 'Navigation', icon: '❝' },
            { title: 'Go to Resources', url: '/resources', category: 'Navigation', icon: '◫' },
            { title: 'Go to Slipping Items', url: '/slipping', category: 'Navigation', icon: '⚠' },
            { title: 'Go to Settings', url: '/settings', category: 'Navigation', icon: '⚙' },
            { title: 'Go to Inbox', url: '/inbox', category: 'Navigation', icon: '📥' }
        ],
        
        recents: [
            { title: 'Laravel Optimization Notes', url: '/notes', type: 'note', desc: 'Performance tuning tips' },
            { title: 'Redis Streams Research', url: '/notes', type: 'note', desc: 'Pub/sub scaling architecture' },
            { title: 'AWS ECS Learning Path', url: '/learning', type: 'learning', desc: 'Progress: 45%' },
            { title: 'Docker Production Checklist', url: '/tasks', type: 'task', desc: 'Due: June 12' },
            { title: 'PostgreSQL Full Text Search', url: '/notes', type: 'note', desc: 'Configuring tsvector indexes' }
        ],

        verbs: [
            { name: 'task', desc: 'Create a new task', placeholder: 'task review PR #work due:tomorrow !high' },
            { name: 'note', desc: 'Create a new knowledge note', placeholder: 'note Redis Streams research #redis' },
            { name: 'journal', desc: 'Create today\'s reflection entry', placeholder: 'journal daily reflection' },
            { name: 'bookmark', desc: 'Save a bookmark link', placeholder: 'bookmark https://laravel.com' },
            { name: 'project', desc: 'Create a new container project', placeholder: 'project Personal Life OS' },
            { name: 'learning', desc: 'Start a new learning path', placeholder: 'learning AWS ECS deployment' }
        ],

        init() {
            window.addEventListener('keydown', e => {
                if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                    e.preventDefault();
                    this.toggle();
                }
                if (e.key === 'Escape' && this.open) {
                    this.close();
                }
            });
            window.addEventListener('open-palette', () => {
                this.show();
            });
        },

        toggle() {
            if (this.open) this.close();
            else this.show();
        },

        show() {
            this.open = true;
            this.input = '';
            this.activeIndex = 0;
            this.$nextTick(() => this.$refs.paletteInput.focus());
        },

        close() {
            this.open = false;
        },

        get parsedGrammar() {
            let text = this.input.trim();
            if (!text) return null;
            
            let firstWord = text.split(' ')[0].toLowerCase();
            let hasVerb = ['task', 'note', 'journal', 'bookmark', 'project', 'learning'].includes(firstWord);
            let verb = hasVerb ? firstWord : null;
            let remainder = hasVerb ? text.substring(firstWord.length).trim() : text;
            
            let tags = remainder.match(/#\w+/g) || [];
            let projects = remainder.match(/@\w+/g) || [];
            let priority = remainder.match(/!(high|medium|low|[1-4])/i);
            let due = remainder.match(/due:\S+/i);
            
            let cleanTitle = remainder
                .replace(/#\w+/g, '')
                .replace(/@\w+/g, '')
                .replace(/!(high|medium|low|[1-4])/gi, '')
                .replace(/due:\S+/gi, '')
                .replace(/\s+/g, ' ')
                .trim();
                
            return {
                verb: verb || 'smart capture',
                title: cleanTitle || 'Untitled',
                tags: tags.map(t => t.replace('#', '')),
                projects: projects.map(p => p.replace('@', '')),
                priority: priority ? priority[1] : null,
                due: due ? due[0].replace('due:', '') : null
            };
        },

        get results() {
            let q = this.input.toLowerCase().trim();
            if (!q) {
                let items = [];
                this.verbs.forEach(v => {
                    items.push({
                        type: 'verb',
                        title: v.name,
                        desc: v.desc,
                        action: () => { this.input = v.name + ' '; this.$refs.paletteInput.focus(); }
                    });
                });
                this.pages.slice(0, 4).forEach(p => {
                    items.push({
                        type: 'nav',
                        title: p.title,
                        desc: p.category,
                        action: () => { window.location.href = p.url; }
                    });
                });
                this.recents.forEach(r => {
                    items.push({
                        type: 'recent',
                        title: r.title,
                        desc: 'Recent ' + r.type + ' · ' + r.desc,
                        action: () => { window.location.href = r.url; }
                    });
                });
                return items;
            }

            let items = [];
            let parsed = this.parsedGrammar;
            
            if (['task', 'note', 'journal', 'bookmark', 'project', 'learning', 'quote', 'idea', 'resource'].includes(parsed.verb)) {
                items.push({
                    type: 'capture',
                    title: 'Capture ' + parsed.verb.toUpperCase(),
                    desc: 'Creates: ' + parsed.title,
                    action: () => {
                        this.sendCapture(this.input);
                    }
                });
            }

            this.pages.forEach(p => {
                if (p.title.toLowerCase().includes(q) || p.category.toLowerCase().includes(q)) {
                    items.push({
                        type: 'nav',
                        title: p.title,
                        desc: p.category,
                        action: () => { window.location.href = p.url; }
                    });
                }
            });

            this.recents.forEach(r => {
                if (r.title.toLowerCase().includes(q) || r.desc.toLowerCase().includes(q)) {
                    items.push({
                        type: 'recent',
                        title: r.title,
                        desc: 'Recent ' + r.type + ' · ' + r.desc,
                        action: () => { window.location.href = r.url; }
                    });
                }
            });

            if (items.length === 0 || !items.some(i => i.type === 'capture')) {
                items.unshift({
                    type: 'capture',
                    title: 'Quick Capture Task: ' + this.input,
                    desc: 'Press Enter to save to Inbox',
                    action: () => {
                        this.sendCapture('task ' + this.input);
                    }
                });
                items.unshift({
                    type: 'capture',
                    title: 'Quick Capture Note: ' + this.input,
                    desc: 'Press Enter to save to Inbox',
                    action: () => {
                        this.sendCapture('note ' + this.input);
                    }
                });
            }

            return items;
        },

        async sendCapture(raw) {
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrf = csrfMeta ? csrfMeta.getAttribute('content') : '';
            try {
                const res = await fetch('/capture', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ input: raw }),
                    credentials: 'same-origin',
                });
                if (!res.ok) {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: 'Capture failed (' + res.status + ')', action: null }
                    }));
                    return;
                }
                const data = await res.json();
                const title = data.title || raw;
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: {
                        message: (data.type || 'entry').toUpperCase() + ' captured: ' + (title.length > 30 ? title.substring(0,30) + '…' : title),
                        action: null,
                    }
                }));
                this.input = '';
                this.close();
            } catch (e) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: 'Capture error: ' + e.message, action: null }
                }));
            }
        },

        selectNext() {
            this.activeIndex = (this.activeIndex + 1) % this.results.length;
            this.scrollActiveIntoView();
        },

        selectPrev() {
            this.activeIndex = (this.activeIndex - 1 + this.results.length) % this.results.length;
            this.scrollActiveIntoView();
        },

        confirmSelection() {
            if (this.results[this.activeIndex]) {
                this.results[this.activeIndex].action();
            }
        },
        
        scrollActiveIntoView() {
            this.$nextTick(() => {
                let activeEl = this.$refs.resultsList.querySelector('.active-palette-item');
                if (activeEl) {
                    activeEl.scrollIntoView({ block: 'nearest' });
                }
            });
        }
    };
};
</script>
