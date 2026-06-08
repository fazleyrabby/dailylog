<?php

namespace Tests\Feature\Domains\Entries;

use App\Domains\Entries\Actions\UpdateEntry;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateEntryActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_meaningful_change_bumps_last_activity_at(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $entry = Entry::factory()->for($user)->create([
            'last_activity_at' => now()->subDays(10),
        ]);
        $previous = $entry->last_activity_at;

        $updated = app(UpdateEntry::class)->execute($entry, ['title' => 'Renamed']);

        $this->assertSame('Renamed', $updated->title);
        $this->assertTrue($updated->last_activity_at->gt($previous));
    }

    public function test_no_op_change_does_not_bump_activity(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $entry = Entry::factory()->for($user)->create([
            'last_activity_at' => now()->subDays(10),
        ]);
        $previous = $entry->last_activity_at;

        $updated = app(UpdateEntry::class)->execute($entry, ['title' => $entry->title]);

        $this->assertEquals($previous->timestamp, $updated->last_activity_at->timestamp);
    }
}
