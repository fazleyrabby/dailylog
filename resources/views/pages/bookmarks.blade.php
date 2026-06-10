@extends('layouts.app')

@section('title', 'Bookmarks')
@section('header_breadcrumbs', 'DAILYLOG // BOOKMARKS')

@section('content')
<div 
    x-data="bookmarksComponent({{ json_encode($bookmarks) }})"
    class="max-w-4xl mx-auto space-y-6"
>
    <!-- Header -->
    <x-ui.section-header title="Bookmarks Queue" badge="">
        <x-slot:badge>
            <span x-text="unreadCount"></span>
        </x-slot:badge>
    </x-ui.section-header>

    <!-- Add Bookmark Inline Form -->
    <div class="bg-surface border border-border rounded-sm p-4 text-xs">
        <h4 class="text-xxs font-bold text-text-subtle uppercase tracking-wider mb-2.5">Capture New Bookmark</h4>
        <div class="flex flex-col sm:flex-row gap-2">
            <div class="flex-grow">
                <input
                    type="url"
                    x-model="newUrl"
                    placeholder="https://example.com/blog-post"
                    class="w-full bg-transparent border border-border px-3 py-1.5 rounded-sm focus:outline-none focus:border-accent text-text-main"
                />
            </div>
            <div class="w-full sm:w-1/3">
                <input
                    type="text"
                    x-model="newTags"
                    placeholder="tags (comma-separated)"
                    class="w-full bg-transparent border border-border px-3 py-1.5 rounded-sm focus:outline-none focus:border-accent text-text-main"
                />
            </div>
            <x-ui.button variant="primary" @click="addBookmark()" class="font-bold cursor-pointer select-none">
                Add Bookmark
            </x-ui.button>
        </div>
    </div>

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
            <div class="bg-surface border border-border rounded-sm p-4 text-xs flex flex-col md:flex-row md:items-center justify-between gap-4 hover:border-accent/30 transition-colors">
                <!-- Main details and optional thumbnail -->
                <div class="flex items-start space-x-3.5 min-w-0 flex-grow">
                    <!-- Thumbnail preview -->
                    <template x-if="b.image">
                        <div class="w-16 h-16 sm:w-20 sm:h-20 flex-shrink-0 rounded-sm overflow-hidden border border-border bg-surface-2/40 hidden sm:block">
                            <img :src="b.image" class="w-full h-full object-cover" x-on:error="b.image = ''" />
                        </div>
                    </template>

                    <div class="space-y-1.5 min-w-0 flex-grow">
                        <div class="flex items-center space-x-2">
                            <!-- Favicon with fallback -->
                            <img x-show="b.favicon" :src="b.favicon" x-on:error="b.favicon = ''" class="h-4 w-4 rounded-sm object-contain flex-shrink-0" />
                            <span x-show="!b.favicon" class="text-type-bookmark flex-shrink-0">⚲</span>
                            
                            <a :href="b.url" target="_blank" class="font-bold text-accent hover:underline text-sm truncate" x-text="b.title"></a>
                        </div>
                        <a :href="b.url" target="_blank" class="text-text-subtle hover:text-text-main font-mono text-[10px] block" x-text="b.site"></a>
                        <p class="text-text-muted select-text leading-normal" x-text="b.desc || 'No description fetched yet'"></p>
                        
                        <div class="flex items-center space-x-2.5 pt-1 flex-wrap gap-y-1">
                            <template x-for="tag in b.tags">
                                <span class="bg-surface-2 border border-border px-1.5 py-0.2 rounded-full text-[9px] text-text-subtle font-mono font-medium">#<span x-text="tag"></span></span>
                            </template>
                            <span class="text-[10px] text-text-subtle font-mono" x-text="'Added: ' + b.added"></span>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex-shrink-0 flex md:flex-col md:items-end justify-start gap-2">
                    <template x-if="b.state === 'unread'">
                        <x-ui.button variant="secondary" size="sm" @click="markReviewed(b.id)">
                            Mark Reviewed
                        </x-ui.button>
                    </template>
                    <template x-if="b.state === 'reviewed'">
                        <span class="bg-success/5 text-success border border-success/20 text-[9px] font-mono font-bold px-1.5 py-0.5 rounded-full uppercase tracking-wider">Reviewed</span>
                    </template>
                    <x-ui.button variant="danger" size="sm" @click="deleteBookmark(b.id)">
                        Delete
                    </x-ui.button>
                </div>
            </div>
        </template>
    </div>
</div>

<script>
window.bookmarksComponent = function(initialBookmarks) {
    return {
        activeTab: 'unread',
        bookmarks: initialBookmarks,
        newUrl: '',
        newTags: '',

        get currentBookmarks() {
            return this.bookmarks.filter(b => b.state === this.activeTab);
        },

        get unreadCount() {
            return this.bookmarks.filter(b => b.state === 'unread').length;
        },

        markReviewed(id) {
            fetch(`/bookmarks/${id}/reviewed`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    let b = this.bookmarks.find(x => x.id === id);
                    if (b) {
                        b.state = 'reviewed';
                    }
                    window.dispatchEvent(new CustomEvent('show-toast', { 
                        detail: { message: 'Bookmark marked reviewed' }
                    }));
                }
            });
        },

        deleteBookmark(id) {
            if (!confirm('Are you sure you want to archive this bookmark?')) return;

            fetch(`/bookmarks/${id}`, {
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
                    this.bookmarks = this.bookmarks.filter(b => b.id !== id);
                    window.dispatchEvent(new CustomEvent('show-toast', { 
                        detail: { message: 'Bookmark archived successfully' }
                    }));
                }
            });
        },

        addBookmark() {
            if (!this.newUrl) return;

            let tagsArr = this.newTags.split(',').map(t => t.trim()).filter(t => t.length > 0);

            fetch('/bookmarks', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    url: this.newUrl,
                    tags: tagsArr
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.bookmark) {
                    this.bookmarks.unshift(data.bookmark);
                    this.newUrl = '';
                    this.newTags = '';
                    window.dispatchEvent(new CustomEvent('show-toast', { 
                        detail: { message: 'Bookmark added! Fetching metadata...' }
                    }));
                }
            });
        }
    };
};
</script>
@endsection
