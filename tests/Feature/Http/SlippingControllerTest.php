<?php

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\Project;
use App\Models\SlippingSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot access slipping', function () {
    $this->get(route('slipping.index'))
        ->assertRedirect(route('auth.login'));
});

test('index shows database slipping alerts', function () {
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
});

test('resume updates subject heartbeat', function () {
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

    expect($snap->fresh()->resolved_at)->not->toBeNull();
    expect($entry->fresh()->last_activity_at->isToday())->toBeTrue();
});

test('schedule creates follow up task', function () {
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

    expect($snap->fresh()->resolved_at)->not->toBeNull();

    $this->assertDatabaseHas('entries', [
        'user_id' => $user->id,
        'type' => 'task',
        'title' => 'Resume work on: Idle Project',
        'project_id' => $project->id,
    ]);
});

test('snooze postpones slipping alert', function () {
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

    expect($snap->fresh()->snoozed_until)->not->toBeNull();
});

test('let go archives subject', function () {
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

    expect($snap->fresh()->resolved_at)->not->toBeNull();
    expect($entry->fresh()->archived_at)->not->toBeNull();
});
