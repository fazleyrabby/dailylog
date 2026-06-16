<?php

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\TaskDetails;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot access tasks', function () {
    $this->get(route('tasks.index'))
        ->assertRedirect(route('auth.login'));
});

test('index shows tasks grouped correctly', function () {
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
});

test('store creates task and resolves grammar', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('tasks.store'), [
            'title' => 'Write backups script @DailyLOG #ops !high due:today',
        ])
        ->assertStatus(201)
        ->assertJsonPath('task.title', 'Write backups script');

    $this->assertDatabaseHas('entries', [
        'user_id' => $user->id,
        'title' => 'Write backups script',
        'type' => 'task',
    ]);

    $entry = Entry::where('title', 'Write backups script')->first();
    expect($entry->taskDetails)->not->toBeNull();
    expect($entry->taskDetails->priority)->toEqual(3);
    // 3 = high
    expect($entry->project)->not->toBeNull();
    expect($entry->project->slug)->toEqual('dailylog');
    expect($entry->tags->contains('name', 'ops'))->toBeTrue();
});

test('toggle marks task completed and incompleted', function () {
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

    expect($details->fresh()->completed_at)->not->toBeNull();

    // Mark incomplete again
    $this->actingAs($user)
        ->patchJson(route('tasks.toggle', $entry))
        ->assertOk()
        ->assertJsonPath('task.completed', false);

    expect($details->fresh()->completed_at)->toBeNull();
});

test('update modifies task title', function () {
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
            'title' => 'New Title',
        ])
        ->assertOk()
        ->assertJsonPath('task.title', 'New Title');

    expect($entry->fresh()->title)->toEqual('New Title');

    $this->actingAs($user)
        ->putJson(route('tasks.update', $entry), [
            'priority' => 'high',
        ])
        ->assertOk()
        ->assertJsonPath('task.priority', 'high');

    expect($entry->taskDetails->fresh()->priority)->toEqual(3);
});

test('destroy archives task', function () {
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

    expect($entry->fresh()->archived_at)->not->toBeNull();
});
