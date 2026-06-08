<?php

namespace App\Domains\Entries\Actions;

use App\Domains\Entries\DTOs\EntryAttributes;
use App\Models\Entry;
use Illuminate\Support\Facades\DB;

class CreateEntry
{
    public function execute(EntryAttributes $attrs): Entry
    {
        return DB::transaction(function () use ($attrs) {
            $entry = new Entry($attrs->toModelAttributes());
            $entry->last_activity_at = now();
            $entry->save();

            return $entry->refresh();
        });
    }
}
