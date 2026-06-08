<?php

namespace App\Http\Controllers\Partials;

use App\Domains\Search\DTOs\SearchResult;
use App\Domains\Search\Services\SearchService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchSuggestController extends Controller
{
    public function __invoke(Request $request, SearchService $search): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $groups = $search->suggest($q);

        $payload = $groups->map(fn ($rows, $type) => $rows->map(fn ($row) => [
            'id' => $row->id,
            'type' => $type,
            'title' => $row->title,
            'url' => SearchResult::detailUrl($type, (int) $row->id),
            'last_activity_at' => $row->last_activity_at,
        ])->values())->toArray();

        return response()->json([
            'query' => $q,
            'groups' => $payload,
        ]);
    }
}
