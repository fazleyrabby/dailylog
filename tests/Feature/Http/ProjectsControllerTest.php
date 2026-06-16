<?php

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot access projects', function () {
    $this->get(route('projects.index'))
        ->assertRedirect(route('auth.login'));
});

test('index shows database projects and connected elements', function () {
    $user = User::factory()->create();

    $project = Project::create([
        'user_id' => $user->id,
        'name' => 'Laravel OS',
        'slug' => 'laravel-os',
        'status' => 'active',
    ]);

    // Incomplete task
    $openTask = Entry::factory()->for($user)->type(EntryType::Task)->create([
        'title' => 'Open Task Test',
        'project_id' => $project->id,
    ]);
    $openTask->taskDetails()->create([
        'due_at' => now(),
        'priority' => 2,
    ]);

    // Completed task
    $completedTask = Entry::factory()->for($user)->type(EntryType::Task)->create([
        'title' => 'Completed Task Test',
        'project_id' => $project->id,
    ]);
    $completedTask->taskDetails()->create([
        'completed_at' => now()->subDays(2),
        'priority' => 1,
    ]);

    // Linked note
    Entry::factory()->for($user)->type(EntryType::Note)->create([
        'title' => 'Note Test',
        'project_id' => $project->id,
    ]);

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertOk()
        ->assertViewHas('projects', function ($projects) {
            return count($projects) === 1 &&
                $projects[0]['name'] === 'Laravel OS' &&
                $projects[0]['progress'] === 50 && // 1 completed out of 2 total
                count($projects[0]['tasks']) === 1 && // 1 open task
                count($projects[0]['notes']) === 1 && // 1 note
                count($projects[0]['activity']) === 2; // 1 note added + 1 task completed
        });
});

test('store creates project', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('projects.store'), [
            'name' => 'New Project Title',
            'description' => 'New Description details',
            'color' => 'emerald',
        ])
        ->assertStatus(201)
        ->assertJsonPath('project.name', 'New Project Title')
        ->assertJsonPath('project.color', 'emerald');

    $this->assertDatabaseHas('projects', [
        'user_id' => $user->id,
        'name' => 'New Project Title',
        'color' => 'emerald',
        'status' => 'active',
    ]);
});

test('update modifies project details', function () {
    $user = User::factory()->create();
    $project = Project::create([
        'user_id' => $user->id,
        'name' => 'Old Name',
        'slug' => 'old-name',
        'description' => 'Old description',
        'status' => 'active',
        'color' => 'orange',
    ]);

    $this->actingAs($user)
        ->putJson(route('projects.update', $project), [
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'status' => 'paused',
            'color' => 'blue',
        ])
        ->assertOk()
        ->assertJsonPath('project.name', 'Updated Name')
        ->assertJsonPath('project.status', 'paused');

    $fresh = $project->fresh();
    expect($fresh->name)->toEqual('Updated Name');
    expect($fresh->status)->toEqual('paused');
    expect($fresh->color)->toEqual('blue');
});

test('destroy archives project', function () {
    $user = User::factory()->create();
    $project = Project::create([
        'user_id' => $user->id,
        'name' => 'To Archive',
        'slug' => 'to-archive',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->deleteJson(route('projects.destroy', $project))
        ->assertOk()
        ->assertJson(['success' => true]);

    $fresh = $project->fresh();
    expect($fresh->status)->toEqual('paused');
    expect($fresh->archived_at)->not->toBeNull();
});
