<?php

namespace App\Domains\Entries\Services;

use App\Models\Entry;
use App\Models\Project;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Heartbeat for the Slipping engine (docs/11 §4).
 * Bumps last_activity_at only on *meaningful* touches — never on passive views
 * or unrelated column writes. Also propagates the bump up to the parent project.
 */
class EntryActivityService
{
    public function bump(Entry $entry, ?CarbonImmutable $at = null): void
    {
        $at = $at ?? CarbonImmutable::now();

        DB::transaction(function () use ($entry, $at) {
            Entry::query()
                ->withoutOwnership()
                ->whereKey($entry->getKey())
                ->update(['last_activity_at' => $at]);

            $entry->forceFill(['last_activity_at' => $at]);

            if ($entry->project_id) {
                Project::query()
                    ->withoutOwnership()
                    ->whereKey($entry->project_id)
                    ->update(['last_activity_at' => $at]);
            }
        });
    }
}
