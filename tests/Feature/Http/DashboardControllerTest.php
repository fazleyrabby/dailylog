<?php

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\Project;
use App\Models\SlippingSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot access dashboard', function () {
    $this->get(route('dashboard.index'))
        ->assertRedirect(route('auth.login'));
});

test('index shows aggregated dashboard data', function () {
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

    // Bookmarks
    $bookmark = Entry::factory()->for($user)->type(EntryType::Bookmark)->create([
        'title' => 'Test Bookmark',
    ]);
    $bookmark->bookmarkDetails()->create([
        'url' => 'https://example.com/test',
        'site' => 'example.com',
        'review_state' => 'unread',
    ]);

    // Resources
    $resource = Entry::factory()->for($user)->type(EntryType::Resource)->create([
        'title' => 'Test Resource',
    ]);
    $resource->resourceDetails()->create([
        'resource_type' => 'book',
        'consume_state' => 'to_consume',
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
        ->assertViewHas('streak', 0)
        ->assertViewHas('recentBookmarksList', function ($list) {
            return count($list) === 1 && $list[0]['title'] === 'Test Bookmark';
        })
        ->assertViewHas('recentResourcesList', function ($list) {
            return count($list) === 1 && $list[0]['title'] === 'Test Resource';
        });
});

test('toggle pin updates entry pin status', function () {
    $user = User::factory()->create();
    $entry = Entry::factory()->for($user)->type(EntryType::Note)->create([
        'pinned' => false,
    ]);

    $this->actingAs($user)
        ->patchJson(route('entries.toggle-pin', $entry))
        ->assertOk()
        ->assertJsonPath('pinned', true);

    expect($entry->fresh()->pinned)->toBeTrue();

    $this->actingAs($user)
        ->patchJson(route('entries.toggle-pin', $entry))
        ->assertOk()
        ->assertJsonPath('pinned', false);

    expect($entry->fresh()->pinned)->toBeFalse();
});
