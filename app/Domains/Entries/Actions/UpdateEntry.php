<?php

namespace App\Domains\Entries\Actions;

use App\Domains\Entries\Services\EntryActivityService;
use App\Domains\Linking\Services\LinkService;
use App\Models\Entry;
use Illuminate\Support\Facades\DB;

class UpdateEntry
{
    public function __construct(
        private readonly EntryActivityService $activity,
        private readonly LinkService $links,
    ) {
    }

    /**
     * @param  array<string, mixed>  $changes  whitelisted via FormRequest upstream
     */
    public function execute(Entry $entry, array $changes): Entry
    {
        return DB::transaction(function () use ($entry, $changes) {
            $entry->fill($changes);

            $bodyChanged = $entry->isDirty('body');
            $meaningful = $entry->isDirty(['title', 'body', 'status', 'project_id', 'pinned', 'occurred_on']);

            $entry->save();

            if ($bodyChanged) {
                $this->links->resolveBody($entry);
            }

            if ($meaningful) {
                $this->activity->bump($entry);
            }

            return $entry->refresh();
        });
    }
}
