@extends('layouts.app')

@section('title', 'Projects')
@section('header_breadcrumbs', 'DAILYLOG // PROJECTS')

@section('content')
<div 
    x-data="projectsComponent({{ json_encode($projects) }})"
    class="max-w-6xl mx-auto space-y-6"
>
    <!-- Header -->
    <x-ui.section-header title="Projects Connector" badge="">
        <x-slot:badge>
            <span x-text="projects.length"></span>
        </x-slot:badge>
        <x-slot:actions>
            <x-ui.button variant="primary" @click="openCreateModal()" class="font-bold cursor-pointer select-none">
                + New Project
            </x-ui.button>
        </x-slot:actions>
    </x-ui.section-header>

    <!-- Project Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <template x-if="projects.length === 0">
            <div class="md:col-span-3 py-12 text-center text-xs text-text-muted border border-dashed border-border rounded-sm bg-surface">
                No active projects found. Click "+ New Project" to create one.
            </div>
        </template>
        <template x-for="p in projects" :key="p.id">
            <div 
                @click="selectedProjId = p.id"
                :class="selectedProjId === p.id ? 'border-accent bg-accent-subtle-bg/10 ring-1 ring-accent' : 'border-border bg-surface hover:bg-surface-2/40'"
                class="border rounded-sm p-4 cursor-pointer transition-all flex flex-col justify-between h-36"
            >
                <div>
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-xs uppercase tracking-wider text-text-main flex items-center">
                            <span class="h-2 w-2 rounded-full mr-2" :style="'background-color: ' + getThemeColor(p.color)"></span>
                            <span x-text="p.name"></span>
                        </span>
                        
                        <span :class="{
                            'bg-accent/10 text-accent border-accent/20': p.status === 'active',
                            'bg-surface-2 text-text-muted border-border': p.status === 'paused'
                        }" class="border text-[9px] px-1.5 py-0.2 rounded-full font-mono font-medium uppercase" x-text="p.status"></span>
                    </div>
                    <p class="text-xxs text-text-muted mt-2 line-clamp-2" x-text="p.desc"></p>
                </div>
                
                <!-- Progress bar -->
                <div>
                    <div class="flex items-center justify-between text-[10px] text-text-subtle mb-1">
                        <span>Progress</span>
                        <span class="font-mono" x-text="p.progress + '%'"></span>
                    </div>
                    <div class="w-full bg-surface-2 h-1 rounded-full overflow-hidden mt-1 border border-border">
                        <div class="bg-accent h-full transition-all duration-300" :style="'width: ' + p.progress + '%'"></div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Active Project Connective Hub -->
    <template x-if="projects.length > 0">
        <div class="border border-border rounded-sm bg-surface p-5 space-y-6">
            <!-- Hub Title -->
            <div class="border-b border-border pb-3 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-text-main uppercase tracking-wider flex items-center">
                        <span class="mr-2">❏</span> Workspace Container: <span x-text="activeProj.name" class="text-accent ml-1.5 font-bold"></span>
                    </h3>
                    <p class="text-xxs text-text-muted mt-1" x-text="activeProj.desc"></p>
                </div>
                
                <x-ui.button variant="secondary" @click="openEditModal()" class="font-bold cursor-pointer">
                    Configure Project
                </x-ui.button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Related Tasks (1/3) -->
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

                <!-- Related Notes (1/3) -->
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

                <!-- Activity Feed (1/3) -->
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
        </div>
    </template>

    <!-- Configure Project Modal -->
    <x-ui.modal name="project-modal" maxWidth="md">
        <x-slot:title>
            <span x-text="modalMode === 'create' ? 'Create Project' : 'Configure Project'"></span>
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
            <template x-if="modalMode === 'edit'">
                <div>
                    <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block mb-1">Status</label>
                    <select 
                        x-model="modalData.status" 
                        class="w-full bg-surface border border-border px-3 py-1.5 rounded-sm focus:outline-none focus:border-accent text-text-main text-xs"
                    >
                        <option value="active">Active</option>
                        <option value="paused">Paused</option>
                    </select>
                </div>
            </template>
        </div>

        <x-slot:footer>
            <template x-if="modalMode === 'edit'">
                <button 
                    @click="deleteActiveProject()" 
                    class="h-8 px-3.5 bg-surface border border-danger hover:bg-danger/10 text-danger text-xxs font-bold rounded-sm flex items-center space-x-1 cursor-pointer select-none"
                >
                    Archive Project
                </button>
            </template>
            <div class="flex-grow"></div>
            <x-ui.button variant="secondary" @click="closeModal()" class="font-bold cursor-pointer">
                Cancel
            </x-ui.button>
            <x-ui.button variant="primary" @click="saveProject()" class="font-bold cursor-pointer">
                Save
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>

<script>
window.projectsComponent = function(initialProjects) {
    return {
        selectedProjId: initialProjects.length > 0 ? initialProjects[0].id : null,
        projects: initialProjects,
        modalMode: 'create',
        modalData: {
            id: null,
            name: '',
            description: '',
            color: 'orange',
            status: 'active'
        },

        get activeProj() {
            return this.projects.find(p => p.id === this.selectedProjId) || {
                id: null,
                name: 'No project selected',
                desc: 'Select a project to view details.',
                status: 'paused',
                color: 'orange',
                progress: 0,
                tasks: [],
                notes: [],
                activity: []
            };
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

        openEditModal() {
            this.modalMode = 'edit';
            this.modalData = {
                id: this.activeProj.id,
                name: this.activeProj.name,
                description: this.activeProj.desc,
                color: this.activeProj.color,
                status: this.activeProj.status
            };
            this.$dispatch('open-modal', { name: 'project-modal' });
        },

        closeModal() {
            this.$dispatch('close-modal', { name: 'project-modal' });
        },

        saveProject() {
            if (!this.modalData.name) return;

            let method = this.modalMode === 'create' ? 'POST' : 'PUT';
            let url = this.modalMode === 'create' ? '/projects' : `/projects/${this.modalData.id}`;

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    name: this.modalData.name,
                    description: this.modalData.description,
                    color: this.modalData.color,
                    status: this.modalData.status
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.project) {
                    if (this.modalMode === 'create') {
                        this.projects.push(data.project);
                        this.selectedProjId = data.project.id;
                    } else {
                        let idx = this.projects.findIndex(p => p.id === data.project.id);
                        if (idx !== -1) {
                            this.projects[idx] = data.project;
                        }
                    }
                    this.closeModal();
                    window.dispatchEvent(new CustomEvent('show-toast', { 
                        detail: { message: this.modalMode === 'create' ? 'Project created successfully' : 'Project configured successfully' }
                    }));
                }
            });
        },

        deleteActiveProject() {
            if (!confirm('Are you sure you want to archive this project?')) return;

            fetch(`/projects/${this.modalData.id}`, {
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
                    let id = this.modalData.id;
                    this.projects = this.projects.filter(p => p.id !== id);
                    this.selectedProjId = this.projects.length > 0 ? this.projects[0].id : null;
                    this.closeModal();
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
