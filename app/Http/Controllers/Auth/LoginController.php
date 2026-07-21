<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $key = 'login:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'token' => 'Too many attempts. Try again in ' . RateLimiter::availableIn($key) . 's.',
            ]);
        }

        $validToken = config('dailylog.login_token') ?? env('LOGIN_TOKEN', '');

        if ($data['token'] !== $validToken) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages([
                'token' => 'Invalid token.',
            ]);
        }

        RateLimiter::clear($key);

        // Log in the first user (single-user app)
        $user = \App\Models\User::query()->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'token' => 'No user found.',
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth.login');
    }
}
