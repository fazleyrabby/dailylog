<?php

namespace App\Domains\Entries\Actions;

use App\Models\Entry;

class PinEntry
{
    public function execute(Entry $entry, bool $pinned = true): Entry
    {
        if ($entry->pinned !== $pinned) {
            $entry->pinned = $pinned;
            $entry->save();
        }

        return $entry;
    }
}
