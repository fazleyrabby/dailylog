<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    /**
     * Display the activity log page with filters and sorting.
     */
    public function index(Request $request): View
    {
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $type = $request->input('type', 'all');
        $sort = $request->input('sort', 'desc');

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $query = Entry::query()
            ->with(['bookmarkDetails', 'taskDetails', 'resourceDetails', 'tags']);

        if ($type && $type !== 'all') {
            $query->where('type', $type);
        }

        $query->where(function ($q) use ($start, $end) {
            $q->whereBetween('created_at', [$start, $end])
                ->orWhereBetween('updated_at', [$start, $end])
                ->orWhereBetween('archived_at', [$start, $end]);
        });

        $entries = $query->get();
        $events = [];

        foreach ($entries as $entry) {
            // 1. Creation event
            if ($entry->created_at && $entry->created_at->between($start, $end)) {
                $events[] = $this->buildEvent($entry, 'created', $entry->created_at);
            }

            // 2. Update event (only if modified after creation, threshold > 10 seconds)
            if ($entry->updated_at && $entry->created_at &&
                $entry->updated_at->between($start, $end) &&
                abs($entry->updated_at->diffInSeconds($entry->created_at)) > 10) {
                $events[] = $this->buildEvent($entry, 'updated', $entry->updated_at);
            }

            // 3. Archiving/Deletion event
            if ($entry->archived_at && $entry->archived_at->between($start, $end)) {
                $events[] = $this->buildEvent($entry, 'archived', $entry->archived_at);
            }
        }

        // Sort events
        usort($events, function (array $a, array $b) use ($sort): int {
            $timeA = $a['timestamp']->getTimestamp();
            $timeB = $b['timestamp']->getTimestamp();

            return $sort === 'asc' ? $timeA <=> $timeB : $timeB <=> $timeA;
        });

        // Group events by day
        $groupedEvents = [];
        foreach ($events as $event) {
            $dayKey = $event['timestamp']->format('Y-m-d');
            $dayLabel = $event['timestamp']->format('F j, Y');
            if (! isset($groupedEvents[$dayKey])) {
                $groupedEvents[$dayKey] = [
                    'label' => $dayLabel,
                    'items' => [],
                ];
            }
            $groupedEvents[$dayKey]['items'][] = $event;
        }

        // Available entry types for filter dropdown
        $types = [
            'all' => 'All Types',
            'task' => 'Tasks',
            'note' => 'Notes',
            'journal' => 'Journals',
            'bookmark' => 'Bookmarks',
            'quote' => 'Quotes',
            'resource' => 'Resources',
            'learning' => 'Learnings',
            'idea' => 'Ideas',
            'lab' => 'Labs',
            'wallet' => 'Wallets',
        ];

        return view('pages.activity-log', [
            'groupedEvents' => $groupedEvents,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedType' => $type,
            'selectedSort' => $sort,
            'types' => $types,
        ]);
    }

    /**
     * Build formatted event array from entry.
     */
    private function buildEvent(Entry $entry, string $action, Carbon $timestamp): array
    {
        $link = null;
        $desc = null;

        // Resolve view/edit links depending on the entry type
        switch ($entry->type->value) {
            case 'note':
                $link = '/notes';
                break;
            case 'task':
                $link = '/tasks';
                $desc = $entry->taskDetails?->priority !== null ? 'Priority: '.$entry->taskDetails->priority : null;
                break;
            case 'bookmark':
                $link = $entry->bookmarkDetails?->url ?? '/bookmarks';
                $desc = $entry->bookmarkDetails?->site ?? null;
                break;
            case 'resource':
                $link = $entry->resourceDetails?->url ?? '/resources';
                $desc = $entry->resourceDetails?->author ? 'Author: '.$entry->resourceDetails->author : null;
                break;
            case 'journal':
                $link = '/journal';
                break;
            case 'learning':
                $link = '/learning';
                break;
            case 'quote':
                $link = '/quotes';
                break;
            case 'lab':
                $link = '/lab';
                break;
            case 'wallet':
                $link = '/wallet';
                break;
            default:
                $link = '/dashboard';
                break;
        }

        return [
            'id' => $entry->id.'-'.$action,
            'entry_id' => $entry->id,
            'type' => $entry->type->value,
            'title' => $entry->title ?? 'Untitled Item',
            'action' => $action,
            'timestamp' => $timestamp,
            'time' => $timestamp->format('g:i A'),
            'link' => $link,
            'desc' => $desc,
            'tags' => $entry->tags->pluck('name')->toArray(),
        ];
    }
}
