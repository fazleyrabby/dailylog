<?php

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('empty search renders landing', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('search.index'))
        ->assertOk()
        ->assertSee('Search across everything');
});

test('search with q returns matches', function () {
    $user = User::factory()->create();
    Entry::factory()->for($user)->type(EntryType::Note)->create([
        'title' => 'Tailwind v4 release notes',
        'body' => 'Lots of perf wins',
    ]);

    $this->actingAs($user)
        ->get(route('search.index', ['q' => 'tailwind']))
        ->assertOk()
        ->assertSee('Tailwind v4 release notes');
});
