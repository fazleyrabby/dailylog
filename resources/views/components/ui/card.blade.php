@props([
    'title' => null,
])

<div {{ $attributes->merge(['class' => 'ui-card bg-surface border border-border rounded-sm overflow-hidden text-sm']) }}>
    @if($title || isset($header))
        <div class="border-b border-border px-4 py-3 flex items-center justify-between bg-surface-2/30">
            @if($title)
                <h4 class="font-semibold text-text-main text-xs uppercase tracking-wider">{{ $title }}</h4>
            @else
                {{ $header }}
            @endif
        </div>
    @endif
    <div class="p-4">
        {{ $slot }}
    </div>
</div>
