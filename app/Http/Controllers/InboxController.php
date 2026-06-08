<?php

namespace App\Http\Controllers;

use App\Domains\Inbox\Queries\InboxQuery;
use App\Support\MockData;
use Illuminate\View\View;

class InboxController extends Controller
{
    public function index(InboxQuery $query): View
    {
        $entries = $query->paginate();

        return view('pages.inbox', [
            'entries' => $entries,
            'data' => MockData::all(),
        ]);
    }
}
