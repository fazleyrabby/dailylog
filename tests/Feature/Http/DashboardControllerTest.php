<?php

namespace Tests\Feature\Http;

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\Project;
use App\Models\SlippingSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get(route('dashboard.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_index_shows_aggregated_dashboard_data(): void
    {
        $user = User::factory()->create();

        // Pinned note
        Entry::factory()->for($user)->type(EntryType::Note)->create([
            'title' => 'Pinned Note Test',
            'pinned' => true,
        ]);

        // Active task due today
        $task = Entry::factory()->for($user)->type(EntryType::Task)->create([
            'title' => 'Active Task Test',
        ]);
        $task->taskDetails()->create([
            'due_at' => now(),
            'priority' => 3,
        ]);

        // Slipping snapshot
        SlippingSnapshot::create([
            'user_id' => $user->id,
            'subject_type' => Entry::class,
            'subject_id' => $task->id,
            'slipping_since' => now()->subDays(31),
            'severity' => 3,
            'rule' => 'test_rule',
        ]);

        // Active Project
        Project::create([
            'user_id' => $user->id,
            'name' => 'Test Project',
            'slug' => 'test-project',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertViewHas('todayTasksCount', 1)
            ->assertViewHas('slippingCount', 1)
            ->assertViewHas('focusItems', function ($focus) {
                return count($focus) === 1 && $focus[0]['title'] === 'Pinned Note Test';
            })
            ->assertViewHas('timeline')
            ->assertViewHas('streak', 0);
    }

    public function test_toggle_pin_updates_entry_pin_status(): void
    {
        $user = User::factory()->create();
        $entry = Entry::factory()->for($user)->type(EntryType::Note)->create([
            'pinned' => false,
        ]);

        $this->actingAs($user)
            ->patchJson(route('entries.toggle-pin', $entry))
            ->assertOk()
            ->assertJsonPath('pinned', true);

        $this->assertTrue($entry->fresh()->pinned);

        $this->actingAs($user)
            ->patchJson(route('entries.toggle-pin', $entry))
            ->assertOk()
            ->assertJsonPath('pinned', false);

        $this->assertFalse($entry->fresh()->pinned);
    }
}
