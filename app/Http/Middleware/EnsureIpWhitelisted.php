<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIpWhitelisted
{
    /** @var list<string> Routes that bypass the whitelist (monitoring, health checks). */
    private const BYPASS_PATHS = [
        'health',
        'up',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('ip-whitelist.enabled')) {
            return $next($request);
        }

        if ($this->isBypassRoute($request)) {
            return $next($request);
        }

        $allowedIps = $this->resolveAllowedIps();

        if ($allowedIps === [] || $this->isAllowed($this->resolveClientIp($request), $allowedIps)) {
            return $next($request);
        }

        abort(403, 'Your IP address is not whitelisted.');
    }

    /**
     * Resolve the real client IP. Behind Cloudflare (Tunnel/proxy) the immediate
     * peer is Cloudflare, so $request->ip() returns the proxy address. Cloudflare
     * forwards the true visitor IP in the Cf-Connecting-Ip header; since the
     * origin is only reachable through the tunnel, that header is trustworthy.
     */
    private function resolveClientIp(Request $request): string
    {
        $cfIp = $request->headers->get('Cf-Connecting-Ip');

        if (is_string($cfIp) && $cfIp !== '') {
            return trim($cfIp);
        }

        return (string) $request->ip();
    }

    /**
     * Merge env-locked IPs with user-managed IPs from the database.
     *
     * @return list<string>
     */
    private function resolveAllowedIps(): array
    {
        $envIps = config('ip-whitelist.ips', []);

        $userIps = [];
        $user = User::query()->first();

        if ($user) {
            $userIps = $user->settings['ip_whitelist'] ?? [];
        }

        return array_values(array_unique(array_merge($envIps, $userIps)));
    }

    /**
     * Check whether the given IP matches any entry (exact or CIDR).
     *
     * @param  list<string>  $allowedIps
     */
    private function isAllowed(string $ip, array $allowedIps): bool
    {
        foreach ($allowedIps as $allowed) {
            if ($allowed === $ip) {
                return true;
            }

            if (str_contains($allowed, '/') && $this->ipMatchesCidr($ip, $allowed)) {
                return true;
            }
        }

        return false;
    }

    private function isBypassRoute(Request $request): bool
    {
        foreach (self::BYPASS_PATHS as $path) {
            if ($request->is($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IP address falls within a CIDR range. Supports both IPv4 and
     * IPv6 by comparing the leading bits of the packed (inet_pton) addresses.
     */
    private function ipMatchesCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr, 2);
        $bits = (int) $bits;

        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);

        // Both addresses must be valid and of the same family (4 vs 16 bytes).
        if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }

        $maxBits = strlen($ipBin) * 8;
        if ($bits < 0 || $bits > $maxBits) {
            return false;
        }

        $fullBytes = intdiv($bits, 8);
        $remainderBits = $bits % 8;

        if ($fullBytes > 0 && substr($ipBin, 0, $fullBytes) !== substr($subnetBin, 0, $fullBytes)) {
            return false;
        }

        if ($remainderBits === 0) {
            return true;
        }

        $mask = 0xFF << (8 - $remainderBits) & 0xFF;

        return (ord($ipBin[$fullBytes]) & $mask) === (ord($subnetBin[$fullBytes]) & $mask);
    }
}
