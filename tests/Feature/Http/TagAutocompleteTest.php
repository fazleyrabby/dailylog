<?php

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('prefix match returns owner tags only', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    Tag::factory()->for($owner)->create(['name' => 'postgres', 'slug' => 'postgres']);
    Tag::factory()->for($owner)->create(['name' => 'postman', 'slug' => 'postman']);
    Tag::factory()->for($owner)->create(['name' => 'redis', 'slug' => 'redis']);
    Tag::factory()->for($stranger)->create(['name' => 'postcss', 'slug' => 'postcss']);

    $this->actingAs($owner)
        ->getJson(route('partials.tags.autocomplete', ['q' => 'post']))
        ->assertOk()
        ->assertJsonCount(2, 'tags');
});

test('empty query returns first tags', function () {
    $owner = User::factory()->create();
    Tag::factory()->for($owner)->count(3)->create();

    $this->actingAs($owner)
        ->getJson(route('partials.tags.autocomplete'))
        ->assertOk()
        ->assertJsonCount(3, 'tags');
});
