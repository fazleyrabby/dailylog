@if($backlinks->isEmpty())
    <div class="text-xxs text-text-subtle italic px-2 py-3">No backlinks yet.</div>
@else
    <ul class="space-y-1">
        @foreach($backlinks as $bl)
            <li class="text-xs">
                <a href="/e/{{ $bl->id }}" class="text-text-main hover:text-accent flex items-center justify-between gap-2 px-2 py-1 rounded-sm hover:bg-surface-2/40">
                    <span class="flex items-center gap-2 min-w-0">
                        <span class="text-[10px] uppercase font-mono text-text-subtle">{{ $bl->type }}</span>
                        <span class="truncate">{{ $bl->title ?? '(untitled)' }}</span>
                    </span>
                    <span class="text-[10px] text-text-subtle flex-shrink-0">{{ $bl->last_activity_at?->diffForHumans() }}</span>
                </a>
            </li>
        @endforeach
    </ul>
@endif
