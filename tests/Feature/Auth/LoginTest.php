<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

test('login form renders', function () {
    $this->get(route('auth.login'))->assertOk();
});

test('successful login redirects to dashboard', function () {
    $user = User::factory()->create([
        'password' => Hash::make('secret-pass-1'),
    ]);

    $this->post(route('auth.attempt'), [
        'email' => $user->email,
        'password' => 'secret-pass-1',
    ])->assertRedirect(route('dashboard.index'));

    $this->assertAuthenticatedAs($user);
});

test('invalid credentials show error', function () {
    User::factory()->create(['password' => Hash::make('correct')]);

    $this->from(route('auth.login'))
        ->post(route('auth.attempt'), [
            'email' => 'nope@example.com',
            'password' => 'wrong',
        ])
        ->assertRedirect(route('auth.login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('login throttle after five failures', function () {
    RateLimiter::clear('login:throttle@example.com|127.0.0.1');
    User::factory()->create(['email' => 'throttle@example.com', 'password' => Hash::make('correct')]);

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('auth.attempt'), ['email' => 'throttle@example.com', 'password' => 'wrong']);
    }

    $this->from(route('auth.login'))
        ->post(route('auth.attempt'), ['email' => 'throttle@example.com', 'password' => 'correct'])
        ->assertSessionHasErrors('email');
});
