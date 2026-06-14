<?php

namespace App\Http\Controllers;

use App\Models\SpeedtestLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SpeedtestController extends Controller
{
    public function index(Request $request): \Illuminate\View\View
    {
        $logs = SpeedtestLog::query()
            ->where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('pages.speedtest', [
            'clientIp' => $request->ip(),
            'initialLogs' => $logs,
        ]);
    }

    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->timestamp,
        ]);
    }

    public function download(Request $request): StreamedResponse
    {
        ini_set('max_execution_time', 120);

        // Allow up to 25MB, default to 8MB for a thorough test
        $sizeMB = min((int) $request->query('size', 8), 25);
        $chunkSize = 1024 * 64; // 64KB chunk
        $totalBytes = $sizeMB * 1024 * 1024;
        $chunksCount = (int) ($totalBytes / $chunkSize);

        return response()->stream(function () use ($chunkSize, $chunksCount) {
            for ($i = 0; $i < $chunksCount; $i++) {
                // Generate a fresh random chunk every time so it is 100% uncompressible
                echo random_bytes($chunkSize);
                
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                if (connection_aborted()) {
                    break;
                }
            }
        }, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => $totalBytes,
            'Content-Disposition' => 'attachment; filename="speedtest_download.bin"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Content-Encoding' => 'identity', // Request proxy not to compress
            'X-Accel-Buffering' => 'no', // Disable buffering in Nginx
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $size = $request->header('Content-Length', 0);
        return response()->json([
            'success' => true,
            'size_bytes' => (int) $size,
        ]);
    }

    public function logResult(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'server_name' => ['required', 'string', 'max:255'],
            'latency_ms' => ['required', 'numeric', 'min:0'],
            'download_speed' => ['required', 'numeric', 'min:0'],
            'upload_speed' => ['required', 'numeric', 'min:0'],
            'ip_address' => ['nullable', 'string', 'max:45'],
        ]);

        $log = SpeedtestLog::create([
            'user_id' => auth()->id(),
            'ip_address' => $validated['ip_address'] ?? $request->ip(),
            'server_name' => $validated['server_name'],
            'latency_ms' => $validated['latency_ms'],
            'download_speed' => $validated['download_speed'],
            'upload_speed' => $validated['upload_speed'],
        ]);

        return response()->json([
            'success' => true,
            'log' => $log,
        ]);
    }

    public function history(): JsonResponse
    {
        $logs = SpeedtestLog::query()
            ->where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return response()->json($logs);
    }
}
