<?php

use App\Domains\Entries\Actions\UpdateEntry;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('meaningful change bumps last activity at', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $entry = Entry::factory()->for($user)->create([
        'last_activity_at' => now()->subDays(10),
    ]);
    $previous = $entry->last_activity_at;

    $updated = app(UpdateEntry::class)->execute($entry, ['title' => 'Renamed']);

    expect($updated->title)->toBe('Renamed');
    expect($updated->last_activity_at->gt($previous))->toBeTrue();
});

test('no op change does not bump activity', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $entry = Entry::factory()->for($user)->create([
        'last_activity_at' => now()->subDays(10),
    ]);
    $previous = $entry->last_activity_at;

    $updated = app(UpdateEntry::class)->execute($entry, ['title' => $entry->title]);

    expect($updated->last_activity_at->timestamp)->toEqual($previous->timestamp);
});
