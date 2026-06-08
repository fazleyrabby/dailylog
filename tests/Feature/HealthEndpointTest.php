<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    public function test_health_returns_db_and_redis_ok(): void
    {
        $this->get(route('health.check'))
            ->assertOk()
            ->assertJson(['db' => 'ok', 'redis' => 'ok']);
    }
}
