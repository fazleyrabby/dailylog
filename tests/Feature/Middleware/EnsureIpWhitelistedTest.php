<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EnsureIpWhitelistedTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_middleware_is_disabled_in_non_production(): void
    {
        config(['ip-whitelist.enabled' => false]);
        config(['ip-whitelist.ips' => ['10.0.0.1']]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard.index'))
            ->assertOk();
    }

    public function test_whitelisted_ip_can_access(): void
    {
        config(['ip-whitelist.enabled' => true]);
        config(['ip-whitelist.ips' => ['127.0.0.1']]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard.index'))
            ->assertOk();
    }

    public function test_non_whitelisted_ip_gets_403(): void
    {
        config(['ip-whitelist.enabled' => true]);
        config(['ip-whitelist.ips' => ['10.0.0.1']]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard.index'))
            ->assertForbidden();
    }

    public function test_health_route_bypasses_whitelist(): void
    {
        config(['ip-whitelist.enabled' => true]);
        config(['ip-whitelist.ips' => ['10.0.0.1']]);

        $this->get(route('health.check'))
            ->assertOk();
    }

    public function test_empty_whitelist_allows_all_traffic(): void
    {
        config(['ip-whitelist.enabled' => true]);
        config(['ip-whitelist.ips' => []]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard.index'))
            ->assertOk();
    }

    public function test_user_settings_ips_are_merged_with_env(): void
    {
        config(['ip-whitelist.enabled' => true]);
        config(['ip-whitelist.ips' => ['10.0.0.1']]);

        $user = User::factory()->create([
            'settings' => ['ip_whitelist' => ['127.0.0.1']],
        ]);

        // Request comes from 127.0.0.1 (default in tests), which is in user settings
        $this->actingAs($user)
            ->get(route('dashboard.index'))
            ->assertOk();
    }

    public function test_cidr_matching_allows_ip_in_range(): void
    {
        config(['ip-whitelist.enabled' => true]);
        config(['ip-whitelist.ips' => ['127.0.0.0/8']]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard.index'))
            ->assertOk();
    }

    public function test_cidr_matching_blocks_ip_outside_range(): void
    {
        config(['ip-whitelist.enabled' => true]);
        config(['ip-whitelist.ips' => ['10.0.0.0/8']]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard.index'))
            ->assertForbidden();
    }

    public function test_tailscale_ipv4_cidr_allows_tailnet_device(): void
    {
        config(['ip-whitelist.enabled' => true]);
        config(['ip-whitelist.ips' => ['100.64.0.0/10']]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '100.101.167.10'])
            ->get(route('dashboard.index'))
            ->assertOk();
    }

    public function test_tailscale_ipv6_cidr_allows_tailnet_device(): void
    {
        config(['ip-whitelist.enabled' => true]);
        config(['ip-whitelist.ips' => ['fd7a:115c:a1e0::/48']]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => 'fd7a:115c:a1e0::6a3a:a70a'])
            ->get(route('dashboard.index'))
            ->assertOk();
    }

    public function test_ipv6_outside_cidr_is_blocked(): void
    {
        config(['ip-whitelist.enabled' => true]);
        config(['ip-whitelist.ips' => ['fd7a:115c:a1e0::/48']]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => 'fd7a:ffff:a1e0::1'])
            ->get(route('dashboard.index'))
            ->assertForbidden();
    }

    public function test_login_page_is_blocked_when_ip_not_whitelisted(): void
    {
        config(['ip-whitelist.enabled' => true]);
        config(['ip-whitelist.ips' => ['10.0.0.1']]);

        $this->get(route('auth.login'))
            ->assertForbidden();
    }
}
