@extends('layouts.app')

@section('title', 'Activity Log')
@section('header_breadcrumbs', 'DAILYLOG // ACTIVITY LOG')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Page Header & Filters -->
    <div class="bg-surface border border-border rounded-sm p-4 text-xs">
        <form method="GET" action="{{ route('activity-log.index') }}" class="space-y-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-lg font-bold text-text-main">Activity Log</h1>
                    <p class="text-xxs text-text-muted mt-0.5">Filter, sort and browse all captured events and updates</p>
                </div>
                
                <div class="flex items-center space-x-2">
                    <button type="submit" class="bg-accent hover:bg-accent/90 text-white font-mono font-bold text-[10px] uppercase tracking-wider px-3 py-1.5 rounded-sm transition-all focus:outline-none cursor-pointer">
                        Apply Filters
                    </button>
                    <a href="{{ route('activity-log.index') }}" class="bg-surface-2 border border-border text-text-main hover:bg-surface px-3 py-1.5 rounded-sm font-mono font-bold text-[10px] uppercase tracking-wider text-center">
                        Reset
                    </a>
                </div>
            </div>

            <!-- Filters Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 pt-2 border-t border-border/60">
                <div>
                    <label class="text-[9px] font-bold uppercase tracking-wider text-text-subtle block mb-1 font-mono">Start Date</label>
                    <input 
                        type="date" 
                        name="start_date" 
                        value="{{ $startDate }}"
                        class="w-full bg-surface border border-border px-2.5 py-1.5 rounded-sm text-text-main text-xs focus:outline-none focus:border-accent"
                    />
                </div>

                <div>
                    <label class="text-[9px] font-bold uppercase tracking-wider text-text-subtle block mb-1 font-mono">End Date</label>
                    <input 
                        type="date" 
                        name="end_date" 
                        value="{{ $endDate }}"
                        class="w-full bg-surface border border-border px-2.5 py-1.5 rounded-sm text-text-main text-xs focus:outline-none focus:border-accent"
                    />
                </div>

                <div>
                    <label class="text-[9px] font-bold uppercase tracking-wider text-text-subtle block mb-1 font-mono">Event Type</label>
                    <select 
                        name="type" 
                        class="w-full bg-surface border border-border px-2.5 py-1.5 rounded-sm text-text-main text-xs focus:outline-none focus:border-accent"
                    >
                        @foreach($types as $val => $label)
                            <option value="{{ $val }}" {{ $selectedType === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-[9px] font-bold uppercase tracking-wider text-text-subtle block mb-1 font-mono">Sort Order</label>
                    <select 
                        name="sort" 
                        class="w-full bg-surface border border-border px-2.5 py-1.5 rounded-sm text-text-main text-xs focus:outline-none focus:border-accent"
                    >
                        <option value="desc" {{ $selectedSort === 'desc' ? 'selected' : '' }}>Newest First</option>
                        <option value="asc" {{ $selectedSort === 'asc' ? 'selected' : '' }}>Oldest First</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- Timeline Wrapper -->
    <div class="space-y-8 select-text">
        @if(empty($groupedEvents))
            <div class="py-16 text-center text-xs text-text-muted border border-dashed border-border rounded-sm bg-surface">
                No activity logs found for the selected date range and criteria.
            </div>
        @else
            @foreach($groupedEvents as $dayKey => $group)
                <div class="space-y-4">
                    <!-- Day Header -->
                    <div class="flex items-center space-x-2">
                        <span class="h-2 w-2 rounded-full bg-accent"></span>
                        <h3 class="font-bold text-xs uppercase tracking-wider text-text-main font-mono bg-surface-2/40 px-2.5 py-0.5 border border-border/80 rounded-full">{{ $group['label'] }}</h3>
                    </div>

                    <!-- Day Events -->
                    <div class="relative border-l border-border pl-6 ml-1.5 space-y-5">
                        @foreach($group['items'] as $item)
                            <div class="relative group">
                                <!-- Event Action Badge / Dotted Indicator -->
                                <span class="absolute -left-[32px] top-3.5 flex h-4 w-4 items-center justify-center rounded-full bg-surface border border-border text-[9px] font-bold shadow-xs select-none">
                                    @if($item['action'] === 'created')
                                        <span class="text-success" title="Created">+</span>
                                    @elseif($item['action'] === 'updated')
                                        <span class="text-accent" title="Updated">✎</span>
                                    @elseif($item['action'] === 'archived')
                                        <span class="text-danger" title="Archived">✕</span>
                                    @endif
                                </span>

                                <div class="bg-surface border border-border/60 hover:border-accent/20 rounded-sm p-3.5 transition-colors text-xs space-y-2">
                                    <!-- Event Header -->
                                    <div class="flex items-center justify-between flex-wrap gap-y-1">
                                        <div class="flex items-center space-x-2 min-w-0">
                                            <!-- Type Badge -->
                                            <span class="bg-surface-2 border border-border px-1.5 py-0.2 rounded-xs text-[8px] uppercase font-mono text-text-muted font-bold">{{ $item['type'] }}</span>
                                            
                                            <!-- Title & Link -->
                                            @if($item['link'])
                                                <a href="{{ $item['link'] }}" class="font-bold text-text-main hover:text-accent truncate block" title="{{ $item['title'] }}">
                                                    {{ $item['title'] }}
                                                </a>
                                            @else
                                                <span class="font-bold text-text-main truncate" title="{{ $item['title'] }}">{{ $item['title'] }}</span>
                                            @endif

                                            <!-- Action Indicator Text -->
                                            <span class="text-[10px] text-text-subtle italic font-mono lowercase">
                                                was {{ $item['action'] }}
                                            </span>
                                        </div>
                                        
                                        <!-- Event Time -->
                                        <span class="text-[9px] text-text-subtle font-mono whitespace-nowrap">{{ $item['time'] }}</span>
                                    </div>

                                    <!-- Event Body/Desc if available -->
                                    @if($item['desc'])
                                        <div class="text-[10px] text-text-muted pl-1.5 border-l border-border bg-surface-2/10 py-0.5">
                                            {{ $item['desc'] }}
                                        </div>
                                    @endif

                                    <!-- Tags -->
                                    @if(!empty($item['tags']))
                                        <div class="flex flex-wrap gap-1 pt-1">
                                            @foreach($item['tags'] as $tag)
                                                <a href="/search?tag[]={{ urlencode($tag) }}" class="bg-surface-2 hover:bg-surface border border-border px-1.5 py-0.2 rounded-full text-[9px] text-accent font-mono transition-colors">#{{ $tag }}</a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </div>

</div>
@endsection
