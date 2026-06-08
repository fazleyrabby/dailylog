<?php

namespace App\Http\Controllers;

use App\Domains\Capture\Services\CaptureService;
use App\Enums\CapturedVia;
use App\Http\Requests\Capture\StoreCaptureRequest;
use Illuminate\Http\JsonResponse;

class CaptureController extends Controller
{
    public function __construct(private readonly CaptureService $capture)
    {
    }

    public function store(StoreCaptureRequest $request): JsonResponse
    {
        $entry = $this->capture->capture(
            $request->string('input')->toString(),
            CapturedVia::Palette,
        );

        return response()->json([
            'id' => $entry->id,
            'type' => $entry->type->value,
            'title' => $entry->title,
            'url' => $this->detailUrl($entry->type->value, $entry->id),
        ], 201);
    }

    private function detailUrl(string $type, int $id): string
    {
        return match ($type) {
            'task' => "/tasks/{$id}",
            'note' => "/notes/{$id}",
            'journal' => "/journal/{$id}",
            'bookmark' => "/bookmarks/{$id}",
            'quote' => "/quotes/{$id}",
            'resource' => "/resources/{$id}",
            'learning' => "/learning/{$id}",
            'idea' => "/ideas/{$id}",
            default => "/e/{$id}",
        };
    }
}
