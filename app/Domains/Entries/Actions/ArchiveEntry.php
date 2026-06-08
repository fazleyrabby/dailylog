<?php

namespace App\Domains\Entries\Actions;

use App\Models\Entry;

class ArchiveEntry
{
    public function execute(Entry $entry): Entry
    {
        if ($entry->archived_at === null) {
            $entry->archived_at = now();
            $entry->save();
        }

        return $entry;
    }
}
