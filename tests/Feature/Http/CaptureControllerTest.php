<?php

namespace Tests\Feature\Http;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaptureControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_rejected(): void
    {
        $this->postJson(route('capture.store'), ['input' => 'task hi'])->assertUnauthorized();
    }

    public function test_authed_capture_returns_json_payload(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('capture.store'), ['input' => 'task ship the thing #ops'])
            ->assertCreated()
            ->assertJsonStructure(['id', 'type', 'title', 'url'])
            ->assertJson(['type' => 'task', 'title' => 'ship the thing']);
    }

    public function test_input_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('capture.store'), ['input' => ''])
            ->assertStatus(422)
            ->assertJsonPath('errors.input.0', fn ($v) => is_string($v));
    }
}
