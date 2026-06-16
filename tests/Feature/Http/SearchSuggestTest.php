<?php

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('empty query returns recents grouped by type', function () {
    $user = User::factory()->create();
    Entry::factory()->for($user)->type(EntryType::Note)->count(2)->create();
    Entry::factory()->for($user)->type(EntryType::Task)->count(2)->create();

    $resp = $this->actingAs($user)
        ->getJson(route('partials.search.suggest'))
        ->assertOk()
        ->json();

    expect($resp)->toHaveKey('groups');
    expect($resp['groups'])->toHaveKey('note');
    expect($resp['groups'])->toHaveKey('task');
});

test('query filters results', function () {
    $user = User::factory()->create();
    Entry::factory()->for($user)->type(EntryType::Note)->create(['title' => 'Postgres tuning', 'body' => 'pg']);
    Entry::factory()->for($user)->type(EntryType::Note)->create(['title' => 'Vue notes', 'body' => 'spa']);

    $resp = $this->actingAs($user)
        ->getJson(route('partials.search.suggest', ['q' => 'postgres']))
        ->json();

    $allTitles = collect($resp['groups'])->flatten(1)->pluck('title');
    expect($allTitles->contains('Postgres tuning'))->toBeTrue();
    expect($allTitles->contains('Vue notes'))->toBeFalse();
});
