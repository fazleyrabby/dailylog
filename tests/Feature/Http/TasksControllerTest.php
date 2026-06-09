<?php

namespace Tests\Feature\Http;

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\User;
use App\Models\TaskDetails;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TasksControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_tasks(): void
    {
        $this->get(route('tasks.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_index_shows_tasks_grouped_correctly(): void
    {
        $user = User::factory()->create();
        
        // 1. Inbox Task (No due date, not completed)
        $inboxEntry = Entry::factory()->for($user)->type(EntryType::Task)->create(['title' => 'Inbox Task']);
        TaskDetails::create([
            'entry_id' => $inboxEntry->id,
            'due_at' => null,
            'completed_at' => null,
            'priority' => 1,
        ]);

        // 2. Today Task (Due today/past, not completed)
        $todayEntry = Entry::factory()->for($user)->type(EntryType::Task)->create(['title' => 'Today Task']);
        TaskDetails::create([
            'entry_id' => $todayEntry->id,
            'due_at' => now(),
            'completed_at' => null,
            'priority' => 2,
        ]);

        // 3. Upcoming Task (Due future, not completed)
        $upcomingEntry = Entry::factory()->for($user)->type(EntryType::Task)->create(['title' => 'Upcoming Task']);
        TaskDetails::create([
            'entry_id' => $upcomingEntry->id,
            'due_at' => now()->addDays(2),
            'completed_at' => null,
            'priority' => 1,
        ]);

        // 4. Completed Task
        $completedEntry = Entry::factory()->for($user)->type(EntryType::Task)->create(['title' => 'Completed Task']);
        TaskDetails::create([
            'entry_id' => $completedEntry->id,
            'due_at' => now(),
            'completed_at' => now(),
            'priority' => 3,
        ]);

        $this->actingAs($user)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertViewHas('tasks', function ($tasks) {
                return count($tasks['inbox']) === 1 &&
                       count($tasks['today']) === 1 &&
                       count($tasks['upcoming']) === 1 &&
                       count($tasks['completed']) === 1;
            });
    }

    public function test_store_creates_task_and_resolves_grammar(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('tasks.store'), [
                'title' => 'Write backups script @DailyLOG #ops !high due:today'
            ])
            ->assertStatus(201)
            ->assertJsonPath('task.title', 'Write backups script');

        $this->assertDatabaseHas('entries', [
            'user_id' => $user->id,
            'title' => 'Write backups script',
            'type' => 'task',
        ]);

        $entry = Entry::where('title', 'Write backups script')->first();
        $this->assertNotNull($entry->taskDetails);
        $this->assertEquals(3, $entry->taskDetails->priority); // 3 = high
        $this->assertNotNull($entry->project);
        $this->assertEquals('dailylog', $entry->project->slug);
        $this->assertTrue($entry->tags->contains('name', 'ops'));
    }

    public function test_toggle_marks_task_completed_and_incompleted(): void
    {
        $user = User::factory()->create();
        $entry = Entry::factory()->for($user)->type(EntryType::Task)->create(['title' => 'Toggled Task']);
        $details = TaskDetails::create([
            'entry_id' => $entry->id,
            'due_at' => null,
            'completed_at' => null,
            'priority' => 1,
        ]);

        // Complete the task
        $this->actingAs($user)
            ->patchJson(route('tasks.toggle', $entry))
            ->assertOk()
            ->assertJsonPath('task.completed', true);

        $this->assertNotNull($details->fresh()->completed_at);

        // Mark incomplete again
        $this->actingAs($user)
            ->patchJson(route('tasks.toggle', $entry))
            ->assertOk()
            ->assertJsonPath('task.completed', false);

        $this->assertNull($details->fresh()->completed_at);
    }

    public function test_update_modifies_task_title(): void
    {
        $user = User::factory()->create();
        $entry = Entry::factory()->for($user)->type(EntryType::Task)->create(['title' => 'Old Title']);
        TaskDetails::create([
            'entry_id' => $entry->id,
            'due_at' => null,
            'completed_at' => null,
            'priority' => 1,
        ]);

        $this->actingAs($user)
            ->putJson(route('tasks.update', $entry), [
                'title' => 'New Title'
            ])
            ->assertOk()
            ->assertJsonPath('task.title', 'New Title');

        $this->assertEquals('New Title', $entry->fresh()->title);

        $this->actingAs($user)
            ->putJson(route('tasks.update', $entry), [
                'priority' => 'high'
            ])
            ->assertOk()
            ->assertJsonPath('task.priority', 'high');

        $this->assertEquals(3, $entry->taskDetails->fresh()->priority);
    }

    public function test_destroy_archives_task(): void
    {
        $user = User::factory()->create();
        $entry = Entry::factory()->for($user)->type(EntryType::Task)->create(['title' => 'Deleted Task']);
        TaskDetails::create([
            'entry_id' => $entry->id,
            'due_at' => null,
            'completed_at' => null,
            'priority' => 1,
        ]);

        $this->actingAs($user)
            ->deleteJson(route('tasks.destroy', $entry))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotNull($entry->fresh()->archived_at);
    }
}
