@props([
    'variant' => 'primary',
    'size' => 'sm',
    'type' => 'button'
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-medium rounded-sm border transition-colors focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-bg disabled:opacity-50 disabled:pointer-events-none cursor-pointer';
    
    $variants = [
        'primary' => 'bg-accent border-accent text-white hover:bg-accent-hover hover:border-accent-hover',
        'secondary' => 'bg-surface border-border text-text-main hover:bg-surface-2 hover:border-border-strong',
        'ghost' => 'bg-transparent border-transparent text-text-muted hover:bg-surface-2 hover:text-text-main',
        'danger' => 'bg-surface border-danger text-danger hover:bg-danger/10',
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
