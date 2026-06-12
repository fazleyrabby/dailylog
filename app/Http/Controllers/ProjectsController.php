<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProjectsController extends Controller
{
    public function index(): View
    {
        try {
            $projects = Project::query()
                ->whereNull('archived_at')
                ->get();

            $formatted = $projects->map(fn (Project $project) => $this->formatProjectData($project))->toArray();

            return view('pages.projects', [
                'projects' => $formatted,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to load projects index: " . $e->getMessage(), [
                'exception' => $e
            ]);
            abort(500, "Failed to load projects page.");
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'in:orange,blue,emerald,violet,stone,rose,cyan,amber'],
        ]);

        DB::beginTransaction();
        try {
            $project = Project::create([
                'user_id' => auth()->id(),
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
                'description' => $validated['description'] ?? '',
                'status' => 'active',
                'color' => $validated['color'] ?? 'orange',
                'last_activity_at' => now(),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to store project: " . $e->getMessage(), [
                'name' => $validated['name'],
                'exception' => $e
            ]);
            return response()->json(['message' => 'Failed to create project.'], 500);
        }

        return response()->json([
            'project' => $this->formatProjectData($project),
        ], 201);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:active,paused'],
            'color' => ['nullable', 'string', 'in:orange,blue,emerald,violet,stone,rose,cyan,amber'],
        ]);

        DB::beginTransaction();
        try {
            $project->update([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
                'description' => $validated['description'] ?? '',
                'status' => $validated['status'],
                'color' => $validated['color'] ?? 'orange',
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update project {$project->id}: " . $e->getMessage(), [
                'project_id' => $project->id,
                'exception' => $e
            ]);
            return response()->json(['message' => 'Failed to update project.'], 500);
        }

        return response()->json([
            'project' => $this->formatProjectData($project),
        ]);
    }

    public function destroy(Project $project): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Soft-archive project
            $project->update([
                'status' => 'paused',
                'archived_at' => now(),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to archive project {$project->id}: " . $e->getMessage(), [
                'project_id' => $project->id,
                'exception' => $e
            ]);
            return response()->json(['message' => 'Failed to archive project.'], 500);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    private function formatProjectData(Project $project): array
    {
        $tasks = Entry::query()
            ->tasks()
            ->where('project_id', $project->id)
            ->active()
            ->with('taskDetails')
            ->get();

        $totalTasks = $tasks->count();
        $completedTasks = $tasks->filter(fn ($t) => !is_null($t->taskDetails?->completed_at))->count();
        $openTasks = $tasks->filter(fn ($t) => is_null($t->taskDetails?->completed_at));

        $progress = $totalTasks > 0 ? (int) round(($completedTasks / $totalTasks) * 100) : 0;

        $openTasksFormatted = $openTasks->map(fn ($t) => [
            'id' => $t->id,
            'title' => $t->title,
            'priority' => match ($t->taskDetails?->priority) {
                0, 1 => 'low',
                2 => 'medium',
                3 => 'high',
                default => 'medium',
            },
        ])->values()->toArray();

        $notes = Entry::query()
            ->notes()
            ->where('project_id', $project->id)
            ->active()
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->title ?? 'Untitled Note',
                'updated' => $n->updated_at ? $n->updated_at->diffForHumans() : 'Just now',
                'updated_timestamp' => $n->updated_at,
            ]);

        $activity = [];
        $recentlyCompleted = $tasks->filter(fn ($t) => !is_null($t->taskDetails?->completed_at))
            ->sortByDesc(fn ($t) => $t->taskDetails->completed_at)
            ->take(5);

        foreach ($recentlyCompleted as $t) {
            $activity[] = [
                'event' => 'Task completed: ' . $t->title,
                'date' => $t->taskDetails->completed_at->diffForHumans(),
                'timestamp' => $t->taskDetails->completed_at,
            ];
        }

        foreach ($notes as $n) {
            $activity[] = [
                'event' => 'Note added: ' . $n['title'],
                'date' => $n['updated_timestamp'] ? $n['updated_timestamp']->diffForHumans() : 'Just now',
                'timestamp' => $n['updated_timestamp'],
            ];
        }

        usort($activity, function ($a, $b) {
            if (!$a['timestamp'] || !$b['timestamp']) return 0;
            return $b['timestamp']->getTimestamp() <=> $a['timestamp']->getTimestamp();
        });
        $activity = array_slice($activity, 0, 5);

        $wallets = Entry::query()
            ->where('project_id', $project->id)
            ->wallets()
            ->active()
            ->with(['walletDetails'])
            ->get();

        $calculatedWallets = $wallets->map(function (Entry $wallet) {
            $initial = (float) ($wallet->walletDetails?->initial_balance ?? 0.0);

            $incomeSum = (float) \App\Models\WalletTransaction::where('wallet_id', $wallet->id)
                ->where('type', 'income')
                ->sum('amount');

            $expenseSum = (float) \App\Models\WalletTransaction::where('wallet_id', $wallet->id)
                ->where('type', 'expense')
                ->sum('amount');

            $transfersOut = (float) \App\Models\WalletTransaction::where('wallet_id', $wallet->id)
                ->where('type', 'transfer')
                ->sum('amount');

            $transfersIn = (float) \App\Models\WalletTransaction::where('target_wallet_id', $wallet->id)
                ->where('type', 'transfer')
                ->sum('amount');

            $wallet->current_balance = $initial + $incomeSum - $expenseSum - $transfersOut + $transfersIn;
            return $wallet;
        });

        $walletIds = $wallets->pluck('id')->toArray();
        $transactions = [];
        if (!empty($walletIds)) {
            $transactions = \App\Models\WalletTransaction::query()
                ->where(function($query) use ($walletIds) {
                    $query->whereIn('wallet_id', $walletIds)
                          ->orWhereIn('target_wallet_id', $walletIds);
                })
                ->with(['wallet.walletDetails', 'targetWallet.walletDetails'])
                ->orderByDesc('occurred_on')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(fn ($tx) => [
                    'id' => $tx->id,
                    'type' => $tx->type,
                    'occurred_on' => $tx->occurred_on->format('Y-m-d'),
                    'amount' => (float) $tx->amount,
                    'currency' => $tx->wallet?->walletDetails?->currency ?? 'BDT',
                    'description' => $tx->description ?? '',
                    'wallet_title' => $tx->wallet?->title ?? '',
                    'target_wallet_title' => $tx->targetWallet?->title ?? '',
                ])->toArray();
        }

        $walletsFormatted = $calculatedWallets->map(fn ($w) => [
            'id' => $w->id,
            'title' => $w->title,
            'type' => $w->walletDetails?->type ?? 'cash',
            'currency' => $w->walletDetails?->currency ?? 'BDT',
            'balance' => (float) $w->current_balance,
        ])->toArray();

        return [
            'id' => $project->id,
            'name' => $project->name,
            'desc' => $project->description ?? '',
            'status' => $project->status,
            'color' => $project->color ?? 'orange',
            'progress' => $progress,
            'tasks' => $openTasksFormatted,
            'notes' => $notes->map(fn ($n) => [
                'id' => $n['id'],
                'title' => $n['title'],
                'updated' => $n['updated'],
            ])->toArray(),
            'activity' => $activity,
            'financials' => [
                'wallets' => $walletsFormatted,
                'transactions' => $transactions,
            ],
        ];
    }
}
