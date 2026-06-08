@extends('layouts.app')

@section('title', 'Bookmarks')
@section('header_breadcrumbs', 'DAILYLOG // BOOKMARKS')

@section('content')
<div 
    x-data="{
        activeTab: 'unread',
        bookmarks: [
            { id: 22, title: 'PostHog Developer Dashboard UI Design Best Practices', url: 'https://posthog.com/blog/ui-design-principles', site: 'posthog.com', desc: 'Learn how PostHog designs high-density developer interfaces. Detailed notes on border usage over shadow elements.', tags: ['design', 'ux'], added: '60 days ago', state: 'unread' },
            { id: 23, title: 'Redis Streams Deep-dive Guide', url: 'https://redis.io/docs/manual/data-types/streams', site: 'redis.io', desc: 'The complete guide to understanding Redis streams logic. Coverage on consumer groups and data structures.', tags: ['redis', 'research'], added: '30 days ago', state: 'unread' },
            { id: 24, title: 'Vite v8 Release Notes', url: 'https://vite.dev/blog/vite-8', site: 'vite.dev', desc: 'Read about the performance improvements and Tailwind v4 integrations in Vite v8.', tags: ['vite', 'frontend'], added: 'Yesterday', state: 'reviewed' }
        ],

        get currentBookmarks() {
            return this.bookmarks.filter(b => b.state === this.activeTab);
        },

        markReviewed(id) {
            let b = this.bookmarks.find(x => x.id === id);
            if (b) {
                b.state = 'reviewed';
                window.dispatchEvent(new CustomEvent('show-toast', { 
                    detail: { message: 'Bookmark marked reviewed · Removed from slipping snapshot', action: 'Undo' }
                }));
            }
        }
    }"
    class="max-w-4xl mx-auto space-y-6"
>
    <!-- Header -->
    <x-ui.section-header title="Bookmarks Queue" badge="3" />

    <!-- Tabs selector -->
    <div class="border-b border-border flex items-center justify-between pb-px">
        <div class="flex space-x-4">
            <button 
                @click="activeTab = 'unread'"
                :class="activeTab === 'unread' ? 'border-b-2 border-accent text-accent font-semibold' : 'text-text-muted hover:text-text-main border-b-2 border-transparent'"
                class="pb-2 text-xs font-semibold uppercase tracking-wider cursor-pointer transition-all focus:outline-none select-none"
            >
                Unread Queue
            </button>
            <button 
                @click="activeTab = 'reviewed'"
                :class="activeTab === 'reviewed' ? 'border-b-2 border-accent text-accent font-semibold' : 'text-text-muted hover:text-text-main border-b-2 border-transparent'"
                class="pb-2 text-xs font-semibold uppercase tracking-wider cursor-pointer transition-all focus:outline-none select-none"
            >
                Reviewed Archive
            </button>
        </div>
    </div>

    <!-- Bookmark Cards Grid -->
    <div class="space-y-4">
        <template x-if="currentBookmarks.length === 0">
            <div class="py-12 text-center text-xs text-text-muted border border-dashed border-border rounded-sm bg-surface">
                No bookmarks found in this view. Paste links in Cmd-K to capture them instantly.
            </div>
        </template>
        
        <template x-for="b in currentBookmarks" :key="b.id">
            <div class="bg-surface border border-border rounded-sm p-4 text-xs flex justify-between space-x-4 hover:border-accent/30 transition-colors">
                <div class="space-y-1.5 min-w-0 flex-grow">
                    <div class="flex items-center space-x-2">
                        <span class="text-type-bookmark">⚲</span>
                        <a :href="b.url" target="_blank" class="font-bold text-accent hover:underline text-sm truncate" x-text="b.title"></a>
                    </div>
                    <a :href="b.url" target="_blank" class="text-text-subtle hover:text-text-main font-mono text-[10px] block" x-text="b.site"></a>
                    <p class="text-text-muted select-text leading-normal" x-text="b.desc"></p>
                    
                    <div class="flex items-center space-x-2.5 pt-1 flex-wrap gap-y-1">
                        <template x-for="tag in b.tags">
                            <span class="bg-surface-2 border border-border px-1.5 py-0.2 rounded-full text-[9px] text-text-subtle font-mono">#<span x-text="tag"></span></span>
                        </template>
                        <span class="text-[10px] text-text-subtle font-mono" x-text="'Added: ' + b.added"></span>
                    </div>
                </div>
                
                <div class="flex-shrink-0 flex flex-col justify-between items-end">
                    <template x-if="b.state === 'unread'">
                        <x-ui.button variant="secondary" @click="markReviewed(b.id)" class="font-bold cursor-pointer select-none">
                            Mark Reviewed
                        </x-ui.button>
                    </template>
                    <template x-if="b.state === 'reviewed'">
                        <span class="bg-success/5 text-success border border-success/20 text-[9px] font-mono font-bold px-1.5 py-0.2 rounded-full uppercase">Reviewed</span>
                    </template>
                </div>
            </div>
        </template>
    </div>
</div>
@endsection
