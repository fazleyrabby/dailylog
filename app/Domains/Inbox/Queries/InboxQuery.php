<?php

namespace App\Domains\Inbox\Queries;

use App\Models\Entry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Inbox = entries captured without a project, awaiting triage (docs/09 §5).
 * Journal entries are dated by definition and never inbox-y.
 */
class InboxQuery
{
    public function paginate(int $perPage = 25): LengthAwarePaginator
    {
        return Entry::query()
            ->whereNull('project_id')
            ->whereNull('archived_at')
            ->where('type', '!=', 'journal')
            ->orderByDesc('last_activity_at')
            ->paginate($perPage);
    }
}
