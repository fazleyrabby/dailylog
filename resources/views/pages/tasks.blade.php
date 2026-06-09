@extends('layouts.app')

@section('title', 'Tasks')
@section('header_breadcrumbs', 'DAILYLOG // TASKS')

@section('content')
<div 
    x-data="tasksComponent({{ json_encode($tasks) }})"
    class="max-w-4xl mx-auto space-y-6"
>
    <!-- Tasks Header -->
    <x-ui.section-header title="Tasks Container" badge="{{ collect($tasks)->except('completed')->flatten(1)->count() }}">
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
                    <div class="flex items-center space-x-3 min-w-0 flex-grow mr-4">
                        <input 
                            type="checkbox" 
                            :checked="task.completed"
                            @click="toggleTask(task.id)"
                            class="rounded-sm border-border bg-surface text-accent focus:ring-accent cursor-pointer"
                        />
                        <template x-if="editingTaskId === task.id">
                            <input 
                                type="text" 
                                x-model="editingTitle" 
                                @keydown.enter="saveTaskTitle(task.id)" 
                                @keydown.escape="cancelEdit()"
                                @blur="saveTaskTitle(task.id)"
                                class="border border-border bg-surface text-text-main text-xs px-2 py-1 rounded focus:outline-none focus:ring-1 focus:ring-accent w-full"
                                x-init="$nextTick(() => $el.focus())"
                            />
                        </template>
                        <template x-if="editingTaskId !== task.id">
                            <span 
                                @dblclick="startEdit(task)"
                                :class="task.completed ? 'line-through text-text-subtle' : 'text-text-main font-medium'"
                                class="truncate cursor-text flex-grow" 
                                x-text="task.title"
                            ></span>
                        </template>
                    </div>
                    
                    <div class="flex items-center space-x-3 flex-shrink-0">
                        <template x-if="task.tags && task.tags.length > 0">
                            <div class="flex items-center space-x-1">
                                <template x-for="tag in task.tags">
                                    <span class="text-[9px] bg-surface-2 border border-border px-1.5 py-0.2 rounded-full text-text-subtle">#<span x-text="tag"></span></span>
                                </template>
                            </div>
                        </template>
                        
                        <span 
                            @click="cyclePriority(task)"
                            :class="{
                                'bg-danger/5 text-danger border-danger/20 hover:bg-danger/10': task.priority === 'high',
                                'bg-warning/5 text-warning border-warning/20 hover:bg-warning/10': task.priority === 'medium',
                                'bg-success/5 text-success border-success/20 hover:bg-success/10': task.priority === 'low'
                            }" 
                            class="border px-1.5 py-0.2 rounded-full text-[9px] uppercase font-semibold font-mono cursor-pointer select-none transition-colors" 
                            x-text="task.priority"
                            title="Click to cycle priority"
                        ></span>
                        
                        <template x-if="task.due">
                            <span class="text-[10px] font-mono text-text-subtle bg-surface border border-border px-1 rounded-sm" x-text="'due: ' + task.due"></span>
                        </template>
                        
                        <template x-if="task.project && task.project !== 'None'">
                            <span class="text-[10px] font-mono text-text-subtle bg-surface border border-border px-1 rounded-sm" x-text="'@' + task.project"></span>
                        </template>
                        
                        <!-- Hover Action triggers -->
                        <div class="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity focus-within:opacity-100">
                            <button 
                                @click="startEdit(task)"
                                class="text-text-subtle hover:text-accent cursor-pointer focus:outline-none"
                                title="Edit Title (or Double-Click)"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                            </button>
                            <button 
                                @click="deleteTask(task.id)"
                                class="text-text-subtle hover:text-danger cursor-pointer focus:outline-none"
                                title="Archive Task"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </x-ui.card>
</div>

<script>
window.tasksComponent = function(initialTasks) {
    return {
        activeTab: 'today',
        newTaskTitle: '',
        editingTaskId: null,
        editingTitle: '',
        
        tasks: Object.keys(initialTasks).flatMap(tab => 
            initialTasks[tab].map(task => ({ ...task, tab }))
        ),

        startEdit(task) {
            this.editingTaskId = task.id;
            this.editingTitle = task.title;
        },

        cancelEdit() {
            this.editingTaskId = null;
            this.editingTitle = '';
        },

        saveTaskTitle(id) {
            if (!this.editingTitle.trim()) {
                this.cancelEdit();
                return;
            }
            
            let t = this.tasks.find(x => x.id === id);
            if (t && t.title === this.editingTitle.trim()) {
                this.cancelEdit();
                return;
            }

            fetch(`/tasks/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ title: this.editingTitle })
            })
            .then(res => res.json())
            .then(data => {
                if (data.task) {
                    if (t) {
                        t.title = data.task.title;
                    }
                    window.dispatchEvent(new CustomEvent('show-toast', { 
                        detail: { message: 'Task updated successfully' }
                    }));
                }
                this.cancelEdit();
            })
            .catch(() => this.cancelEdit());
        },

        cyclePriority(task) {
            const priorities = ['low', 'medium', 'high'];
            let nextIndex = (priorities.indexOf(task.priority) + 1) % priorities.length;
            let nextPriority = priorities[nextIndex];

            fetch(`/tasks/${task.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ priority: nextPriority })
            })
            .then(res => res.json())
            .then(data => {
                if (data.task) {
                    task.priority = data.task.priority;
                    window.dispatchEvent(new CustomEvent('show-toast', { 
                        detail: { message: `Priority set to ${task.priority.toUpperCase()}` }
                    }));
                }
            });
        },

        get currentTasks() {
            return this.tasks.filter(t => t.tab === this.activeTab);
        },

        addTask() {
            if (!this.newTaskTitle.trim()) return;
            
            let titleToSend = this.newTaskTitle.trim();
            if (!/due:/i.test(titleToSend)) {
                if (this.activeTab === 'today') {
                    titleToSend += ' due:today';
                } else if (this.activeTab === 'upcoming') {
                    titleToSend += ' due:tomorrow';
                }
            }
            
            fetch('/tasks', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ title: titleToSend })
            })
            .then(res => res.json())
            .then(data => {
                if (data.task) {
                    const task = data.task;
                    task.tab = task.completed ? 'completed' : (task.due ? (task.due === 'Today' ? 'today' : 'upcoming') : 'inbox');
                    this.tasks.push(task);
                    window.dispatchEvent(new CustomEvent('show-toast', { 
                        detail: { message: 'Task created successfully' }
                    }));
                    this.newTaskTitle = '';
                }
            });
        },

        toggleTask(id) {
            fetch(`/tasks/${id}/toggle`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.task) {
                    let t = this.tasks.find(x => x.id === id);
                    if (t) {
                        t.completed = data.task.completed;
                        if (t.completed) {
                            setTimeout(() => {
                                t.tab = 'completed';
                                window.dispatchEvent(new CustomEvent('show-toast', { 
                                    detail: { message: 'Task completed · Moved to history' }
                                }));
                            }, 500);
                        } else {
                            t.tab = data.task.due ? (data.task.due === 'Today' ? 'today' : 'upcoming') : 'inbox';
                            window.dispatchEvent(new CustomEvent('show-toast', { 
                                detail: { message: 'Task marked incomplete' }
                            }));
                        }
                    }
                }
            });
        },

        deleteTask(id) {
            let t = this.tasks.find(x => x.id === id);
            if (!t) return;

            fetch(`/tasks/${id}`, {
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
                    this.tasks = this.tasks.filter(x => x.id !== id);
                    window.dispatchEvent(new CustomEvent('show-toast', { 
                        detail: { message: 'Archived: ' + (t.title.length > 15 ? t.title.substring(0, 15) + '...' : t.title) }
                    }));
                }
            });
        }
    };
};
</script>
@endsection
