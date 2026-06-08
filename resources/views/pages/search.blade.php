@extends('layouts.app')

@section('title', 'Search')
@section('header_breadcrumbs', 'DAILYLOG // SEARCH')

@section('content')
<div class="max-w-4xl mx-auto space-y-4">

    <form method="GET" action="{{ route('search.index') }}" class="bg-surface border border-border rounded-sm p-3 space-y-3">
        <div class="flex items-center gap-2">
            <input
                type="text"
                name="q"
                value="{{ $filter->q }}"
                placeholder="Search anything…"
                autofocus
                class="flex-grow h-9 px-3 bg-surface-2 border border-border rounded-sm text-sm text-text-main focus:outline-none focus:ring-1 focus:ring-accent"
            />
            <button type="submit" class="h-9 px-4 bg-accent hover:bg-accent-hover text-accent-fg text-xs font-semibold rounded-sm">
                Search
            </button>
        </div>

        <div class="flex flex-wrap gap-2 text-xs">
            @foreach (['task','note','journal','bookmark','quote','resource','learning','idea'] as $t)
                <label class="flex items-center gap-1.5 px-2 py-1 bg-surface-2 border border-border rounded-sm cursor-pointer">
                    <input type="checkbox" name="type[]" value="{{ $t }}" @checked(in_array($t, $filter->types, true)) class="accent-accent">
                    <span class="capitalize">{{ $t }}</span>
                </label>
            @endforeach
        </div>

        <div class="flex flex-wrap gap-2">
            <input type="text" name="project" value="{{ $filter->projectSlug }}" placeholder="project slug" class="h-8 px-2 bg-surface-2 border border-border rounded-sm text-xs w-40">
            <input type="text" name="tag[]" value="{{ $filter->tagSlugs[0] ?? '' }}" placeholder="tag slug" class="h-8 px-2 bg-surface-2 border border-border rounded-sm text-xs w-32">
            <input type="date" name="from" value="{{ $filter->from?->toDateString() }}" class="h-8 px-2 bg-surface-2 border border-border rounded-sm text-xs">
            <input type="date" name="to" value="{{ $filter->to?->toDateString() }}" class="h-8 px-2 bg-surface-2 border border-border rounded-sm text-xs">
            <label class="flex items-center gap-1.5 px-2 h-8 bg-surface-2 border border-border rounded-sm text-xs cursor-pointer">
                <input type="checkbox" name="archived" value="1" @checked($filter->includeArchived) class="accent-accent"> archived
            </label>
        </div>
    </form>

    @if ($results === null)
        <div class="bg-surface border border-border rounded-sm p-8 text-center text-text-muted">
            <p class="text-xs uppercase tracking-wider text-text-subtle mb-2">Search across everything</p>
            <p class="text-sm">Enter a query or pick filters above.</p>
        </div>
    @elseif ($results->total() === 0)
        <div class="bg-surface border border-border rounded-sm p-8 text-center text-text-muted">
            <p class="text-sm">No results.</p>
            @if ($filter->q !== '')
                <p class="text-xs text-text-subtle mt-1">Tried fuzzy match too. Nothing matched.</p>
            @endif
        </div>
    @else
        <div class="bg-surface border border-border rounded-sm divide-y divide-border/40">
            <div class="px-4 py-2 text-xxs uppercase tracking-wider text-text-subtle">
                {{ $results->total() }} result{{ $results->total() === 1 ? '' : 's' }}
            </div>
            @foreach ($results as $r)
                <a href="{{ $r->url }}" class="block px-4 py-3 hover:bg-surface-2/40 transition-colors">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-[10px] uppercase font-mono text-text-subtle bg-surface-2 border border-border px-1 rounded-xs">{{ $r->type }}</span>
                            <span class="text-sm text-text-main truncate">{{ $r->title ?? '(untitled)' }}</span>
                        </div>
                        <span class="text-[10px] text-text-subtle flex-shrink-0">{{ $r->lastActivityAt?->diffForHumans() }}</span>
                    </div>
                    @if ($r->snippet)
                        <div class="mt-1 text-xs text-text-muted">{!! $r->snippet !!}</div>
                    @endif
                </a>
            @endforeach
        </div>

        <div>{{ $results->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
