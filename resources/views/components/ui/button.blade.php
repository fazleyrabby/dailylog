@props([
    'variant' => 'primary',
    'size' => 'sm',
    'type' => 'button'
])

@php
    $baseClasses = 'ui-button inline-flex items-center justify-center font-bold rounded-md border border-b-[3px] transition-all hover:-translate-y-[0.5px] hover:border-b-[4px] hover:brightness-105 active:translate-y-[1px] active:border-b-[1px] active:brightness-95 focus:outline-none disabled:opacity-50 disabled:pointer-events-none cursor-pointer';
    
    $variants = [
        'primary' => 'bg-accent border-accent border-b-[color-mix(in_srgb,var(--color-accent-app)_85%,black)] text-white',
        'secondary' => 'bg-surface border-border border-b-[color-mix(in_srgb,var(--color-border-app)_85%,black)] text-text-main',
        'ghost' => 'bg-transparent border-transparent text-text-muted hover:bg-surface-2 hover:text-text-main hover:-translate-y-0 hover:border-b-0 active:translate-y-0 active:border-b-0',
        'danger' => 'bg-surface border-danger border-b-[color-mix(in_srgb,var(--color-danger-app)_85%,black)] text-danger',
    ];
    
    $sizes = [
        'sm' => 'h-7 px-3 text-xs',
        'md' => 'h-8 px-4 text-sm',
        'lg' => 'h-10 px-5 text-base',
    ];

    $classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['sm']);
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>
