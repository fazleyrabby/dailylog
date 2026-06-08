<?php

namespace App\Http\Controllers\Partials;

use App\Domains\Tags\Queries\TagAutocompleteQuery;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagAutocompleteController extends Controller
{
    public function __invoke(Request $request, TagAutocompleteQuery $query): JsonResponse
    {
        $prefix = $request->string('q')->toString();
        $tags = $query->run($prefix);

        return response()->json(['tags' => $tags]);
    }
}
