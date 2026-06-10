@extends('layouts.app')

@section('title', 'Tasks')
@section('header_breadcrumbs', 'DAILYLOG // TASKS')

@section('content')
<div
    x-data="Object.assign(tasksComponent({{ json_encode($tasks) }}), panelResizer({key:'tasks', initial:420, min:300, max:640}))"
    x-init="initPanelResizer()"
    class="h-[calc(100vh-100px)] flex flex-col md:flex-row overflow-hidden border border-border rounded-sm bg-surface select-none"
    :class="resizing ? 'cursor-col-resize' : ''"
>
    <!-- MIDDLE COLUMN: Tasks List & Search (Pane 2) -->
    <div
        :style="panelStyle"
        class="w-full md:flex-shrink-0 flex flex-col border-b md:border-b-0 md:border-r border-border bg-surface-2/10 select-text max-h-[40vh] md:max-h-full"
    >
        <!-- Search & Quick Add Header -->
        <div class="p-3 border-b border-border bg-surface space-y-3">
            <div class="flex items-center space-x-2">
                <div class="flex-grow">
                    <x-ui.search-input x-model="searchQuery" placeholder="Search tasks..." />
                </div>
            </div>
            
            <!-- Quick Task Creation Input -->
            <form @submit.prevent="addTask()" class="flex space-x-2">
                <div class="flex-grow">
                    <input 
                        type="text"
                        x-model="newTaskTitle" 
                        placeholder="New task... (e.g. Write script @DailyLOG #ops due:today)" 
                        class="w-full bg-surface-2 border border-border rounded-sm px-2.5 py-1 text-xs focus:outline-none focus:border-accent text-text-main placeholder-text-muted"
                    />
                </div>
                <button type="submit" class="bg-accent hover:bg-accent-hover text-white px-2.5 py-1 rounded-sm text-xs font-semibold cursor-pointer transition-colors">
                    Add
                </button>
            </form>
        </div>

        <!-- Task Navigation Tabs -->
        <div class="border-b border-border bg-surface px-3 py-1 flex items-center justify-between">
            <div class="flex space-x-3">
                <template x-for="tab in ['inbox', 'today', 'upcoming', 'completed']">
                    <button 
                        @click="activeTab = tab; selectFirstTaskOfTab()"
                        :class="activeTab === tab ? 'border-b-2 border-accent text-accent font-semibold' : 'text-text-muted hover:text-text-main border-b-2 border-transparent'"
                        class="pb-1 text-[10px] font-bold uppercase tracking-wider cursor-pointer transition-all focus:outline-none select-none"
                        x-text="tab"
                    ></button>
                </template>
            </div>
            <kbd class="text-[9px] text-text-subtle font-mono select-none">TAB cycles</kbd>
        </div>

        <!-- Tasks Scroll List -->
        <div class="flex-grow overflow-y-auto divide-y divide-border">
            <template x-if="filteredTasks.length === 0">
                <div class="py-8 text-center text-xs text-text-muted">
                    No tasks found in this view.
                </div>
            </template>
            
            <template x-for="task in filteredTasks" :key="task.id">
                <div 
                    @click="selectedTaskId = task.id"
                    :class="selectedTaskId === task.id ? 'bg-accent-subtle-bg/30 text-text-main' : 'text-text-muted hover:bg-surface-2/30'"
                    class="p-3 cursor-pointer flex flex-col transition-all"
                >
                    <div class="flex items-start justify-between space-x-2">
                        <div class="flex items-start space-x-2.5 min-w-0">
                            <input 
                                type="checkbox" 
                                :checked="task.completed"
                                @click.stop="toggleTask(task.id)"
                                class="mt-0.5 rounded-sm border-border bg-surface text-accent focus:ring-accent cursor-pointer flex-shrink-0"
                            />
                            <span 
                                :class="task.completed ? 'line-through text-text-subtle' : 'text-text-main font-medium'"
                                class="text-xs break-words" 
                                x-text="task.title"
                            ></span>
                        </div>
                        
                        <span 
                            :class="{
                                'bg-danger/10 text-danger border-danger/20': task.priority === 'high',
                                'bg-warning/10 text-warning border-warning/20': task.priority === 'medium',
                                'bg-success/10 text-success border-success/20': task.priority === 'low'
                            }" 
                            class="border px-1.5 py-0.2 rounded-full text-[9px] uppercase font-semibold font-mono flex-shrink-0" 
                            x-text="task.priority"
                        ></span>
                    </div>

                    <!-- Task Metadata Row -->
                    <div class="flex items-center justify-between mt-2 text-[9px] text-text-subtle">
                        <div class="flex items-center space-x-1.5 overflow-hidden">
                            <template x-if="task.project && task.project !== 'None'">
                                <span class="font-mono bg-surface border border-border px-1 rounded-sm text-text-muted" x-text="'@' + task.project"></span>
                            </template>
                            <template x-for="tag in task.tags">
                                <span class="font-mono text-text-muted">#<span x-text="tag"></span></span>
                            </template>
                        </div>
                        <template x-if="task.due">
                            <span class="font-mono text-text-muted" x-text="'due: ' + task.due"></span>
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

    <!-- RIGHT COLUMN: Task Workspace & Detail Inspector (Pane 3) -->
    <div class="flex-grow flex flex-col h-full bg-surface overflow-hidden select-text min-w-0">
        <template x-if="activeTask.id">
            <div class="flex flex-col h-full">
                <!-- Header Actions -->
                <div class="px-4 py-2.5 border-b border-border bg-surface-2/10 flex items-center justify-between flex-shrink-0">
                    <div class="flex items-center space-x-2">
                        <span class="text-xxs font-mono font-bold uppercase tracking-wider text-text-subtle">Task Details</span>
                        <span class="text-xxs font-mono bg-surface border border-border text-text-muted px-1.5 py-0.5 rounded-sm" x-text="activeTask.project ? '@' + activeTask.project : '@None'"></span>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <x-ui.button variant="danger" size="sm" @click="deleteTask(activeTask.id)">
                            Archive Task
                        </x-ui.button>
                    </div>
                </div>

                <!-- Detail Inspector Body -->
                <div class="flex-grow p-6 overflow-y-auto space-y-6 max-w-xl">
                    <!-- Title Input Area -->
                    <div class="space-y-1.5">
                        <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block">Task Title</label>
                        <input 
                            type="text" 
                            x-model="activeTask.title" 
                            @blur="saveTaskTitle(activeTask.id)"
                            @keydown.enter="$el.blur()"
                            class="w-full text-sm font-semibold bg-transparent border-0 border-b border-border pb-2 focus:ring-0 focus:border-accent focus:outline-none text-text-main"
                            placeholder="Task title"
                        />
                        <p class="text-xxs text-text-subtle">Edits save automatically on blur or Enter.</p>
                    </div>

                    <!-- Quick Attributes Grid -->
                    <div class="grid grid-cols-2 gap-4 pt-2">
                        <div>
                            <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block mb-1">Priority</label>
                            <div class="flex space-x-1">
                                <template x-for="p in ['low', 'medium', 'high']">
                                    <button 
                                        @click="setTaskPriority(activeTask, p)"
                                        :class="{
                                            'bg-danger/15 text-danger border-danger/30 font-bold': activeTask.priority === p && p === 'high',
                                            'bg-warning/15 text-warning border-warning/30 font-bold': activeTask.priority === p && p === 'medium',
                                            'bg-success/15 text-success border-success/30 font-bold': activeTask.priority === p && p === 'low',
                                            'bg-surface border-border text-text-muted hover:text-text-main': activeTask.priority !== p
                                        }"
                                        class="px-2.5 py-1 text-xxs border rounded-sm cursor-pointer select-none transition-all"
                                        x-text="p"
                                    ></button>
                                </template>
                            </div>
                        </div>

                        <div>
                            <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block mb-1">Due Date</label>
                            <span class="inline-block px-2.5 py-1 text-xxs border border-border bg-surface-2/40 text-text-main rounded-sm font-mono" x-text="activeTask.due || 'No due date'"></span>
                        </div>
                    </div>

                    <!-- Associated Project & Tags -->
                    <div class="grid grid-cols-2 gap-4 border-t border-border pt-4">
                        <div>
                            <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block mb-1">Project Container</label>
                            <span class="inline-block px-2.5 py-1 text-xxs border border-border bg-surface-2/40 text-text-main rounded-sm font-mono" x-text="activeTask.project"></span>
                        </div>

                        <div>
                            <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block mb-1">Tags</label>
                            <div class="flex flex-wrap gap-1">
                                <template x-if="!activeTask.tags || activeTask.tags.length === 0">
                                    <span class="text-xxs text-text-subtle">No tags</span>
                                </template>
                                <template x-for="tag in activeTask.tags" :key="tag">
                                    <span class="bg-surface-2 border border-border text-[9px] px-2 py-0.5 rounded-sm text-text-subtle font-mono">#<span x-text="tag"></span></span>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Status Display -->
                    <div class="border-t border-border pt-4">
                        <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block mb-2">Status</label>
                        <button 
                            @click="toggleTask(activeTask.id)"
                            :class="activeTask.completed ? 'bg-success/10 border-success/30 text-success' : 'bg-surface border-border text-text-main'"
                            class="flex items-center space-x-2 px-3 py-1.5 border rounded-sm text-xxs font-semibold cursor-pointer select-none transition-colors"
                        >
                            <span x-text="activeTask.completed ? '✓ Completed' : '☐ Active'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
        
        <template x-if="!activeTask.id">
            <div class="flex-grow flex flex-col items-center justify-center p-6 text-center">
                <span class="text-2xl mb-2 text-text-muted">☑</span>
                <h3 class="text-xs font-bold text-text-main uppercase tracking-wider">No Task Selected</h3>
                <p class="text-xs text-text-subtle mt-1">Select a task from the list or add a new one.</p>
            </div>
        </template>
    </div>
</div>

<script>
window.tasksComponent = function(initialTasks) {
    return {
        activeTab: 'today',
        newTaskTitle: '',
        searchQuery: '',
        selectedTaskId: null,
        
        tasks: Object.keys(initialTasks).flatMap(tab => 
            initialTasks[tab].map(task => ({ ...task, tab }))
        ),

        init() {
            this.selectFirstTaskOfTab();
            // Bind keyboard shortcut to cycle tabs
            window.addEventListener('keydown', e => {
                if (e.key === 'Tab' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
                    e.preventDefault();
                    const tabs = ['inbox', 'today', 'upcoming', 'completed'];
                    let nextIndex = (tabs.indexOf(this.activeTab) + 1) % tabs.length;
                    this.activeTab = tabs[nextIndex];
                    this.selectFirstTaskOfTab();
                }
            });
        },

        selectFirstTaskOfTab() {
            this.$nextTick(() => {
                const list = this.filteredTasks;
                if (list.length > 0) {
                    this.selectedTaskId = list[0].id;
                } else {
                    this.selectedTaskId = null;
                }
            });
        },

        get filteredTasks() {
            return this.tasks.filter(t => {
                const matchesTab = t.tab === this.activeTab;
                const matchesSearch = t.title.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                                      (t.project && t.project.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
                                      (t.tags && t.tags.some(tag => tag.toLowerCase().includes(this.searchQuery.toLowerCase())));
                return matchesTab && matchesSearch;
            });
        },

        get activeTask() {
            return this.tasks.find(x => x.id === this.selectedTaskId) || {
                id: null,
                title: '',
                priority: 'low',
                project: 'None',
                tags: [],
                due: '',
                completed: false
            };
        },

        saveTaskTitle(id) {
            const t = this.tasks.find(x => x.id === id);
            if (!t || !t.title.trim()) return;

            fetch(`/tasks/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ title: t.title })
            })
            .then(res => res.json())
            .then(data => {
                if (data.task) {
                    t.title = data.task.title;
                    window.dispatchEvent(new CustomEvent('show-toast', { 
                        detail: { message: 'Task title updated' }
                    }));
                }
            });
        },

        setTaskPriority(task, priority) {
            fetch(`/tasks/${task.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ priority: priority })
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
                    this.selectedTaskId = task.id;
                    window.dispatchEvent(new CustomEvent('show-toast', { 
                        detail: { message: 'Task created' }
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
                                if (this.selectedTaskId === id && this.activeTab !== 'completed') {
                                    this.selectFirstTaskOfTab();
                                }
                                window.dispatchEvent(new CustomEvent('show-toast', { 
                                    detail: { message: 'Task completed' }
                                }));
                            }, 500);
                        } else {
                            t.tab = data.task.due ? (data.task.due === 'Today' ? 'today' : 'upcoming') : 'inbox';
                            if (this.selectedTaskId === id && this.activeTab !== t.tab) {
                                this.selectFirstTaskOfTab();
                            }
                            window.dispatchEvent(new CustomEvent('show-toast', { 
                                detail: { message: 'Task marked active' }
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
                    this.selectFirstTaskOfTab();
                    window.dispatchEvent(new CustomEvent('show-toast', { 
                        detail: { message: 'Task archived' }
                    }));
                }
            });
        }
    };
};
</script>
@endsection
