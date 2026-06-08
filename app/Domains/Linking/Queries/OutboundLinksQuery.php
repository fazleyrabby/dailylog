<?php

namespace App\Domains\Linking\Queries;

use App\Models\Entry;
use Illuminate\Support\Collection;

class OutboundLinksQuery
{
    public function run(Entry $entry, int $limit = 50): Collection
    {
        return Entry::query()
            ->join('entry_links', 'entry_links.target_id', '=', 'entries.id')
            ->where('entry_links.source_id', $entry->id)
            ->whereNull('entries.archived_at')
            ->orderByDesc('entries.last_activity_at')
            ->limit($limit)
            ->get(['entries.id', 'entries.type', 'entries.title', 'entries.last_activity_at']);
    }
}
