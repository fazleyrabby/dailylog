@extends('layouts.app')

@section('title', 'Projects')
@section('header_breadcrumbs', 'DAILYLOG // PROJECTS')

@section('content')
<div 
    x-data="Object.assign(projectsComponent({{ json_encode($projects) }}), panelResizer({key:'projects', initial:350, min:280, max:600}))"
    x-init="initPanelResizer()"
    class="h-[calc(100vh-100px)] flex flex-col md:flex-row overflow-hidden border border-border rounded-sm bg-surface select-none"
    :class="resizing ? 'cursor-col-resize' : ''"
>
    <!-- MIDDLE COLUMN: Projects List & Search (Pane 2) -->
    <div
        :style="isMobile ? '' : 'width:' + panelWidth + 'px'"
        class="w-full md:flex-shrink-0 flex flex-col border-b md:border-b-0 md:border-r border-border bg-surface-2/10 select-text max-h-[40vh] md:max-h-full"
    >
        <!-- Search & New Project Header -->
        <div class="p-3 border-b border-border bg-surface space-y-3">
            <div class="flex items-center space-x-2">
                <div class="flex-grow">
                    <x-ui.search-input x-model="searchQuery" placeholder="Search projects..." />
                </div>
                <x-ui.button variant="primary" @click="openCreateModal()" class="h-8 font-semibold select-none cursor-pointer">
                    + New
                </x-ui.button>
            </div>
        </div>

        <!-- Project Filter Tabs -->
        <div class="border-b border-border bg-surface px-3 py-1 flex items-center justify-between">
            <div class="flex space-x-3">
                <template x-for="tab in ['active', 'paused', 'all']">
                    <button 
                        @click="activeTab = tab; selectFirstProjectOfTab()"
                        :class="activeTab === tab ? 'border-b-2 border-accent text-accent font-semibold' : 'text-text-muted hover:text-text-main border-b-2 border-transparent'"
                        class="pb-1 text-[10px] font-bold uppercase tracking-wider cursor-pointer transition-all focus:outline-none select-none"
                        x-text="tab"
                    ></button>
                </template>
            </div>
        </div>

        <!-- Projects Scroll List -->
        <div class="flex-grow overflow-y-auto divide-y divide-border">
            <template x-if="filteredProjects.length === 0">
                <div class="py-8 text-center text-xs text-text-muted">
                    No projects found.
                </div>
            </template>
            
            <template x-for="p in filteredProjects" :key="p.id">
                <div 
                    @click="selectedProjId = p.id"
                    :class="selectedProjId === p.id ? 'bg-accent-subtle-bg/30 text-text-main border-l-2 border-l-accent' : 'text-text-muted hover:bg-surface-2/30 border-l-2 border-l-transparent'"
                    class="p-3.5 cursor-pointer flex flex-col transition-all"
                >
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-xs uppercase tracking-wider text-text-main flex items-center">
                            <span class="h-2 w-2 rounded-full mr-2" :style="'background-color: ' + getThemeColor(p.color)"></span>
                            <span x-text="p.name"></span>
                        </span>
                        
                        <span :class="{
                            'bg-accent/15 text-accent border-accent/25': p.status === 'active',
                            'bg-surface-2 text-text-muted border-border': p.status === 'paused'
                        }" class="border text-[9px] px-1.5 py-0.2 rounded-full font-mono font-medium uppercase" x-text="p.status"></span>
                    </div>
                    <p class="text-xxs text-text-muted mt-2 line-clamp-1" x-text="p.desc || 'No description'"></p>

                    <!-- Mini progress indicator -->
                    <div class="mt-3 flex items-center justify-between text-[9px] text-text-subtle">
                        <span>Progress</span>
                        <span class="font-mono font-bold" x-text="p.progress + '%'"></span>
                    </div>
                    <div class="w-full bg-surface-2 h-0.5 rounded-full overflow-hidden mt-1 border border-border/50">
                        <div class="bg-accent h-full transition-all duration-300" :style="'width: ' + p.progress + '%'"></div>
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

    <!-- RIGHT COLUMN: Project Hub / Workspace (Pane 3) -->
    <div class="flex-grow flex flex-col h-full bg-surface overflow-hidden select-text min-w-0">
        <template x-if="activeProj.id">
            <div class="flex flex-col h-full">
                <!-- Header Actions -->
                <div class="px-4 py-2.5 border-b border-border bg-surface-2/10 flex items-center justify-between flex-shrink-0">
                    <div class="flex items-center space-x-2">
                        <span class="text-xs font-mono font-bold uppercase tracking-wider text-text-subtle">Project Inspector</span>
                        <span :class="{
                            'bg-accent/15 text-accent border-accent/25': activeProj.status === 'active',
                            'bg-surface-2 text-text-muted border-border': activeProj.status === 'paused'
                        }" class="border text-[9px] px-1.5 py-0.5 rounded-full font-mono font-medium uppercase" x-text="activeProj.status"></span>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <x-ui.button variant="danger" size="sm" @click="deleteActiveProject()">
                            Archive Project
                        </x-ui.button>
                    </div>
                </div>

                <!-- Inspector Workspace Body -->
                <div class="flex-grow p-6 overflow-y-auto space-y-6 max-w-3xl">
                    <!-- Project Title and Metadata -->
                    <div class="space-y-3">
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-text-subtle block mb-1">Project Name</label>
                            <input 
                                type="text" 
                                x-model="activeProj.name" 
                                @blur="saveProjectField('name', activeProj.name)"
                                @keydown.enter="$el.blur()"
                                class="w-full text-base font-bold bg-transparent border-0 border-b border-border pb-2 focus:ring-0 focus:border-accent focus:outline-none text-text-main"
                                placeholder="Project Name"
                            />
                        </div>

                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-text-subtle block mb-1">Description</label>
                            <textarea 
                                x-model="activeProj.desc" 
                                @blur="saveProjectField('description', activeProj.desc)"
                                rows="2"
                                class="w-full bg-transparent border-0 border-b border-border pb-2 focus:ring-0 focus:border-accent focus:outline-none text-text-main text-xs resize-none"
                                placeholder="Add a project description..."
                            ></textarea>
                            <span class="text-[10px] text-text-subtle">Edits save automatically on blur or Enter.</span>
                        </div>
                    </div>

                    <!-- Config Attributes Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 border-t border-border pt-4">
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-text-subtle block mb-1.5">Theme Color</label>
                            <select 
                                x-model="activeProj.color" 
                                @change="saveProjectField('color', activeProj.color)"
                                class="w-full bg-surface border border-border px-2 py-1 rounded-sm focus:outline-none focus:border-accent text-text-main text-xs"
                            >
                                <option value="orange">Orange</option>
                                <option value="blue">Blue</option>
                                <option value="emerald">Emerald</option>
                                <option value="violet">Violet</option>
                                <option value="stone">Stone</option>
                                <option value="rose">Rose</option>
                                <option value="cyan">Cyan</option>
                                <option value="amber">Amber</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-text-subtle block mb-1.5">Status</label>
                            <select 
                                x-model="activeProj.status" 
                                @change="saveProjectField('status', activeProj.status)"
                                class="w-full bg-surface border border-border px-2 py-1 rounded-sm focus:outline-none focus:border-accent text-text-main text-xs"
                            >
                                <option value="active">Active</option>
                                <option value="paused">Paused</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-text-subtle block mb-1.5">Progress</label>
                            <div class="flex items-center space-x-2 mt-1">
                                <div class="flex-grow bg-surface-2 h-2 rounded-full overflow-hidden border border-border">
                                    <div class="bg-accent h-full transition-all duration-300" :style="'width: ' + activeProj.progress + '%'"></div>
                                </div>
                                <span class="font-mono text-xs text-text-main font-bold" x-text="activeProj.progress + '%'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Project Connected Workspace Elements -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 border-t border-border pt-6">
                        <!-- Open Tasks -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-bold text-text-subtle uppercase tracking-wider border-b border-border/60 pb-1">Open Tasks</h4>
                            <div class="space-y-1.5">
                                <template x-if="activeProj.tasks.length === 0">
                                    <p class="text-xxs text-text-subtle italic">No related open tasks.</p>
                                </template>
                                <template x-for="t in activeProj.tasks" :key="t.id">
                                    <div class="flex items-start space-x-2 text-xs py-1">
                                        <span class="text-accent mt-0.5">•</span>
                                        <span class="text-text-main font-medium leading-normal" x-text="t.title"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Linked Notes -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-bold text-text-subtle uppercase tracking-wider border-b border-border/60 pb-1">Linked Notes</h4>
                            <div class="space-y-2">
                                <template x-if="activeProj.notes.length === 0">
                                    <p class="text-xxs text-text-subtle italic">No linked notes.</p>
                                </template>
                                <template x-for="n in activeProj.notes" :key="n.id">
                                    <a href="/notes" class="block p-2 bg-surface-2/30 border border-border/60 rounded-sm text-xs flex justify-between items-center hover:bg-surface-2 transition-colors">
                                        <span class="font-medium text-text-main truncate" x-text="n.title"></span>
                                        <span class="text-[9px] text-text-subtle font-mono flex-shrink-0 ml-2" x-text="n.updated"></span>
                                    </a>
                                </template>
                            </div>
                        </div>

                        <!-- Activity Timeline -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-bold text-text-subtle uppercase tracking-wider border-b border-border/60 pb-1">Activity Timeline</h4>
                            <div class="relative pl-3 border-l border-border space-y-3">
                                <template x-if="activeProj.activity.length === 0">
                                    <p class="text-xxs text-text-subtle italic ml-2">No recorded activities.</p>
                                </template>
                                <template x-for="(act, index) in activeProj.activity" :key="index">
                                    <div class="relative text-xs">
                                        <!-- Timeline dot indicator -->
                                        <span class="absolute -left-[15px] top-1.5 h-2 w-2 rounded-full bg-accent border border-surface"></span>
                                        <div class="font-medium text-text-main leading-normal" x-text="act.event"></div>
                                        <span class="text-[9px] text-text-subtle font-mono block mt-0.5" x-text="act.date"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Project Financials Section -->
                    <div class="border-t border-border pt-6 space-y-4">
                        <div class="flex items-center justify-between border-b border-border/60 pb-1">
                            <h4 class="text-xs font-bold text-text-subtle uppercase tracking-wider">Project Financials</h4>
                            <span class="text-[9px] font-mono text-text-muted">// linked wallets & recent activity</span>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Linked Wallets -->
                            <div class="space-y-3">
                                <h5 class="text-xxs font-bold text-text-muted uppercase tracking-wider">Linked Wallets</h5>
                                <div class="space-y-2">
                                    <template x-if="!activeProj.financials || activeProj.financials.wallets.length === 0">
                                        <p class="text-xxs text-text-subtle italic">No wallets linked to this project.</p>
                                    </template>
                                    <template x-for="w in (activeProj.financials ? activeProj.financials.wallets : [])" :key="w.id">
                                        <div class="p-2.5 bg-surface-2/30 border border-border/60 rounded-sm flex justify-between items-center">
                                            <div>
                                                <span class="text-xs font-semibold text-text-main" x-text="w.title"></span>
                                                <span class="text-[9px] font-bold font-mono px-1.5 py-0.2 rounded-xs uppercase tracking-wide border border-border bg-surface-2 text-text-muted ml-2" x-text="w.type"></span>
                                            </div>
                                            <span class="font-mono text-xs font-bold text-text-main">
                                                <span x-text="w.balance.toFixed(2)"></span> <span class="text-[9px] font-normal text-text-subtle" x-text="w.currency"></span>
                                            </span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Recent Financial Transactions -->
                            <div class="space-y-3">
                                <h5 class="text-xxs font-bold text-text-muted uppercase tracking-wider">Recent Transactions</h5>
                                <div class="space-y-2">
                                    <template x-if="!activeProj.financials || activeProj.financials.transactions.length === 0">
                                        <p class="text-xxs text-text-subtle italic">No recent financial transactions.</p>
                                    </template>
                                    <template x-for="tx in (activeProj.financials ? activeProj.financials.transactions : [])" :key="tx.id">
                                        <div class="p-2 bg-surface-2/30 border border-border/60 rounded-sm text-xs flex justify-between items-center">
                                            <div class="flex flex-col min-w-0">
                                                <div class="flex items-center space-x-1.5">
                                                    <span :class="{
                                                        'bg-success/5 text-success border-success/20': tx.type === 'income',
                                                        'bg-danger/5 text-danger border-danger/20': tx.type === 'expense',
                                                        'bg-info/5 text-info border-info/20': tx.type === 'transfer'
                                                    }" class="border text-[8px] px-1 py-0.2 rounded-xs font-bold font-mono uppercase" x-text="tx.type"></span>
                                                    <span class="font-medium text-text-main truncate" x-text="tx.description || 'No description'"></span>
                                                </div>
                                                <span class="text-[9px] text-text-subtle mt-0.5" x-text="tx.wallet_title + (tx.target_wallet_title ? ' → ' + tx.target_wallet_title : '') + ' • ' + tx.occurred_on"></span>
                                            </div>
                                            <span :class="{
                                                'text-success': tx.type === 'income',
                                                'text-danger': tx.type === 'expense',
                                                'text-text-main': tx.type === 'transfer'
                                            }" class="font-mono text-xs font-bold whitespace-nowrap ml-2">
                                                <span x-text="(tx.type === 'income' ? '+' : (tx.type === 'expense' ? '-' : '')) + tx.amount.toFixed(2)"></span>
                                                <span class="text-[8px] font-normal text-text-subtle" x-text="tx.currency"></span>
                                            </span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <template x-if="!activeProj.id">
            <div class="flex-grow flex items-center justify-center text-xs text-text-muted">
                Select a project from the list to view its workspace.
            </div>
        </template>
    </div>

    <!-- Configure Project Modal (Creation Only) -->
    <x-ui.modal name="project-modal" maxWidth="md">
        <x-slot:title>
            <span>Create Project</span>
        </x-slot:title>
        
        <div class="space-y-4">
            <div>
                <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block mb-1">Project Name</label>
                <input 
                    type="text" 
                    x-model="modalData.name" 
                    class="w-full bg-transparent border border-border px-3 py-1.5 rounded-sm focus:outline-none focus:border-accent text-text-main text-xs"
                    placeholder="e.g. DailyLOG"
                />
            </div>
            <div>
                <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block mb-1">Description</label>
                <textarea 
                    x-model="modalData.description" 
                    rows="2"
                    class="w-full bg-transparent border border-border px-3 py-1.5 rounded-sm focus:outline-none focus:border-accent text-text-main text-xs resize-none"
                    placeholder="Brief description of project goals"
                ></textarea>
            </div>
            <div>
                <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block mb-1">Theme Color</label>
                <select 
                    x-model="modalData.color" 
                    class="w-full bg-surface border border-border px-3 py-1.5 rounded-sm focus:outline-none focus:border-accent text-text-main text-xs"
                >
                    <option value="orange">Orange</option>
                    <option value="blue">Blue</option>
                    <option value="emerald">Emerald</option>
                    <option value="violet">Violet</option>
                    <option value="stone">Stone</option>
                    <option value="rose">Rose</option>
                    <option value="cyan">Cyan</option>
                    <option value="amber">Amber</option>
                </select>
            </div>
        </div>

        <x-slot:footer>
            <div class="flex-grow"></div>
            <x-ui.button variant="secondary" @click="closeModal()" class="font-bold cursor-pointer">
                Cancel
            </x-ui.button>
            <x-ui.button variant="primary" @click="saveProject()" class="font-bold cursor-pointer">
                Create
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>

<script>
window.projectsComponent = function(initialProjects) {
    return {
        selectedProjId: initialProjects.length > 0 ? initialProjects[0].id : null,
        projects: initialProjects,
        searchQuery: '',
        activeTab: 'active',
        modalMode: 'create',
        modalData: {
            id: null,
            name: '',
            description: '',
            color: 'orange',
            status: 'active'
        },

        get filteredProjects() {
            return this.projects.filter(p => {
                let matchesSearch = p.name.toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                                    p.desc.toLowerCase().includes(this.searchQuery.toLowerCase());
                let matchesTab = this.activeTab === 'all' || p.status === this.activeTab;
                return matchesSearch && matchesTab;
            });
        },

        get activeProj() {
            return this.projects.find(p => p.id === this.selectedProjId) || {
                id: null,
                name: '',
                desc: '',
                status: 'paused',
                color: 'orange',
                progress: 0,
                tasks: [],
                notes: [],
                activity: [],
                financials: {
                    wallets: [],
                    transactions: []
                }
            };
        },

        selectFirstProjectOfTab() {
            let projects = this.filteredProjects;
            if (projects.length > 0) {
                this.selectedProjId = projects[0].id;
            } else {
                this.selectedProjId = null;
            }
        },

        getThemeColor(color) {
            const colors = {
                orange: 'var(--color-accent)',
                blue: '#3b82f6',
                emerald: '#10b981',
                violet: '#8b5cf6',
                stone: '#78716c',
                rose: '#f43f5e',
                cyan: '#06b6d4',
                amber: '#f59e0b'
            };
            return colors[color] || colors.orange;
        },

        openCreateModal() {
            this.modalMode = 'create';
            this.modalData = {
                id: null,
                name: '',
                description: '',
                color: 'orange',
                status: 'active'
            };
            this.$dispatch('open-modal', { name: 'project-modal' });
        },

        closeModal() {
            this.$dispatch('close-modal', { name: 'project-modal' });
        },

        saveProject() {
            if (!this.modalData.name) return;

            fetch('/projects', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    name: this.modalData.name,
                    description: this.modalData.description,
                    color: this.modalData.color
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.project) {
                    this.projects.push(data.project);
                    this.selectedProjId = data.project.id;
                    this.closeModal();
                    window.dispatchEvent(new CustomEvent('show-toast', { 
                        detail: { message: 'Project created successfully' }
                    }));
                }
            });
        },

        saveProjectField(field, value) {
            if (!this.activeProj.id) return;

            let payload = {
                name: this.activeProj.name,
                description: field === 'description' ? value : this.activeProj.desc,
                color: this.activeProj.color,
                status: this.activeProj.status
            };

            fetch(`/projects/${this.activeProj.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.project) {
                    let idx = this.projects.findIndex(p => p.id === data.project.id);
                    if (idx !== -1) {
                        // Keep frontend-only fields like tasks, notes, activity intact on update
                        const original = this.projects[idx];
                        this.projects[idx] = Object.assign({}, original, data.project);
                    }
                    window.dispatchEvent(new CustomEvent('show-toast', { 
                        detail: { message: 'Project settings updated' }
                    }));
                }
            });
        },

        deleteActiveProject() {
            if (!confirm('Are you sure you want to archive this project?')) return;

            fetch(`/projects/${this.selectedProjId}`, {
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
                    let id = this.selectedProjId;
                    this.projects = this.projects.filter(p => p.id !== id);
                    this.selectFirstProjectOfTab();
                    window.dispatchEvent(new CustomEvent('show-toast', { 
                        detail: { message: 'Project archived successfully' }
                    }));
                }
            });
        }
    };
};
</script>
@endsection
