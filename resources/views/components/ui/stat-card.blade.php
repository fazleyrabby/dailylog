@props([
    'title',
    'value',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'bg-surface border border-border rounded-sm p-4']) }}>
    <span class="text-xs font-medium text-text-muted uppercase tracking-wider block">{{ $title }}</span>
    <span class="text-2xl font-semibold text-text-main mt-1 block font-mono tracking-tight">{{ $value }}</span>
    @if($subtitle)
        <span class="text-xs text-text-subtle mt-1.5 block">{{ $subtitle }}</span>
    @endif
</div>
