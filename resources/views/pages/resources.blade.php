@extends('layouts.app')

@section('title', 'Resources')
@section('header_breadcrumbs', 'DAILYLOG // RESOURCES')

@section('content')
<div 
    x-data="{
        filterType: 'all',
        resources: [
            { id: 25, title: 'Designing Data-Intensive Applications', type: 'book', author: 'Martin Kleppmann', state: 'done', rating: 5, url: 'https://dataintensive.net', tags: ['architecture', 'databases'] },
            { id: 26, title: 'Laravel 12 Deep Dive Video Series', type: 'video', author: 'Laracasts', state: 'consuming', rating: 4, url: 'https://laracasts.com/series/laravel-12-deep-dive', tags: ['laravel'] },
            { id: 27, title: 'Refactoring UI', type: 'book', author: 'Adam Wathan & Steve Schoger', state: 'done', rating: 5, url: 'https://refactoringui.com', tags: ['design', 'ux'] }
        ],

        get filteredResources() {
            if (this.filterType === 'all') return this.resources;
            return this.resources.filter(r => r.type === this.filterType);
        }
    }"
    class="max-w-4xl mx-auto space-y-6"
>
    <!-- Header -->
    <x-ui.section-header title="Reference Library Resources" badge="3">
        <x-slot name="actions">
            <div class="flex space-x-1.5 text-xs">
                <button @click="filterType = 'all'" :class="filterType === 'all' ? 'bg-accent/15 border-accent/20 text-accent font-semibold' : 'bg-surface-2 border-border text-text-muted hover:text-text-main'" class="px-2 py-0.5 border rounded-full text-xxs cursor-pointer">All</button>
                <button @click="filterType = 'book'" :class="filterType === 'book' ? 'bg-accent/15 border-accent/20 text-accent font-semibold' : 'bg-surface-2 border-border text-text-muted hover:text-text-main'" class="px-2 py-0.5 border rounded-full text-xxs cursor-pointer">Books</button>
                <button @click="filterType = 'video'" :class="filterType === 'video' ? 'bg-accent/15 border-accent/20 text-accent font-semibold' : 'bg-surface-2 border-border text-text-muted hover:text-text-main'" class="px-2 py-0.5 border rounded-full text-xxs cursor-pointer">Videos</button>
            </div>
        </x-slot>
    </x-ui.section-header>

    <!-- Resources Listing -->
    <div class="space-y-4">
        <template x-for="r in filteredResources" :key="r.id">
            <div class="bg-surface border border-border rounded-sm p-4 text-xs flex justify-between items-center hover:border-accent/30 transition-colors">
                <div class="space-y-1.5 min-w-0">
                    <div class="flex items-center space-x-2">
                        <span class="bg-surface-2 border border-border px-1.5 py-0.2 rounded-sm text-[9px] uppercase font-mono text-text-muted" x-text="r.type"></span>
                        <a :href="r.url" target="_blank" class="font-bold text-text-main hover:text-accent text-sm truncate" x-text="r.title"></a>
                    </div>
                    <div class="text-[10px] text-text-muted">Author: <span class="font-semibold" x-text="r.author"></span></div>
                    
                    <div class="flex items-center space-x-2 pt-1 flex-wrap">
                        <template x-for="tag in r.tags">
                            <span class="bg-surface-2 border border-border px-1.5 py-0.2 rounded-full text-[9px] text-text-subtle font-mono">#<span x-text="tag"></span></span>
                        </template>
                        
                        <!-- Consume state badge -->
                        <span :class="{
                            'bg-success/5 text-success border-success/20': r.state === 'done',
                            'bg-accent/5 text-accent border-accent/20': r.state === 'consuming'
                        }" class="border text-[8px] px-1.5 py-0.2 rounded-full font-mono font-bold uppercase tracking-wider" x-text="r.state"></span>
                    </div>
                </div>

                <!-- Star Rating Display -->
                <div class="flex-shrink-0 flex items-center space-x-0.5 text-warning text-base font-semibold">
                    <template x-for="star in Array.from({length: r.rating})">
                        <span>★</span>
                    </template>
                </div>
            </div>
        </template>
    </div>
</div>
@endsection
