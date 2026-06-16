<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest rejected', function () {
    $this->postJson(route('capture.store'), ['input' => 'task hi'])->assertUnauthorized();
});

test('authed capture returns json payload', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('capture.store'), ['input' => 'task ship the thing #ops'])
        ->assertCreated()
        ->assertJsonStructure(['id', 'type', 'title', 'url'])
        ->assertJson(['type' => 'task', 'title' => 'ship the thing']);
});

test('input required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('capture.store'), ['input' => ''])
        ->assertStatus(422)
        ->assertJsonPath('errors.input.0', fn ($v) => is_string($v));
});
