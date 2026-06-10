<?php

namespace Tests\Feature\Http;

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\Project;
use App\Models\SlippingSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlippingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_slipping(): void
    {
        $this->get(route('slipping.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_index_shows_database_slipping_alerts(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Failing Project',
            'slug' => 'failing-project',
            'status' => 'active',
        ]);

        $snap = SlippingSnapshot::create([
            'user_id' => $user->id,
            'subject_type' => Project::class,
            'subject_id' => $project->id,
            'rule' => 'project-inactive',
            'slipping_since' => now()->subDays(25),
            'severity' => 2,
        ]);

        $this->actingAs($user)
            ->get(route('slipping.index'))
            ->assertOk()
            ->assertViewHas('slippingItems', function ($items) {
                return count($items) === 1 && $items[0]['title'] === 'Failing Project' && $items[0]['severity'] === 'medium';
            });
    }

    public function test_resume_updates_subject_heartbeat(): void
    {
        $user = User::factory()->create();
        $entry = Entry::factory()->for($user)->type(EntryType::Note)->create([
            'last_activity_at' => now()->subDays(40),
        ]);

        $snap = SlippingSnapshot::create([
            'user_id' => $user->id,
            'subject_type' => Entry::class,
            'subject_id' => $entry->id,
            'rule' => 'note-inactive',
            'slipping_since' => now()->subDays(40),
            'severity' => 3,
        ]);

        $this->actingAs($user)
            ->postJson(route('slipping.resume', $snap))
            ->assertOk();

        $this->assertNotNull($snap->fresh()->resolved_at);
        $this->assertTrue($entry->fresh()->last_activity_at->isToday());
    }

    public function test_schedule_creates_follow_up_task(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Idle Project',
            'slug' => 'idle-project',
            'status' => 'active',
        ]);

        $snap = SlippingSnapshot::create([
            'user_id' => $user->id,
            'subject_type' => Project::class,
            'subject_id' => $project->id,
            'rule' => 'project-inactive',
            'slipping_since' => now()->subDays(25),
        ]);

        $this->actingAs($user)
            ->postJson(route('slipping.schedule', $snap))
            ->assertOk();

        $this->assertNotNull($snap->fresh()->resolved_at);
        
        $this->assertDatabaseHas('entries', [
            'user_id' => $user->id,
            'type' => 'task',
            'title' => 'Resume work on: Idle Project',
            'project_id' => $project->id,
        ]);
    }

    public function test_snooze_postpones_slipping_alert(): void
    {
        $user = User::factory()->create();
        $entry = Entry::factory()->for($user)->type(EntryType::Note)->create();

        $snap = SlippingSnapshot::create([
            'user_id' => $user->id,
            'subject_type' => Entry::class,
            'subject_id' => $entry->id,
            'rule' => 'note-inactive',
            'slipping_since' => now()->subDays(40),
        ]);

        $this->actingAs($user)
            ->postJson(route('slipping.snooze', $snap))
            ->assertOk();

        $this->assertNotNull($snap->fresh()->snoozed_until);
    }

    public function test_let_go_archives_subject(): void
    {
        $user = User::factory()->create();
        $entry = Entry::factory()->for($user)->type(EntryType::Note)->create();

        $snap = SlippingSnapshot::create([
            'user_id' => $user->id,
            'subject_type' => Entry::class,
            'subject_id' => $entry->id,
            'rule' => 'note-inactive',
            'slipping_since' => now()->subDays(40),
        ]);

        $this->actingAs($user)
            ->postJson(route('slipping.let-go', $snap))
            ->assertOk();

        $this->assertNotNull($snap->fresh()->resolved_at);
        $this->assertNotNull($entry->fresh()->archived_at);
    }
}
