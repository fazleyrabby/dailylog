<?php

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot access inbox', function () {
    $this->get(route('inbox.index'))
        ->assertRedirect(route('auth.login'));
});

test('index shows database inbox items and projects', function () {
    $user = User::factory()->create();
    $project = Project::create([
        'user_id' => $user->id,
        'name' => 'Design Project',
        'slug' => 'design-project',
        'status' => 'active',
    ]);

    // Unfiled note (lands in inbox)
    Entry::factory()->for($user)->type(EntryType::Note)->create([
        'title' => 'Triage this note',
    ]);

    // Filed note (does not land in inbox)
    Entry::factory()->for($user)->type(EntryType::Note)->create([
        'title' => 'Filed note',
        'project_id' => $project->id,
    ]);

    $this->actingAs($user)
        ->get(route('inbox.index'))
        ->assertOk()
        ->assertViewHas('entries', function ($entries) {
            return count($entries) === 1 && $entries[0]['text'] === 'Triage this note';
        })
        ->assertViewHas('projects', function ($projects) {
            return count($projects) === 1 && $projects[0]['name'] === 'Design Project';
        });
});

test('triage archives entry', function () {
    $user = User::factory()->create();
    $entry = Entry::factory()->for($user)->type(EntryType::Note)->create();

    $this->actingAs($user)
        ->patchJson(route('inbox.triage', $entry), [
            'action' => 'archive',
        ])
        ->assertOk();

    expect($entry->fresh()->archived_at)->not->toBeNull();
});

test('triage files as task with project', function () {
    $user = User::factory()->create();
    $project = Project::create([
        'user_id' => $user->id,
        'name' => 'Test Project',
        'slug' => 'test-project',
        'status' => 'active',
    ]);
    $entry = Entry::factory()->for($user)->type(EntryType::Note)->create();

    $this->actingAs($user)
        ->patchJson(route('inbox.triage', $entry), [
            'action' => 'task',
            'project_id' => $project->id,
        ])
        ->assertOk();

    $fresh = $entry->fresh();
    expect($fresh->type)->toEqual(EntryType::Task);
    expect($fresh->project_id)->toEqual($project->id);
    expect($fresh->taskDetails()->exists())->toBeTrue();
});
