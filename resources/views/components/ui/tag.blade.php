@props([
    'value' => '',
    'removable' => false,
])

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-surface-2 text-text-muted border border-border group hover:text-text-main transition-colors cursor-pointer']) }}>
    <span class="text-text-subtle group-hover:text-accent mr-0.5">#</span>{{ $value ?? $slot }}
    @if($removable)
        <button type="button" class="ml-1 text-text-subtle hover:text-danger focus:outline-none focus:text-danger">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    @endif
</span>
