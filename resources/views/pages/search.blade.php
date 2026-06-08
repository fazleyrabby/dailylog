@extends('layouts.app')

@section('title', 'Global Search')
@section('header_breadcrumbs', 'DAILYLOG // SEARCH')

@section('content')
<div 
    x-data="{
        query: '',
        selectedType: 'all',
        selectedProject: 'all',
        
        results: [
            { id: 10, title: 'Laravel Optimization Notes', type: 'note', project: 'DailyLOG', text: 'Optimizing Laravel 12 monoliths at personal scale. OPcache, config caching, FrankenPHP and Octane speedup tips.', tags: ['laravel', 'performance'], date: '2h ago' },
            { id: 11, title: 'PostgreSQL Full Text Search Configuration', type: 'note', project: 'DailyLOG', text: 'Using Postgres instead of Elasticsearch for simple search indexes. Dynamic tsvector configuration and GIN indexing.', tags: ['postgres', 'db'], date: 'Yesterday' },
            { id: 12, title: 'Redis Streams Pub/Sub Architecture', type: 'note', project: 'DailyLOG', text: 'Detailed research on how Redis Streams can act as a message broker for async jobs without overhead.', tags: ['redis', 'architecture'], date: '5h ago' },
            { id: 3, title: 'Review pull request for auth service rewrite', type: 'task', project: 'DailyLOG', text: 'Ensure secure hashing tokens are mapped inside auth service configs.', tags: ['security', 'auth'], date: 'Today' },
            { id: 13, title: 'Docker Container Security Checklist', type: 'note', project: 'DevOps', text: 'Security guidelines for production containers. Use non-root user execution, read-only root filesystems.', tags: ['docker', 'security'], date: '3d ago' }
        ],

        get filteredResults() {
            return this.results.filter(r => {
                let matchesQuery = r.title.toLowerCase().includes(this.query.toLowerCase()) || 
                                   r.text.toLowerCase().includes(this.query.toLowerCase());
                let matchesType = this.selectedType === 'all' || r.type === this.selectedType;
                let matchesProj = this.selectedProject === 'all' || r.project === this.selectedProject;
                return matchesQuery && matchesType && matchesProj;
            });
        }
    }"
    class="max-w-6xl mx-auto flex space-x-6 h-[calc(100vh-100px)] overflow-hidden"
>
    <!-- LEFT RAIL: Filters (Width 1/4) -->
    <div class="w-1/4 border-r border-border pr-6 space-y-5 flex-shrink-0 select-none overflow-y-auto">
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-text-subtle mb-2.5">Filter by Type</h3>
            <div class="space-y-1.5 text-xs">
                <template x-for="type in ['all', 'note', 'task', 'project', 'learning']">
                    <button 
                        @click="selectedType = type"
                        :class="selectedType === type ? 'bg-accent/15 border-accent text-accent font-semibold' : 'bg-surface border-border text-text-muted hover:text-text-main'"
                        class="w-full text-left px-3 py-2 border rounded-sm transition-colors cursor-pointer uppercase font-mono text-[10px]"
                        x-text="type"
                    ></button>
                </template>
            </div>
        </div>

        <div class="h-px bg-border"></div>

        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-text-subtle mb-2.5">Filter by Project</h3>
            <div class="space-y-1.5 text-xs">
                <template x-for="proj in ['all', 'DailyLOG', 'DevOps']">
                    <button 
                        @click="selectedProject = proj"
                        :class="selectedProject === proj ? 'bg-accent/15 border-accent text-accent font-semibold' : 'bg-surface border-border text-text-muted hover:text-text-main'"
                        class="w-full text-left px-3 py-2 border rounded-sm transition-colors cursor-pointer font-mono text-[10px]"
                        x-text="'@' + proj"
                    ></button>
                </template>
            </div>
        </div>
    </div>

    <!-- MAIN WINDOW: Results (Width 3/4) -->
    <div class="w-3/4 flex flex-col h-full bg-surface">
        <div class="pb-4 border-b border-border flex-shrink-0">
            <x-ui.search-input x-model="query" placeholder="Type keywords to search search index..." />
        </div>

        <!-- Ranked results list -->
        <div class="flex-grow overflow-y-auto py-4 divide-y divide-border pr-2">
            <template x-if="filteredResults.length === 0">
                <div class="py-12 text-center text-xs text-text-muted border border-dashed border-border rounded-sm">
                    No results found for that search query. Try widening the search parameters.
                </div>
            </template>
            
            <template x-for="res in filteredResults" :key="res.id">
                <div class="py-4 hover:bg-surface-2/20 transition-colors flex flex-col space-y-1 px-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2.5">
                            <span :class="{
                                'bg-type-note': res.type === 'note',
                                'bg-type-task': res.type === 'task'
                            }" class="h-2 w-2 rounded-full flex-shrink-0"></span>
                            <span class="bg-surface border border-border px-1.5 py-0.2 rounded-sm text-[8px] font-mono text-text-subtle uppercase" x-text="res.type"></span>
                            <h4 class="font-bold text-text-main text-sm" x-text="res.title"></h4>
                        </div>
                        <span class="text-[10px] text-text-subtle font-mono" x-text="res.date"></span>
                    </div>
                    
                    <p class="text-xs text-text-muted leading-relaxed font-serif-reading select-text" x-text="res.text"></p>
                    
                    <div class="flex items-center space-x-2 pt-1.5 flex-wrap">
                        <span class="text-[10px] font-mono text-accent" x-text="'@' + res.project"></span>
                        <template x-for="tag in res.tags">
                            <span class="bg-surface-2 border border-border px-1.5 py-0.2 rounded-full text-[9px] text-text-subtle font-mono">#<span x-text="tag"></span></span>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection
