@extends('layouts.app')

@section('title', 'Tasks')
@section('header_breadcrumbs', 'DAILYLOG // TASKS')

@section('content')
<div 
    x-data="tasksComponent()"
    class="max-w-4xl mx-auto space-y-6"
>
    <!-- Tasks Header -->
    <x-ui.section-header title="Tasks Container" badge="12">
        <x-slot name="actions">
            <!-- Priority Filter Chips -->
            <div class="flex items-center space-x-1">
                <span class="text-[10px] text-text-subtle font-semibold mr-1 font-mono uppercase">Filter Priority:</span>
                <span class="bg-danger/10 text-danger border border-danger/20 text-[9px] font-bold px-1.5 py-0.5 rounded cursor-pointer">High</span>
                <span class="bg-warning/10 text-warning border border-warning/20 text-[9px] font-bold px-1.5 py-0.5 rounded cursor-pointer">Medium</span>
                <span class="bg-success/10 text-success border border-success/20 text-[9px] font-bold px-1.5 py-0.5 rounded cursor-pointer">Low</span>
            </div>
        </x-slot>
    </x-ui.section-header>

    <!-- Task Navigation Tabs -->
    <div class="border-b border-border flex items-center justify-between pb-px">
        <div class="flex space-x-4">
            <template x-for="tab in ['inbox', 'today', 'upcoming', 'completed']">
                <button 
                    @click="activeTab = tab"
                    :class="activeTab === tab ? 'border-b-2 border-accent text-accent font-semibold' : 'text-text-muted hover:text-text-main border-b-2 border-transparent'"
                    class="pb-2 text-xs font-semibold uppercase tracking-wider cursor-pointer transition-all focus:outline-none select-none"
                    x-text="tab"
                ></button>
            </template>
        </div>
        
        <kbd class="text-[10px] text-text-subtle font-mono select-none">TAB to cycle views</kbd>
    </div>

    <!-- Quick Task Creation Input -->
    <form @submit.prevent="addTask()" class="flex space-x-2">
        <div class="flex-grow">
            <x-ui.input 
                x-model="newTaskTitle" 
                placeholder="Type new task and press Enter (e.g. Write backups script @DailyLOG #ops)" 
            />
        </div>
        <x-ui.button type="submit" variant="primary" class="font-bold select-none cursor-pointer">
            Add Task
        </x-ui.button>
    </form>

    <!-- High Density List Rows -->
    <x-ui.card>
        <div class="divide-y divide-border">
            <template x-if="currentTasks.length === 0">
                <div class="py-8 text-center text-xs text-text-muted">
                    No tasks found in this view. Use the input box above or <kbd class="bg-surface-2 px-1 border rounded text-text-muted font-mono font-semibold">c</kbd> to add.
                </div>
            </template>
            
            <template x-for="task in currentTasks" :key="task.id">
                <div class="flex items-center justify-between py-2.5 px-1 hover:bg-surface-2/20 transition-colors text-xs group">
                    <div class="flex items-center space-x-3 min-w-0">
                        <input 
                            type="checkbox" 
                            :checked="task.completed"
                            @click="toggleTask(task.id)"
                            class="rounded-sm border-border bg-surface text-accent focus:ring-accent cursor-pointer"
                        />
                        <span 
                            :class="task.completed ? 'line-through text-text-subtle' : 'text-text-main font-medium'"
                            class="truncate" 
                            x-text="task.title"
                        ></span>
                    </div>
                    
                    <div class="flex items-center space-x-3 flex-shrink-0">
                        <template x-if="task.tags">
                            <div class="flex items-center space-x-1">
                                <template x-for="tag in task.tags">
                                    <span class="text-[9px] bg-surface-2 border border-border px-1.5 py-0.2 rounded-full text-text-subtle">#<span x-text="tag"></span></span>
                                </template>
                            </div>
                        </template>
                        
                        <span :class="{
                            'bg-danger/5 text-danger border-danger/20': task.priority === 'high',
                            'bg-warning/5 text-warning border-warning/20': task.priority === 'medium',
                            'bg-success/5 text-success border-success/20': task.priority === 'low'
                        }" class="border px-1.5 py-0.2 rounded-full text-[9px] uppercase font-semibold font-mono" x-text="task.priority"></span>
                        
                        <span class="text-[10px] font-mono text-text-subtle bg-surface border border-border px-1 rounded-sm" x-text="'@' + task.project"></span>
                        
                        <!-- Hover Action triggers -->
                        <button 
                            @click="deleteTask(task.id)"
                            class="text-text-subtle hover:text-danger cursor-pointer opacity-0 group-hover:opacity-100 transition-opacity focus:opacity-100"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </x-ui.card>
</div>

<script>
window.tasksComponent = function() {
    return {
        activeTab: 'today',
        newTaskTitle: '',
        
        tasks: [
            { id: 1, title: 'Docker production config review', priority: 'medium', project: 'DevOps', tags: ['docker', 'ops'], due: null, completed: false, tab: 'inbox' },
            { id: 2, title: 'Buy SSL cert for client sandbox', priority: 'low', project: 'Freelancing', tags: ['security'], due: null, completed: false, tab: 'inbox' },
            
            { id: 3, title: 'Review pull request for auth service rewrite', priority: 'high', project: 'DailyLOG', tags: ['security', 'auth'], due: 'Today', completed: false, tab: 'today' },
            { id: 4, title: 'Setup Redis cluster local configurations', priority: 'high', project: 'DailyLOG', tags: ['redis', 'scaling'], due: 'Today', completed: false, tab: 'today' },
            { id: 5, title: 'Write daily log reflection', priority: 'low', project: 'Self', tags: ['journal'], due: 'Today', completed: true, tab: 'today' },
            
            { id: 6, title: 'Optimize PostgreSQL full text search tsvector indexes', priority: 'medium', project: 'DailyLOG', tags: ['postgres', 'db'], due: 'Tomorrow', completed: false, tab: 'upcoming' },
            { id: 7, title: 'Deploy staging app onto AWS ECS Cluster', priority: 'high', project: 'DevOps', tags: ['aws', 'ecs'], due: 'In 3 days', completed: false, tab: 'upcoming' },
            { id: 8, title: 'Setup backups script with AWS S3 integration', priority: 'low', project: 'DailyLOG', tags: ['backups'], due: 'In 5 days', completed: false, tab: 'upcoming' },
            
            { id: 9, title: 'Configure Vite with Tailwind v4 engine', priority: 'medium', project: 'DailyLOG', tags: ['tailwind', 'frontend'], due: 'Yesterday', completed: true, tab: 'completed' }
        ],

        get currentTasks() {
            return this.tasks.filter(t => t.tab === this.activeTab);
        },

        addTask() {
            if (!this.newTaskTitle.trim()) return;
            let id = Date.now();
            this.tasks.push({
                id: id,
                title: this.newTaskTitle,
                priority: 'medium',
                project: 'DailyLOG',
                tags: ['quick-capture'],
                due: this.activeTab === 'today' ? 'Today' : null,
                completed: this.activeTab === 'completed',
                tab: this.activeTab
            });
            window.dispatchEvent(new CustomEvent('show-toast', { 
                detail: { message: 'Task created in ' + this.activeTab.toUpperCase(), action: 'Undo' }
            }));
            this.newTaskTitle = '';
        },

        toggleTask(id) {
            let t = this.tasks.find(x => x.id === id);
            if (t) {
                t.completed = !t.completed;
                if (t.completed && t.tab !== 'completed') {
                    setTimeout(() => {
                        t.tab = 'completed';
                        window.dispatchEvent(new CustomEvent('show-toast', { 
                            detail: { message: 'Task completed · Moved to history', action: 'Undo' }
                        }));
                    }, 500);
                } else if (!t.completed && t.tab === 'completed') {
                    t.tab = t.due ? 'today' : 'inbox';
                }
            }
        },

        deleteTask(id) {
            let t = this.tasks.find(x => x.id === id);
            if (t) {
                this.tasks = this.tasks.filter(x => x.id !== id);
                window.dispatchEvent(new CustomEvent('show-toast', { 
                    detail: { message: 'Archived: ' + (t.title.length > 15 ? t.title.substring(0, 15) + '...' : t.title), action: 'Undo' }
                }));
            }
        }
    };
};
</script>
@endsection
