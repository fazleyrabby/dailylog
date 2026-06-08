<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $db = $this->probe(fn () => DB::select('SELECT 1'));
        $redis = $this->probe(fn () => Redis::connection()->ping());

        $ok = $db === 'ok' && $redis === 'ok';

        return response()->json(
            ['db' => $db, 'redis' => $redis],
            $ok ? 200 : 503,
        );
    }

    private function probe(callable $fn): string
    {
        try {
            $fn();
            return 'ok';
        } catch (Throwable) {
            return 'fail';
        }
    }
}
