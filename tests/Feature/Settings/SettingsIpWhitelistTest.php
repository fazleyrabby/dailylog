<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SettingsIpWhitelistTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_settings_page_shows_ip_whitelist_data(): void
    {
        $user = User::factory()->create([
            'settings' => ['ip_whitelist' => ['192.168.1.100']],
        ]);

        $response = $this->actingAs($user)->get(route('settings.profile'));

        $response->assertOk();
        $response->assertViewHas('currentIp');
        $response->assertViewHas('userIps', ['192.168.1.100']);
        $response->assertViewHas('envIps');
        $response->assertViewHas('isWhitelistEnabled');
    }

    public function test_authenticated_user_can_add_ips(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('settings.ip-whitelist.update'), [
                'ips' => ['10.0.0.1', '192.168.1.0/24'],
            ]);

        $response->assertOk();
        $response->assertJson(['message' => 'IP whitelist updated successfully.']);

        $user->refresh();
        $this->assertEquals(['10.0.0.1', '192.168.1.0/24'], $user->settings['ip_whitelist']);
    }

    public function test_authenticated_user_can_clear_all_ips(): void
    {
        $user = User::factory()->create([
            'settings' => ['ip_whitelist' => ['10.0.0.1']],
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('settings.ip-whitelist.update'), [
                'ips' => [''],
            ]);

        $response->assertOk();

        $user->refresh();
        $this->assertEquals([], $user->settings['ip_whitelist']);
    }

    public function test_validation_rejects_invalid_ips(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('settings.ip-whitelist.update'), [
                'ips' => ['not-an-ip'],
            ]);

        $response->assertStatus(422);
    }

    public function test_validation_rejects_invalid_cidr(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('settings.ip-whitelist.update'), [
                'ips' => ['10.0.0.0/33'],
            ]);

        $response->assertStatus(422);
    }

    public function test_duplicate_ips_are_deduplicated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('settings.ip-whitelist.update'), [
                'ips' => ['10.0.0.1', '10.0.0.1', '192.168.1.1'],
            ]);

        $response->assertOk();

        $user->refresh();
        $this->assertEquals(['10.0.0.1', '192.168.1.1'], $user->settings['ip_whitelist']);
    }

    public function test_guest_cannot_update_ip_whitelist(): void
    {
        $response = $this->postJson(route('settings.ip-whitelist.update'), [
            'ips' => ['10.0.0.1'],
        ]);

        $response->assertUnauthorized();
    }

    public function test_ipv6_addresses_are_accepted(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('settings.ip-whitelist.update'), [
                'ips' => ['fd7a:115c:a1e0::6a3a:a70a'],
            ]);

        $response->assertOk();

        $user->refresh();
        $this->assertEquals(['fd7a:115c:a1e0::6a3a:a70a'], $user->settings['ip_whitelist']);
    }

    public function test_existing_settings_are_preserved(): void
    {
        $user = User::factory()->create([
            'settings' => ['some_other_key' => 'value'],
        ]);

        $this->actingAs($user)
            ->postJson(route('settings.ip-whitelist.update'), [
                'ips' => ['10.0.0.1'],
            ]);

        $user->refresh();
        $this->assertEquals('value', $user->settings['some_other_key']);
        $this->assertEquals(['10.0.0.1'], $user->settings['ip_whitelist']);
    }
}
