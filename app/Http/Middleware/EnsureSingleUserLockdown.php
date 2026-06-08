<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleUserLockdown
{
    public function handle(Request $request, Closure $next): Response
    {
        if (User::query()->exists()) {
            abort(404);
        }

        return $next($request);
    }
}
