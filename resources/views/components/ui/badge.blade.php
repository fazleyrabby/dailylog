@props([
    'status' => 'info',
])

@php
    $configs = [
        'success' => [
            'classes' => 'text-success bg-success/5 border-success/20 dark:text-success dark:bg-success/10 dark:border-success/30',
            'icon' => '<svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>'
        ],
        'warning' => [
            'classes' => 'text-warning bg-warning/5 border-warning/20 dark:text-warning dark:bg-warning/10 dark:border-warning/30',
            'icon' => '<svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>'
        ],
        'danger' => [
            'classes' => 'text-danger bg-danger/5 border-danger/20 dark:text-danger dark:bg-danger/10 dark:border-danger/30',
            'icon' => '<svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
        ],
        'info' => [
            'classes' => 'text-info bg-info/5 border-info/20 dark:text-info dark:bg-info/10 dark:border-info/30',
            'icon' => '<svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
        ],
        'active' => [
            'classes' => 'text-accent bg-accent-subtle-bg border-accent/20 dark:border-accent/30',
            'icon' => '<svg class="w-3 h-3 mr-1 animate-pulse" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="6" /></svg>'
        ],
        'neutral' => [
            'classes' => 'text-text-muted bg-surface-2 border-border',
            'icon' => ''
        ]
    ];

    $config = $configs[$status] ?? $configs['info'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium border " . $config['classes']]) }}>
    {!! $config['icon'] !!}
    {{ $slot }}
</span>
