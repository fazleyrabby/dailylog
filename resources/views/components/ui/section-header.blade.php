@props([
    'title',
    'badge' => null
])

<div {{ $attributes->merge(['class' => 'flex items-center justify-between border-b border-border pb-2 mb-4']) }}>
    <div class="flex items-center space-x-2">
        <h2 class="text-sm font-semibold text-text-main tracking-tight uppercase">{{ $title }}</h2>
        @if($badge !== null)
            <span class="bg-surface-2 border border-border px-2 py-0.5 rounded-full text-xs text-text-muted font-mono">
                {{ $badge }}
            </span>
        @endif
    </div>
    @if(isset($actions))
        <div class="flex items-center space-x-2">
            {{ $actions }}
        </div>
    @endif
</div>
