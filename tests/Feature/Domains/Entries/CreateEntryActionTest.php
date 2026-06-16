<?php

use App\Domains\Entries\Actions\CreateEntry;
use App\Domains\Entries\DTOs\EntryAttributes;
use App\Enums\CapturedVia;
use App\Enums\EntryType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('creates a task with default status', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $entry = app(CreateEntry::class)->execute(new EntryAttributes(
        type: EntryType::Task,
        title: 'Wire up auth',
        body: 'Use Laravel Breeze',
        capturedVia: CapturedVia::Palette,
    ));

    expect($entry->type->value)->toBe('task');
    expect($entry->status)->toBe('open');
    expect($entry->user_id)->toBe($user->id);
    expect($entry->last_activity_at)->not->toBeNull();
    expect($entry->captured_via)->toBe('palette');
});

test('creates an idea with spark status', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $entry = app(CreateEntry::class)->execute(new EntryAttributes(
        type: EntryType::Idea,
        title: 'Personal LLM index',
    ));

    expect($entry->status)->toBe('spark');
});

test('user id resolves from auth', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $entry = app(CreateEntry::class)->execute(new EntryAttributes(
        type: EntryType::Note,
        title: 'A note',
    ));

    expect($entry->user_id)->toBe($user->id);
});
