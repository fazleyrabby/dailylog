@extends('layouts.app')

@section('title', 'Quotes')
@section('header_breadcrumbs', 'DAILYLOG // QUOTES')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <x-ui.section-header title="Quotes Library" badge="2" />

    <!-- Quotes container -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($data['quotes'] as $q)
            <div class="bg-surface border border-border rounded-sm p-5 text-xs flex flex-col justify-between space-y-4 hover:border-accent/30 transition-colors">
                <blockquote class="text-sm font-serif-reading italic text-text-main leading-relaxed select-text">
                    “{{ $q['body'] }}”
                </blockquote>
                
                <div class="flex items-center justify-between border-t border-border/60 pt-3">
                    <div class="space-y-0.5">
                        <cite class="not-italic font-bold text-text-main text-[11px] block">{{ $q['author'] }}</cite>
                        <span class="text-text-muted font-mono text-[9px] block">{{ $q['source'] }}</span>
                    </div>
                    
                    <div class="flex items-center space-x-1">
                        @foreach($q['tags'] as $tag)
                            <span class="bg-surface-2 border border-border px-1.5 py-0.2 rounded-full text-[9px] text-text-subtle font-mono">#{{ $tag }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
