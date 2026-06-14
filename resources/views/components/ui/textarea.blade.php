@props([
    'name' => '',
    'value' => '',
    'error' => null,
    'rows' => 3,
])

@php
    $errorClasses = $error 
        ? 'border-danger text-danger focus:border-danger focus:ring-danger' 
        : 'border-border text-text-main focus:border-accent focus:ring-accent';
@endphp

<div class="w-full">
    <textarea 
        name="{{ $name }}" 
        rows="{{ $rows }}"
        {{ $attributes->merge([
            'class' => "ui-input w-full p-2 bg-surface border rounded-sm text-sm focus:ring-1 focus:outline-none placeholder-text-subtle disabled:opacity-50 " . $errorClasses
        ]) }}
    >{{ $value ?? $slot }}</textarea>
    @if($error)
        <span class="text-xs text-danger mt-1 block">{{ $error }}</span>
    @endif
</div>
