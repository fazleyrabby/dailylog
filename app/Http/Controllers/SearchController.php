<?php

namespace App\Http\Controllers;

use App\Domains\Search\DTOs\SearchFilter;
use App\Domains\Search\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __construct(private readonly SearchService $search)
    {
    }

    public function index(Request $request): View
    {
        $filter = SearchFilter::fromRequest($request);
        $results = $filter->isEmpty()
            ? null
            : $this->search->search($filter, (int) $request->query('page', 1));

        return view('pages.search', [
            'filter' => $filter,
            'results' => $results,
        ]);
    }
}
