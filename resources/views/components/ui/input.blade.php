@props([
    'type' => 'text',
    'name' => '',
    'value' => '',
    'error' => null,
])

@php
    $errorClasses = $error 
        ? 'border-danger text-danger focus:border-danger focus:ring-danger' 
        : 'border-border text-text-main focus:border-accent focus:ring-accent';
@endphp

<div class="w-full">
    <input 
        type="{{ $type }}" 
        name="{{ $name }}" 
        value="{{ $value }}"
        {{ $attributes->merge([
            'class' => "w-full h-8 px-2 bg-surface border rounded-sm text-sm focus:ring-1 focus:outline-none placeholder-text-subtle disabled:opacity-50 " . $errorClasses
        ]) }}
    />
    @if($error)
        <span class="text-xs text-danger mt-1 block">{{ $error }}</span>
    @endif
</div>
