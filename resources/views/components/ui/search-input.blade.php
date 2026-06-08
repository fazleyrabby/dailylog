@props([
    'placeholder' => 'Search or filter...',
    'name' => 'search',
    'value' => ''
])

<div x-data="{ query: '{{ $value }}' }" class="relative w-full">
    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-text-subtle">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
    </div>
    
    <input 
        x-ref="searchInput"
        x-model="query"
        type="text" 
        name="{{ $name }}"
        placeholder="{{ $placeholder }}"
        x-on:keydown.window="
            if ($event.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA' && !document.activeElement.hasAttribute('contenteditable')) {
                $event.preventDefault();
                $refs.searchInput.focus();
            }
        "
        {{ $attributes->merge([
            'class' => 'w-full h-8 pl-8 pr-8 bg-surface border border-border rounded-sm text-sm focus:border-accent focus:ring-1 focus:ring-accent focus:outline-none placeholder-text-subtle'
        ]) }}
    />
    
    <div class="absolute inset-y-0 right-0 pr-2 flex items-center">
        <div x-show="query === ''" class="flex items-center">
            <kbd class="text-[10px] font-mono border border-border bg-surface-2 text-text-subtle px-1 rounded-xs">/</kbd>
        </div>
        <button 
            type="button"
            x-show="query !== ''" 
            @click="query = ''; $refs.searchInput.value = ''; $dispatch('clear-search');" 
            class="text-text-subtle hover:text-text-main focus:outline-none"
            style="display: none;"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</div>
