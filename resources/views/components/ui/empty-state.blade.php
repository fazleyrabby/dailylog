@props([
    'title',
    'description',
    'actionKey' => 'c',
    'actionLabel' => 'create first item',
    'secondaryHint' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center p-12 text-center border border-dashed border-border rounded-sm bg-surface/30']) }}>
    <h3 class="text-sm font-semibold text-text-main">{{ $title }}</h3>
    <p class="text-xs text-text-muted mt-1 max-w-sm">{{ $description }}</p>
    
    <div class="mt-4 flex items-center space-x-1.5 text-xs text-text-subtle">
        <kbd class="bg-surface-2 px-1.5 py-0.5 rounded border border-border text-text-muted font-mono font-semibold">{{ $actionKey }}</kbd>
        <span>to {{ $actionLabel }}</span>
        @if($secondaryHint)
            <span>·</span>
            <span>{!! $secondaryHint !!}</span>
        @endif
    </div>
</div>
