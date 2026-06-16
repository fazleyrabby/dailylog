<?php

use App\Models\SpeedtestLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot access speedtest routes', function () {
    $this->get(route('speedtest.ping'))->assertRedirect(route('auth.login'));
    $this->get(route('speedtest.download'))->assertRedirect(route('auth.login'));
    $this->post(route('speedtest.upload'))->assertRedirect(route('auth.login'));
    $this->post(route('speedtest.log'))->assertRedirect(route('auth.login'));
    $this->get(route('speedtest.history'))->assertRedirect(route('auth.login'));
});

test('ping returns ok', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('speedtest.ping'))
        ->assertOk()
        ->assertJson([
            'status' => 'ok',
        ]);
});

test('download streams correct size', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('speedtest.download', ['size' => 1]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/octet-stream');
    $response->assertHeader('Content-Length', 1 * 1024 * 1024);
});

test('upload captures size', function () {
    $user = User::factory()->create();

    $payload = str_repeat('a', 1024 * 100);

    // 100KB payload
    $this->actingAs($user)
        ->post(route('speedtest.upload'), [], [
            'Content-Length' => strlen($payload),
        ])
        ->assertOk()
        ->assertJson([
            'success' => true,
            'size_bytes' => 1024 * 100,
        ]);
});

test('log result saves to database', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('speedtest.log'), [
            'server_name' => 'Singapore',
            'latency_ms' => 45.2,
            'download_speed' => 95.5,
            'upload_speed' => 48.7,
            'ip_address' => '103.153.171.97',
        ])
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $this->assertDatabaseHas('speedtest_logs', [
        'user_id' => $user->id,
        'server_name' => 'Singapore',
        'latency_ms' => 45.2,
        'download_speed' => 95.5,
        'upload_speed' => 48.7,
        'ip_address' => '103.153.171.97',
    ]);
});

test('history returns user logs', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    SpeedtestLog::create([
        'user_id' => $user->id,
        'server_name' => 'Singapore',
        'latency_ms' => 12.5,
        'download_speed' => 150.0,
        'upload_speed' => 80.0,
    ]);

    SpeedtestLog::create([
        'user_id' => $otherUser->id,
        'server_name' => 'Hong Kong',
        'latency_ms' => 60.0,
        'download_speed' => 90.0,
        'upload_speed' => 30.0,
    ]);

    $this->actingAs($user)
        ->get(route('speedtest.history'))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.server_name', 'Singapore');
});
