<?php

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot access learning', function () {
    $this->get(route('learning.index'))
        ->assertRedirect(route('auth.login'));
});

test('index shows user learning paths', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $path = Entry::factory()->for($user)->type(EntryType::Learning)->create([
        'title' => 'My AWS Course',
        'last_activity_at' => now(),
    ]);
    $path->learningDetails()->create([
        'kind' => 'course',
        'provider' => 'Laracasts',
        'progress' => 10,
        'completed_units' => 1,
        'total_units' => 10,
        'status' => 'active',
    ]);

    // Connect a related task and note
    $task = Entry::factory()->for($user)->type(EntryType::Task)->create(['title' => 'Review AWS definitions']);
    $note = Entry::factory()->for($user)->type(EntryType::Note)->create(['title' => 'AWS Notes']);

    $path->links()->attach($task->id, ['relation' => 'relates']);
    $path->links()->attach($note->id, ['relation' => 'relates']);

    $otherPath = Entry::factory()->for($otherUser)->type(EntryType::Learning)->create([
        'title' => 'Other Path',
    ]);

    $this->actingAs($user)
        ->get(route('learning.index'))
        ->assertOk()
        ->assertViewHas('learningPaths', function ($paths) {
            return count($paths) === 1 &&
                $paths[0]['title'] === 'My AWS Course' &&
                count($paths[0]['tasks']) === 1 &&
                count($paths[0]['notes']) === 1;
        });
});

test('complete unit increments completed units', function () {
    $user = User::factory()->create();
    $path = Entry::factory()->for($user)->type(EntryType::Learning)->create([
        'title' => 'Active Topic',
    ]);
    $details = $path->learningDetails()->create([
        'kind' => 'topic',
        'provider' => 'Laracasts',
        'progress' => 0,
        'completed_units' => 0,
        'total_units' => 10,
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->patchJson(route('learning.complete-unit', $path))
        ->assertOk()
        ->assertJsonPath('path.completedUnits', 1)
        ->assertJsonPath('path.progress', 10);

    expect($details->fresh()->completed_units)->toEqual(1);
    expect($details->fresh()->progress)->toEqual(10);
});
