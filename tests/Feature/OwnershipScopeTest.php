<?php

use App\Models\Entry;
use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('entry queries filtered by authed user', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    Entry::factory()->for($owner)->create(['title' => 'mine']);
    Entry::factory()->for($intruder)->create(['title' => 'theirs']);

    $this->actingAs($owner);

    expect(Entry::query()->count())->toBe(1);
    expect(Entry::query()->value('title'))->toBe('mine');
});

test('project and tag queries filtered by authed user', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    Project::factory()->for($owner)->create();
    Project::factory()->for($intruder)->create();
    Tag::factory()->for($owner)->create();
    Tag::factory()->for($intruder)->create();

    $this->actingAs($owner);

    expect(Project::query()->count())->toBe(1);
    expect(Tag::query()->count())->toBe(1);
});

test('without ownership bypasses scope', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();
    Entry::factory()->for($a)->create();
    Entry::factory()->for($b)->create();

    $this->actingAs($a);

    expect(Entry::query()->withoutOwnership()->count())->toBe(2);
});
