<?php

use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('owner can view update delete', function () {
    $owner = User::factory()->create();
    $entry = Entry::factory()->for($owner)->create();

    expect($owner->can('view', $entry))->toBeTrue();
    expect($owner->can('update', $entry))->toBeTrue();
    expect($owner->can('delete', $entry))->toBeTrue();
});

test('other users cannot view update delete', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $entry = Entry::factory()->for($owner)->create();

    expect($stranger->can('view', $entry))->toBeFalse();
    expect($stranger->can('update', $entry))->toBeFalse();
    expect($stranger->can('delete', $entry))->toBeFalse();
});
