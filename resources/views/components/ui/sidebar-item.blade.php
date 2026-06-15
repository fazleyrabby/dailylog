@props([
    'href' => '#',
    'active' => false,
    'badge' => null,
    'labelKey' => null,
])

@php
    $classes = $active
        ? 'relative group ui-sidebar-item flex items-center justify-between px-3 py-2 text-xs font-semibold rounded-sm bg-accent-subtle-bg/40 text-text-main transition-all duration-75'
        : 'relative group ui-sidebar-item flex items-center justify-between px-3 py-2 text-xs font-medium rounded-sm text-text-muted hover:bg-surface-2 hover:text-text-main transition-all duration-75';
@endphp

<a 
    href="{{ $href }}" 
    {{ $attributes->merge(['class' => $classes]) }}
>
    <div class="flex items-center">
        @if(isset($icon))
            <span class="inline-flex items-center justify-center w-5 h-5 mr-2.5 flex-shrink-0 {{ $active ? 'text-text-main' : 'text-text-subtle' }}">
                {{ $icon }}
            </span>
        @endif
        <span x-show="!sidebarCollapsed" @if($labelKey) x-text="$store.themes.label(@js($labelKey))" @endif class="whitespace-nowrap transition-opacity duration-75 uppercase tracking-wide">{{ $slot }}</span>
    </div>
    
    @if($badge !== null)
        <span x-show="!sidebarCollapsed" class="ml-2 font-mono text-[10px] px-1 py-0.2 bg-surface-2 text-text-muted rounded-xs border border-border">
            {{ $badge }}
        </span>
    @endif

    <!-- Custom Tooltip -->
    <div 
        x-show="sidebarCollapsed" 
        class="absolute left-full top-1/2 -translate-y-1/2 ml-3 px-2.5 py-1 bg-surface-2 border border-border text-text-main text-[10px] font-bold uppercase tracking-wider font-mono rounded-xs shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-150 pointer-events-none whitespace-nowrap z-50"
        x-text="@js($labelKey) ? $store.themes.label(@js($labelKey)) : '{{ trim(strip_tags($slot)) }}'"
    ></div>
</a>
