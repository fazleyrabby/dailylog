@extends('layouts.app')

@section('title', 'Resources')
@section('header_breadcrumbs', 'DAILYLOG // RESOURCES')

@section('content')
<div 
    x-data="resourcesComponent({{ json_encode($resources) }})"
    class="max-w-4xl mx-auto space-y-6"
>
    <!-- Header -->
    <x-ui.section-header title="Reference Library Resources" badge="">
        <x-slot:badge>
            <span x-text="resources.length"></span>
        </x-slot:badge>
        
        <x-slot name="actions">
            <!-- Filter Pills -->
            <div class="flex flex-wrap gap-1.5 text-xs">
                <template x-for="typeOpt in typeFilters" :key="typeOpt.value">
                    <button 
                        @click="filterType = typeOpt.value" 
                        :class="filterType === typeOpt.value ? 'bg-accent/15 border-accent/20 text-accent font-semibold' : 'bg-surface-2 border-border text-text-muted hover:text-text-main'" 
                        class="px-2 py-0.5 border rounded-full text-xxs cursor-pointer transition-all uppercase tracking-wider font-mono font-bold"
                        x-text="typeOpt.label"
                    ></button>
                </template>
            </div>
        </x-slot>
    </x-ui.section-header>

    <!-- Controls Row -->
    <div class="flex flex-col sm:flex-row gap-3 items-center justify-between">
        <!-- Search -->
        <div class="relative w-full sm:w-80">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-text-subtle">
                <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </span>
            <input 
                type="text" 
                x-model="searchQuery"
                placeholder="Search resources, authors, tags..."
                class="w-full bg-surface border border-border pl-9 pr-4 py-1.5 rounded-sm focus:outline-none focus:border-accent text-text-main text-xs"
            />
        </div>

        <!-- Add Button -->
        <x-ui.button variant="primary" @click="openCreateModal()" class="font-bold w-full sm:w-auto">
            + Add Resource
        </x-ui.button>
    </div>

    <!-- Resources Listing -->
    <div class="space-y-4">
        <template x-for="r in filteredResources" :key="r.id">
            <div class="bg-surface border border-border rounded-sm p-4 text-xs flex justify-between items-center hover:border-accent/30 transition-colors group relative">
                <div class="space-y-1.5 min-w-0 pr-8">
                    <div class="flex items-center space-x-2">
                        <span class="bg-surface-2 border border-border px-1.5 py-0.2 rounded-sm text-[9px] uppercase font-mono text-text-muted" x-text="r.type"></span>
                        
                        <template x-if="r.url">
                            <a :href="r.url" target="_blank" class="font-bold text-text-main hover:text-accent text-sm truncate" x-text="r.title"></a>
                        </template>
                        <template x-if="!r.url">
                            <span class="font-bold text-text-main text-sm truncate" x-text="r.title"></span>
                        </template>
                    </div>
                    
                    <div x-show="r.author" class="text-[10px] text-text-muted">Author: <span class="font-semibold" x-text="r.author"></span></div>
                    
                    <div class="flex items-center space-x-2 pt-1 flex-wrap gap-y-1">
                        <template x-for="tag in r.tags">
                            <a :href="'/search?tag[]=' + encodeURIComponent(tag)" class="bg-surface-2 hover:bg-surface border border-border px-1.5 py-0.2 rounded-full text-[9px] text-accent font-mono transition-colors">#<span x-text="tag"></span></a>
                        </template>
                        
                        <!-- Consume state badge -->
                        <span :class="{
                            'bg-success/5 text-success border-success/20': r.state === 'done',
                            'bg-accent/5 text-accent border-accent/20': r.state === 'consuming',
                            'bg-surface-2 text-text-muted border-border': r.state === 'to_consume'
                        }" class="border text-[8px] px-1.5 py-0.2 rounded-full font-mono font-bold uppercase tracking-wider" x-text="r.state.replace('_', ' ')"></span>
                        
                        <span class="text-[9px] text-text-subtle font-mono pl-1" x-text="'Added: ' + r.added"></span>
                    </div>
                </div>

                <!-- Star Rating Display & Actions -->
                <div class="flex-shrink-0 flex items-center space-x-3">
                    <div x-show="r.rating" class="flex items-center space-x-0.5 text-warning text-base font-semibold select-none">
                        <template x-for="star in Array.from({length: r.rating})">
                            <span>★</span>
                        </template>
                    </div>

                    <!-- Actions on hover -->
                    <div class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center space-x-1">
                        <button @click="openEditModal(r)" class="text-text-subtle hover:text-accent p-1" title="Edit Resource">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <button @click="deleteResource(r.id)" class="text-text-subtle hover:text-danger p-1" title="Delete Resource">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty State -->
    <template x-if="filteredResources.length === 0">
        <div class="py-12 text-center text-xs text-text-muted border border-dashed border-border rounded-sm bg-surface">
            No resources found matching your filter criteria.
        </div>
    </template>

    <!-- Create/Edit Modal -->
    <x-ui.modal name="resource-modal" maxWidth="md">
        <x-slot:title>
            <span x-text="isEdit ? 'Edit Resource' : 'Add New Resource'"></span>
        </x-slot:title>

        <div class="space-y-4">
            <div>
                <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block mb-1">Resource Title</label>
                <input 
                    type="text" 
                    x-model="modalData.title"
                    class="w-full bg-transparent border border-border px-3 py-1.5 rounded-sm focus:outline-none focus:border-accent text-text-main text-xs"
                    :class="errors.title ? 'border-danger focus:border-danger' : 'border-border'"
                    placeholder="e.g. Designing Data-Intensive Applications"
                />
                <template x-if="errors.title">
                    <p class="text-danger text-[10px] mt-1 font-mono" x-text="errors.title[0]"></p>
                </template>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block mb-1">Resource Type</label>
                    <select 
                        x-model="modalData.resource_type"
                        class="w-full bg-surface border border-border px-3 py-1.5 rounded-sm focus:outline-none focus:border-accent text-text-main text-xs"
                    >
                        <option value="book">Book</option>
                        <option value="video">Video</option>
                        <option value="article">Article</option>
                        <option value="tool">Tool</option>
                        <option value="repo">Repository</option>
                        <option value="doc">Documentation</option>
                    </select>
                </div>
                <div>
                    <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block mb-1">Consume State</label>
                    <select 
                        x-model="modalData.consume_state"
                        class="w-full bg-surface border border-border px-3 py-1.5 rounded-sm focus:outline-none focus:border-accent text-text-main text-xs"
                    >
                        <option value="to_consume">To Consume</option>
                        <option value="consuming">Consuming</option>
                        <option value="done">Done</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block mb-1">Author / Publisher</label>
                    <input 
                        type="text" 
                        x-model="modalData.author"
                        class="w-full bg-transparent border border-border px-3 py-1.5 rounded-sm focus:outline-none focus:border-accent text-text-main text-xs"
                        placeholder="e.g. Martin Kleppmann"
                    />
                </div>
                <div>
                    <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block mb-1">URL</label>
                    <input 
                        type="text" 
                        x-model="modalData.url"
                        class="w-full bg-transparent border border-border px-3 py-1.5 rounded-sm focus:outline-none focus:border-accent text-text-main text-xs"
                        placeholder="e.g. https://dataintensive.net"
                    />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block mb-1">Rating (1-5 Stars)</label>
                    <select 
                        x-model="modalData.rating"
                        class="w-full bg-surface border border-border px-3 py-1.5 rounded-sm focus:outline-none focus:border-accent text-text-main text-xs"
                    >
                        <option value="">No Rating</option>
                        <option value="1">1 Star</option>
                        <option value="2">2 Stars</option>
                        <option value="3">3 Stars</option>
                        <option value="4">4 Stars</option>
                        <option value="5">5 Stars</option>
                    </select>
                </div>
                <div>
                    <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block mb-1">Tags (comma-separated)</label>
                    <input 
                        type="text" 
                        x-model="modalData.tags"
                        class="w-full bg-transparent border border-border px-3 py-1.5 rounded-sm focus:outline-none focus:border-accent text-text-main text-xs"
                        placeholder="e.g. architecture, system-design"
                    />
                </div>
            </div>
        </div>

        <x-slot:footer>
            <div class="flex-grow"></div>
            <x-ui.button variant="secondary" @click="closeModal()" class="font-bold cursor-pointer">
                Cancel
            </x-ui.button>
            <x-ui.button variant="primary" @click="saveResource()" class="font-bold cursor-pointer">
                <span x-text="isEdit ? 'Save Changes' : 'Add Resource'"></span>
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>

<script>
window.resourcesComponent = function(initialResources) {
    return {
        resources: initialResources,
        filterType: 'all',
        searchQuery: '',
        isEdit: false,
        editId: null,
        modalData: {
            title: '',
            resource_type: 'book',
            consume_state: 'to_consume',
            author: '',
            url: '',
            rating: '',
            tags: ''
        },
        errors: {},

        typeFilters: [
            { label: 'All', value: 'all' },
            { label: 'Books', value: 'book' },
            { label: 'Videos', value: 'video' },
            { label: 'Articles', value: 'article' },
            { label: 'Tools', value: 'tool' },
            { label: 'Repos', value: 'repo' },
            { label: 'Docs', value: 'doc' }
        ],

        get filteredResources() {
            let res = this.resources;
            
            if (this.filterType !== 'all') {
                res = res.filter(r => r.type === this.filterType);
            }
            
            if (this.searchQuery) {
                const q = this.searchQuery.toLowerCase();
                res = res.filter(r => {
                    return r.title.toLowerCase().includes(q) ||
                           (r.author && r.author.toLowerCase().includes(q)) ||
                           (r.url && r.url.toLowerCase().includes(q)) ||
                           r.tags.some(tag => tag.toLowerCase().includes(q));
                });
            }
            
            return res;
        },

        openCreateModal() {
            this.isEdit = false;
            this.editId = null;
            this.modalData = {
                title: '',
                resource_type: 'book',
                consume_state: 'to_consume',
                author: '',
                url: '',
                rating: '',
                tags: ''
            };
            this.errors = {};
            this.$dispatch('open-modal', { name: 'resource-modal' });
        },

        openEditModal(resource) {
            this.isEdit = true;
            this.editId = resource.id;
            this.modalData = {
                title: resource.title,
                resource_type: resource.type,
                consume_state: resource.state,
                author: resource.author,
                url: resource.url,
                rating: resource.rating ? resource.rating.toString() : '',
                tags: resource.tags.join(', ')
            };
            this.errors = {};
            this.$dispatch('open-modal', { name: 'resource-modal' });
        },

        closeModal() {
            this.$dispatch('close-modal', { name: 'resource-modal' });
        },

        saveResource() {
            this.errors = {};
            const url = this.isEdit ? `/resources/${this.editId}` : '/resources';
            const method = this.isEdit ? 'PUT' : 'POST';

            const tagsArr = this.modalData.tags
                .split(',')
                .map(t => t.trim())
                .filter(t => t.length > 0);

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    title: this.modalData.title,
                    resource_type: this.modalData.resource_type,
                    consume_state: this.modalData.consume_state,
                    author: this.modalData.author,
                    url: this.modalData.url,
                    rating: this.modalData.rating ? parseInt(this.modalData.rating) : null,
                    tags: tagsArr
                })
            })
            .then(res => {
                if (!res.ok) {
                    return res.json().then(errData => {
                        this.errors = errData.errors || {};
                        throw new Error('Validation failed');
                    });
                }
                return res.json();
            })
            .then(data => {
                if (data.resource) {
                    if (this.isEdit) {
                        const index = this.resources.findIndex(r => r.id === this.editId);
                        if (index !== -1) {
                            this.resources[index] = data.resource;
                        }
                        window.dispatchEvent(new CustomEvent('show-toast', { 
                            detail: { message: 'Resource updated successfully.' }
                        }));
                    } else {
                        this.resources.unshift(data.resource);
                        window.dispatchEvent(new CustomEvent('show-toast', { 
                            detail: { message: 'Resource added successfully.' }
                        }));
                    }
                    this.closeModal();
                }
            })
            .catch(err => {
                // Validation error display inline
            });
        },

        deleteResource(id) {
            if (!confirm('Are you sure you want to archive this resource?')) return;

            fetch(`/resources/${id}`, {
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
                    this.resources = this.resources.filter(r => r.id !== id);
                    window.dispatchEvent(new CustomEvent('show-toast', { 
                        detail: { message: 'Resource archived successfully.' }
                    }));
                }
            });
        }
    };
};
</script>
@endsection
