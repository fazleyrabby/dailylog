<?php

use App\Enums\EntryType;
use App\Http\ViewComposers\SidebarCountsComposer;
use App\Models\Entry;
use App\Models\Project;
use App\Models\TaskDetails;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('counts match db state for authed user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $task = Entry::factory()->for($user)->type(EntryType::Task)->create(['status' => 'open']);
    TaskDetails::query()->create(['entry_id' => $task->id, 'due_at' => now(), 'priority' => 1]);

    Entry::factory()->for($user)->type(EntryType::Learning)->create(['status' => 'active']);
    Entry::factory()->for($user)->type(EntryType::Learning)->create(['status' => 'paused']);
    Project::factory()->for($user)->create(['status' => 'active']);

    $view = view('layouts.app');
    (new SidebarCountsComposer)->compose($view);
    $counts = $view->getData()['sidebarCounts'];

    expect($counts['tasks'])->toBe(1);
    expect($counts['learning'])->toBe(1);
    expect($counts['projects'])->toBe(1);
    expect($counts['slipping'])->toBe(0);
    expect($counts['wallets'])->toBe(0);
});

test('guest gets zero counts', function () {
    $view = view('layouts.app');
    (new SidebarCountsComposer)->compose($view);
    $counts = $view->getData()['sidebarCounts'];

    expect(array_values($counts))->toBe([0, 0, 0, 0, 0]);
});
