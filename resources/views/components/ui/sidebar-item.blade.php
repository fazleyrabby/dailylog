@props([
    'href' => '#',
    'active' => false,
    'badge' => null,
])

@php
    $classes = $active
        ? 'flex items-center justify-between px-3 py-2 text-xs font-semibold rounded-sm bg-accent-subtle-bg/40 text-text-main transition-all duration-75'
        : 'flex items-center justify-between px-3 py-2 text-xs font-medium rounded-sm text-text-muted hover:bg-surface-2 hover:text-text-main transition-all duration-75';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    <div class="flex items-center space-x-2.5">
        @if(isset($icon))
            <span class="{{ $active ? 'text-text-main' : 'text-text-subtle' }}">
                {{ $icon }}
            </span>
        @endif
        <span x-show="!sidebarCollapsed" class="whitespace-nowrap transition-opacity duration-75 uppercase tracking-wide">{{ $slot }}</span>
    </div>
    
    @if($badge !== null)
        <span x-show="!sidebarCollapsed" class="ml-2 font-mono text-[10px] px-1 py-0.2 bg-surface-2 text-text-muted rounded-xs border border-border">
            {{ $badge }}
        </span>
    @endif
</a>
