<?php

namespace App\Http\Controllers;

use App\Domains\Inbox\Queries\InboxQuery;
use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InboxController extends Controller
{
    public function index(InboxQuery $query): View
    {
        $entries = $query->paginate(100);
        
        $formatted = collect($entries->items())->map(function (Entry $entry) {
            return [
                'id' => $entry->id,
                'text' => $entry->title ?? '',
                'type' => $entry->type->value,
                'project_id' => $entry->project_id,
                'project' => $entry->project?->name ?? 'None',
            ];
        })->toArray();

        $projects = Project::query()->whereNull('archived_at')->get(['id', 'name'])->toArray();

        return view('pages.inbox', [
            'entries' => $formatted,
            'projects' => $projects,
        ]);
    }

    public function triage(Request $request, Entry $entry): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:task,note,archive'],
            'project_id' => ['nullable', 'exists:projects,id'],
        ]);

        $action = $validated['action'];
        $projectId = $validated['project_id'] ?? null;

        if ($action === 'archive') {
            $entry->update(['archived_at' => now()]);
        } else {
            $entry->type = $action === 'task' ? EntryType::Task : EntryType::Note;
            $entry->project_id = $projectId;
            $entry->status = $entry->type->defaultStatus();
            $entry->save();

            // If converted to task and taskDetails is missing, create it
            if ($entry->type === EntryType::Task && !$entry->taskDetails()->exists()) {
                $entry->taskDetails()->create([
                    'priority' => 1, // default low
                ]);
            }
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
