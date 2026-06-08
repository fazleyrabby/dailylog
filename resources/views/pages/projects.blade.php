@extends('layouts.app')

@section('title', 'Projects')
@section('header_breadcrumbs', 'DAILYLOG // PROJECTS')

@section('content')
<div 
    x-data="{
        selectedProjId: 15,
        projects: [
            { 
                id: 15, 
                name: 'DailyLOG', 
                desc: 'Personal Life OS single source of truth dashboard', 
                status: 'active', 
                color: 'orange',
                progress: 80,
                tasks: [
                    { title: 'Review pull request for auth service rewrite', completed: false, priority: 'high' },
                    { title: 'Setup Redis cluster local configurations', completed: false, priority: 'high' },
                    { title: 'Optimize PostgreSQL full text search tsvector indexes', completed: false, priority: 'medium' }
                ],
                notes: [
                    { title: 'Laravel Optimization Notes', updated: '2 hours ago' },
                    { title: 'PostgreSQL Full Text Search Configuration', updated: 'Yesterday' }
                ],
                activity: [
                    { event: 'Note added: PostgreSQL Full Text Search', date: 'Yesterday' },
                    { event: 'Task completed: Configure Vite with Tailwind v4 engine', date: '2 days ago' }
                ]
            },
            { 
                id: 16, 
                name: 'DevOps', 
                desc: 'Infrastructure configurations and deployment setups', 
                status: 'active', 
                color: 'blue',
                progress: 50,
                tasks: [
                    { title: 'Docker production config review', completed: false, priority: 'medium' },
                    { title: 'Deploy staging app onto AWS ECS Cluster', completed: false, priority: 'high' }
                ],
                notes: [
                    { title: 'Docker Container Security Checklist', updated: '3 days ago' },
                    { title: 'AWS ECS Deployment Guide', updated: '5 days ago' }
                ],
                activity: [
                    { event: 'Note added: AWS ECS Deployment Guide', date: '5 days ago' }
                ]
            },
            { 
                id: 17, 
                name: 'Freelancing', 
                desc: 'Client works and miscellaneous freelance projects', 
                status: 'paused', 
                color: 'emerald',
                progress: 10,
                tasks: [
                    { title: 'Buy SSL cert for client sandbox', completed: false, priority: 'low' }
                ],
                notes: [],
                activity: []
            }
        ],

        get activeProj() {
            return this.projects.find(p => p.id === this.selectedProjId) || this.projects[0];
        }
    }"
    class="max-w-6xl mx-auto space-y-6"
>
    <!-- Header -->
    <x-ui.section-header title="Projects Connector" badge="3" />

    <!-- Project Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <template x-for="p in projects" :key="p.id">
            <div 
                @click="selectedProjId = p.id"
                :class="selectedProjId === p.id ? 'border-accent bg-accent-subtle-bg/10 ring-1 ring-accent' : 'border-border bg-surface hover:bg-surface-2/40'"
                class="border rounded-sm p-4 cursor-pointer transition-all flex flex-col justify-between h-36"
            >
                <div>
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-xs uppercase tracking-wider text-text-main flex items-center">
                            <span class="h-2 w-2 rounded-full mr-2" :class="'bg-' + p.color + '-500'" style="background-color: var(--color-accent)"></span>
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
                    <div class="w-full bg-surface-2 h-1 rounded-full overflow-hidden border border-border">
                        <div class="bg-accent h-full transition-all duration-300" :style="'width: ' + p.progress + '%'"></div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Active Project Connective Hub -->
    <div class="border border-border rounded-sm bg-surface p-5 space-y-6">
        <!-- Hub Title -->
        <div class="border-b border-border pb-3 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-text-main uppercase tracking-wider flex items-center">
                    <span class="mr-2">❏</span> Workspace Container: <span x-text="activeProj.name" class="text-accent ml-1.5"></span>
                </h3>
                <p class="text-xxs text-text-muted mt-1" x-text="activeProj.desc"></p>
            </div>
            
            <x-ui.button variant="secondary" class="font-bold cursor-pointer">
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
                    <template x-for="t in activeProj.tasks">
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
                    <template x-for="n in activeProj.notes">
                        <div class="p-2 bg-surface-2/30 border border-border/60 rounded-sm text-xs flex justify-between items-center hover:bg-surface-2 transition-colors">
                            <span class="font-medium text-text-main truncate" x-text="n.title"></span>
                            <span class="text-[9px] text-text-subtle font-mono flex-shrink-0 ml-2" x-text="n.updated"></span>
                        </div>
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
                    <template x-for="act in activeProj.activity">
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
</div>
@endsection
