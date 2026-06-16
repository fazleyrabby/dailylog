<?php

use App\Domains\Entries\Actions\ArchiveEntry;
use App\Domains\Entries\Actions\RestoreEntry;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('archive sets archived at', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $entry = Entry::factory()->for($user)->create();

    app(ArchiveEntry::class)->execute($entry);

    expect($entry->fresh()->archived_at)->not->toBeNull();
});

test('restore clears archived at', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $entry = Entry::factory()->for($user)->archived()->create();

    app(RestoreEntry::class)->execute($entry);

    expect($entry->fresh()->archived_at)->toBeNull();
});
