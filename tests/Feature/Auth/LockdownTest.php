<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('create owner command creates first user', function () {
    $this->assertDatabaseCount('users', 0);

    $this->artisan('app:create-owner', [
        '--name' => 'Owner',
        '--email' => 'owner@example.com',
        '--password' => 'lockdown-pass',
    ])->assertSuccessful();

    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseHas('users', ['email' => 'owner@example.com']);
});

test('create owner refused when owner already exists', function () {
    User::factory()->create();

    $this->artisan('app:create-owner', [
        '--name' => 'Second',
        '--email' => 'second@example.com',
        '--password' => 'lockdown-pass',
    ])->assertFailed();

    $this->assertDatabaseCount('users', 1);
});

test('register route does not exist', function () {
    $this->get('/register')->assertNotFound();
});
