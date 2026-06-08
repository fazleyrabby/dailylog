@props([
    'align' => 'right',
    'width' => '56'
])

@php
    $alignmentClasses = [
        'right' => 'right-0 origin-top-right',
        'left' => 'left-0 origin-top-left',
    ][$align] ?? 'right-0 origin-top-right';

    $widthClasses = [
        '48' => 'w-48',
        '56' => 'w-56',
        '64' => 'w-64',
    ][$width] ?? 'w-56';
@endphp

<div x-data="{ open: false }" @click.away="open = false" @close.stop="open = false" class="relative inline-block text-left">
    <div @click="open = !open" class="cursor-pointer">
        {{ $trigger }}
    </div>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="absolute z-50 mt-1 rounded-sm border border-border bg-surface shadow-lg focus:outline-none {{ $alignmentClasses }} {{ $widthClasses }}"
         style="display: none;">
        <div class="py-1 text-sm text-text-main">
            {{ $content }}
        </div>
    </div>
</div>
