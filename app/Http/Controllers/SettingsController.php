<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $userIps = $user->settings['ip_whitelist'] ?? [];
        $envIps = config('ip-whitelist.ips', []);
        $isWhitelistEnabled = config('ip-whitelist.enabled');

        return view('pages.settings', [
            'lastBackupAt' => cache('supabase_backup_last_at'),
            'currentIp' => $request->ip(),
            'envIps' => $envIps,
            'userIps' => $userIps,
            'isWhitelistEnabled' => $isWhitelistEnabled,
        ]);
    }

    public function updateIpWhitelist(Request $request): RedirectResponse|JsonResponse
    {
        $ips = array_values(array_filter(
            $request->input('ips', []),
            fn (mixed $v): bool => is_string($v) && $v !== '',
        ));

        $request->merge(['ips' => $ips]);

        $validator = Validator::make($request->all(), [
            'ips' => ['present', 'array'],
            'ips.*' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! $this->isValidIpOrCidr($value)) {
                    $fail("The {$attribute} must be a valid IP address or CIDR range.");
                }
            }],
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $validator->errors()->first()], 422);
            }

            return back()->withErrors($validator)->withInput();
        }

        $user = $request->user();
        $settings = $user->settings ?? [];
        $settings['ip_whitelist'] = array_values(array_unique($validator->validated()['ips']));
        $user->settings = $settings;
        $user->save();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'IP whitelist updated successfully.']);
        }

        return back()->with('ip_status', 'IP whitelist updated successfully.');
    }

    private function isValidIpOrCidr(string $value): bool
    {
        // Exact IP (v4 or v6)
        if (filter_var($value, FILTER_VALIDATE_IP)) {
            return true;
        }

        // CIDR notation (v4 only for now)
        if (str_contains($value, '/')) {
            [$subnet, $bits] = explode('/', $value, 2);

            return filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
                && is_numeric($bits)
                && (int) $bits >= 0
                && (int) $bits <= 32;
        }

        return false;
    }
}
