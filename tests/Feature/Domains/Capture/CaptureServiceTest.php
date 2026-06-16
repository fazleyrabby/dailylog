<?php

use App\Domains\Capture\Services\CaptureService;
use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('captures a task with tags project priority due', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $entry = app(CaptureService::class)->capture('task review auth PR due:tomorrow !high #security @sideproject');

    expect($entry->type->value)->toBe('task');
    expect($entry->title)->toBe('review auth PR');
    expect($entry->status)->toBe('open');
    expect($entry->project_id)->not->toBeNull();
    expect(Project::query()->find($entry->project_id)->slug)->toBe('sideproject');
    expect($entry->tags()->pluck('name')->all())->toBe(['security']);
    expect($entry->taskDetails)->not->toBeNull();
    expect($entry->taskDetails->priority)->toBe(3);
    expect($entry->taskDetails->due_at)->not->toBeNull();
});

test('captures a bookmark from bare url', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $entry = app(CaptureService::class)->capture('https://redis.io/streams #redis');

    expect($entry->type->value)->toBe('bookmark');
    expect($entry->bookmarkDetails)->not->toBeNull();
    expect($entry->bookmarkDetails->url)->toBe('https://redis.io/streams');
    expect($entry->bookmarkDetails->site)->toBe('redis.io');
    expect($entry->bookmarkDetails->review_state)->toBe('unread');
    expect($entry->tags()->pluck('name')->all())->toBe(['redis']);
});

test('reuses existing tag', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Tag::factory()->for($user)->create(['name' => 'redis', 'slug' => 'redis']);

    app(CaptureService::class)->capture('note pubsub thoughts #redis');

    expect(Tag::query()->where('name', 'redis')->count())->toBe(1);
});

test('no verb defaults to note', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $entry = app(CaptureService::class)->capture('a random thought');

    expect($entry->type->value)->toBe('note');
    expect($entry->title)->toBe('a random thought');
});
