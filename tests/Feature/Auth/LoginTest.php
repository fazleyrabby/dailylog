<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_form_renders(): void
    {
        $this->get(route('auth.login'))->assertOk();
    }

    public function test_successful_login_redirects_to_dashboard(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret-pass-1'),
        ]);

        $this->post(route('auth.attempt'), [
            'email' => $user->email,
            'password' => 'secret-pass-1',
        ])->assertRedirect(route('dashboard.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_show_error(): void
    {
        User::factory()->create(['password' => Hash::make('correct')]);

        $this->from(route('auth.login'))
            ->post(route('auth.attempt'), [
                'email' => 'nope@example.com',
                'password' => 'wrong',
            ])
            ->assertRedirect(route('auth.login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_throttle_after_five_failures(): void
    {
        RateLimiter::clear('login:throttle@example.com|127.0.0.1');
        User::factory()->create(['email' => 'throttle@example.com', 'password' => Hash::make('correct')]);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('auth.attempt'), ['email' => 'throttle@example.com', 'password' => 'wrong']);
        }

        $this->from(route('auth.login'))
            ->post(route('auth.attempt'), ['email' => 'throttle@example.com', 'password' => 'correct'])
            ->assertSessionHasErrors('email');
    }
}
