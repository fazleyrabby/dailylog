@props([
    'name',
    'show' => false,
    'maxWidth' => 'md'
])

@php
$maxWidthClass = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
][$maxWidth] ?? 'sm:max-w-md';
@endphp

<div
    x-data="{
        show: @js($show),
        focusables() {
            let selector = 'a, button, input, textarea, select, details, [tabindex]:not([tabindex=\'-1\'])';
            return [...$el.querySelectorAll(selector)]
                .filter(el => !el.hasAttribute('disabled') && el.offsetParent !== null);
        },
        firstFocusable() { return this.focusables()[0] },
        lastFocusable() { let f = this.focusables(); return f[f.length - 1] },
        nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable() },
        prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable() },
        nextFocusableIndex() { return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1) },
        prevFocusableIndex() { return (this.focusables().indexOf(document.activeElement) - 1 + this.focusables().length + 1) % (this.focusables().length + 1) }
    }"
    x-init="
        $watch('show', value => {
            if (value) {
                document.body.classList.add('overflow-y-hidden');
            } else {
                document.body.classList.remove('overflow-y-hidden');
            }
        });
    "
    x-on:open-modal.window="if ($event.detail.name == '{{ $name }}') show = true"
    x-on:close-modal.window="if ($event.detail.name == '{{ $name }}') show = false"
    x-on:keydown.escape.window="show = false"
    x-on:keydown.tab.prevent="$event.shiftKey ? prevFocusable().focus() : nextFocusable().focus()"
    x-show="show"
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
>
    <!-- Backdrop -->
    <div x-show="show" class="fixed inset-0 transform transition-all" x-on:click="show = false">
        <div class="absolute inset-0 bg-stone-900/60 dark:bg-stone-950/80 backdrop-blur-xs"></div>
    </div>

    <!-- Modal Content -->
    <div x-show="show" 
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         class="relative z-10 mb-6 bg-surface border border-border rounded-sm overflow-hidden shadow-lg transform transition-all sm:w-full sm:mx-auto {{ $maxWidthClass }} mt-[15vh]">
        
        <div class="border-b border-border px-4 py-3 flex items-center justify-between">
            @if(isset($title))
                <h3 class="text-sm font-semibold text-text-main">{{ $title }}</h3>
            @endif
            <button @click="show = false" class="text-text-subtle hover:text-text-main">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="px-4 py-3 text-sm">
            {{ $slot }}
        </div>

        @if(isset($footer))
            <div class="border-t border-border px-4 py-3 bg-surface-2 flex justify-end space-x-2">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
