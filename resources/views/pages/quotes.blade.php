@extends('layouts.app')

@section('title', 'Quotes')
@section('header_breadcrumbs', 'DAILYLOG // QUOTES')

@section('content')
<div 
    x-data="quotesComponent({{ json_encode($quotes) }})"
    class="max-w-4xl mx-auto space-y-6"
>
    <!-- Header -->
    <x-ui.section-header title="Quotes Library" badge="">
        <x-slot:badge>
            <span x-text="quotes.length"></span>
        </x-slot:badge>
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
                placeholder="Search quotes, authors, tags..."
                class="w-full bg-surface border border-border pl-9 pr-4 py-1.5 rounded-sm focus:outline-none focus:border-accent text-text-main text-xs"
            />
        </div>

        <!-- Create Button -->
        <x-ui.button variant="primary" @click="openCreateModal()" class="font-bold w-full sm:w-auto">
            + Add Quote
        </x-ui.button>
    </div>

    <!-- Quotes container -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <template x-for="q in filteredQuotes" :key="q.id">
            <div class="bg-surface border border-border rounded-sm p-5 text-xs flex flex-col justify-between space-y-4 hover:border-accent/30 transition-colors group relative">
                <!-- Quote text -->
                <blockquote class="text-sm font-serif-reading italic text-text-main leading-relaxed select-text pr-10">
                    “<span x-text="q.body"></span>”
                </blockquote>

                <!-- Actions overlaid at top-right or next to content on hover -->
                <div class="absolute top-3 right-3 flex items-center space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button @click="openEditModal(q)" class="text-text-subtle hover:text-accent p-1" title="Edit Quote">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </button>
                    <button @click="deleteQuote(q.id)" class="text-text-subtle hover:text-danger p-1" title="Delete Quote">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
                
                <div class="flex items-center justify-between border-t border-border/60 pt-3">
                    <div class="space-y-0.5">
                        <cite class="not-italic font-bold text-text-main text-[11px] block" x-text="q.author"></cite>
                        <span class="text-text-muted font-mono text-[9px] block">
                            <span x-text="q.source"></span>
                            <span x-show="q.location" x-text="' — ' + q.location"></span>
                        </span>
                    </div>
                    
                    <div class="flex items-center space-x-1 flex-wrap justify-end gap-y-0.5">
                        <template x-for="tag in q.tags">
                            <a :href="'/search?tag[]=' + encodeURIComponent(tag)" class="bg-surface-2 hover:bg-surface border border-border px-1.5 py-0.2 rounded-full text-[9px] text-accent font-mono transition-colors">#<span x-text="tag"></span></a>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty State -->
    <template x-if="filteredQuotes.length === 0">
        <div class="py-12 text-center text-xs text-text-muted border border-dashed border-border rounded-sm bg-surface">
            No quotes found matching your search.
        </div>
    </template>

    <!-- Create/Edit Modal -->
    <x-ui.modal name="quote-modal" maxWidth="md">
        <x-slot:title>
            <span x-text="isEdit ? 'Edit Quote' : 'Add New Quote'"></span>
        </x-slot:title>

        <div class="space-y-4">
            <div>
                <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block mb-1">Quote Body</label>
                <textarea 
                    x-model="modalData.body" 
                    rows="3"
                    class="w-full bg-transparent border border-border px-3 py-1.5 rounded-sm focus:outline-none focus:border-accent text-text-main text-xs resize-none"
                    :class="errors.body ? 'border-danger focus:border-danger' : 'border-border'"
                    placeholder="Enter the quote text..."
                ></textarea>
                <template x-if="errors.body">
                    <p class="text-danger text-[10px] mt-1 font-mono" x-text="errors.body[0]"></p>
                </template>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block mb-1">Author</label>
                    <input 
                        type="text" 
                        x-model="modalData.author"
                        class="w-full bg-transparent border border-border px-3 py-1.5 rounded-sm focus:outline-none focus:border-accent text-text-main text-xs"
                        placeholder="e.g. Marcus Aurelius"
                    />
                </div>
                <div>
                    <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block mb-1">Source / Book</label>
                    <input 
                        type="text" 
                        x-model="modalData.source"
                        class="w-full bg-transparent border border-border px-3 py-1.5 rounded-sm focus:outline-none focus:border-accent text-text-main text-xs"
                        placeholder="e.g. Meditations"
                    />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block mb-1">Location / Page</label>
                    <input 
                        type="text" 
                        x-model="modalData.location"
                        class="w-full bg-transparent border border-border px-3 py-1.5 rounded-sm focus:outline-none focus:border-accent text-text-main text-xs"
                        placeholder="e.g. Book IV, Section 3"
                    />
                </div>
                <div>
                    <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block mb-1">Tags (comma-separated)</label>
                    <input 
                        type="text" 
                        x-model="modalData.tags"
                        class="w-full bg-transparent border border-border px-3 py-1.5 rounded-sm focus:outline-none focus:border-accent text-text-main text-xs"
                        placeholder="e.g. wisdom, stoicism"
                    />
                </div>
            </div>
        </div>

        <x-slot:footer>
            <div class="flex-grow"></div>
            <x-ui.button variant="secondary" @click="closeModal()" class="font-bold cursor-pointer">
                Cancel
            </x-ui.button>
            <x-ui.button variant="primary" @click="saveQuote()" class="font-bold cursor-pointer">
                <span x-text="isEdit ? 'Save Changes' : 'Add Quote'"></span>
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>

<script>
window.quotesComponent = function(initialQuotes) {
    return {
        quotes: initialQuotes,
        searchQuery: '',
        isEdit: false,
        editId: null,
        modalData: {
            body: '',
            author: '',
            source: '',
            location: '',
            tags: ''
        },
        errors: {},

        get filteredQuotes() {
            if (!this.searchQuery) return this.quotes;
            const q = this.searchQuery.toLowerCase();
            return this.quotes.filter(item => {
                return item.body.toLowerCase().includes(q) ||
                       item.author.toLowerCase().includes(q) ||
                       (item.source && item.source.toLowerCase().includes(q)) ||
                       (item.location && item.location.toLowerCase().includes(q)) ||
                       item.tags.some(tag => tag.toLowerCase().includes(q));
            });
        },

        openCreateModal() {
            this.isEdit = false;
            this.editId = null;
            this.modalData = {
                body: '',
                author: '',
                source: '',
                location: '',
                tags: ''
            };
            this.errors = {};
            this.$dispatch('open-modal', { name: 'quote-modal' });
        },

        openEditModal(quote) {
            this.isEdit = true;
            this.editId = quote.id;
            this.modalData = {
                body: quote.body,
                author: quote.author,
                source: quote.source,
                location: quote.location,
                tags: quote.tags.join(', ')
            };
            this.errors = {};
            this.$dispatch('open-modal', { name: 'quote-modal' });
        },

        closeModal() {
            this.$dispatch('close-modal', { name: 'quote-modal' });
        },

        saveQuote() {
            this.errors = {};
            const url = this.isEdit ? `/quotes/${this.editId}` : '/quotes';
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
                    body: this.modalData.body,
                    author: this.modalData.author,
                    source: this.modalData.source,
                    location: this.modalData.location,
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
                if (data.quote) {
                    if (this.isEdit) {
                        const index = this.quotes.findIndex(q => q.id === this.editId);
                        if (index !== -1) {
                            this.quotes[index] = data.quote;
                        }
                        window.dispatchEvent(new CustomEvent('show-toast', { 
                            detail: { message: 'Quote updated successfully.' }
                        }));
                    } else {
                        this.quotes.unshift(data.quote);
                        window.dispatchEvent(new CustomEvent('show-toast', { 
                            detail: { message: 'Quote added successfully.' }
                        }));
                    }
                    this.closeModal();
                }
            })
            .catch(err => {
                // Validation error is displayed inline
            });
        },

        deleteQuote(id) {
            if (!confirm('Are you sure you want to archive this quote?')) return;

            fetch(`/quotes/${id}`, {
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
                    this.quotes = this.quotes.filter(q => q.id !== id);
                    window.dispatchEvent(new CustomEvent('show-toast', { 
                        detail: { message: 'Quote archived successfully.' }
                    }));
                }
            });
        }
    };
};
</script>
@endsection
