<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LockdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_owner_command_creates_first_user(): void
    {
        $this->assertDatabaseCount('users', 0);

        $this->artisan('app:create-owner', [
            '--name' => 'Owner',
            '--email' => 'owner@example.com',
            '--password' => 'lockdown-pass',
        ])->assertSuccessful();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', ['email' => 'owner@example.com']);
    }

    public function test_create_owner_refused_when_owner_already_exists(): void
    {
        User::factory()->create();

        $this->artisan('app:create-owner', [
            '--name' => 'Second',
            '--email' => 'second@example.com',
            '--password' => 'lockdown-pass',
        ])->assertFailed();

        $this->assertDatabaseCount('users', 1);
    }

    public function test_register_route_does_not_exist(): void
    {
        $this->get('/register')->assertNotFound();
    }
}
