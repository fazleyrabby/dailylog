<?php

namespace App\Domains\Entries\Actions;

use App\Domains\Entries\Services\EntryActivityService;
use App\Models\Entry;

class RestoreEntry
{
    public function __construct(private readonly EntryActivityService $activity)
    {
    }

    public function execute(Entry $entry): Entry
    {
        if ($entry->archived_at !== null) {
            $entry->archived_at = null;
            $entry->save();
            $this->activity->bump($entry);
        }

        return $entry;
    }
}
