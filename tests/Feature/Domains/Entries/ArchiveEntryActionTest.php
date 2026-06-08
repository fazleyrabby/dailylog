<?php

namespace Tests\Feature\Domains\Entries;

use App\Domains\Entries\Actions\ArchiveEntry;
use App\Domains\Entries\Actions\RestoreEntry;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchiveEntryActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_archive_sets_archived_at(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $entry = Entry::factory()->for($user)->create();

        app(ArchiveEntry::class)->execute($entry);

        $this->assertNotNull($entry->fresh()->archived_at);
    }

    public function test_restore_clears_archived_at(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $entry = Entry::factory()->for($user)->archived()->create();

        app(RestoreEntry::class)->execute($entry);

        $this->assertNull($entry->fresh()->archived_at);
    }
}
