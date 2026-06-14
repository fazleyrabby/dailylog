<?php

namespace App\Http\Controllers;

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\Project;
use App\Models\WalletTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function index(): View
    {
        $wallets = Entry::query()
            ->where('user_id', auth()->id())
            ->wallets()
            ->active()
            ->with(['walletDetails', 'project'])
            ->get();

        $walletIds = $wallets->pluck('id')->toArray();

        $incomeSums = WalletTransaction::query()
            ->whereIn('wallet_id', $walletIds)
            ->where('type', 'income')
            ->groupBy('wallet_id')
            ->select('wallet_id', DB::raw('SUM(amount) as total'))
            ->pluck('total', 'wallet_id');

        $expenseSums = WalletTransaction::query()
            ->whereIn('wallet_id', $walletIds)
            ->where('type', 'expense')
            ->groupBy('wallet_id')
            ->select('wallet_id', DB::raw('SUM(amount) as total'))
            ->pluck('total', 'wallet_id');

        $transfersOut = WalletTransaction::query()
            ->whereIn('wallet_id', $walletIds)
            ->where('type', 'transfer')
            ->groupBy('wallet_id')
            ->select('wallet_id', DB::raw('SUM(amount) as total'))
            ->pluck('total', 'wallet_id');

        $transfersIn = WalletTransaction::query()
            ->whereIn('target_wallet_id', $walletIds)
            ->where('type', 'transfer')
            ->groupBy('target_wallet_id')
            ->select('target_wallet_id', DB::raw('SUM(amount) as total'))
            ->pluck('total', 'target_wallet_id');

        // Calculate balances dynamically
        $calculatedWallets = $wallets->map(function (Entry $wallet) use ($incomeSums, $expenseSums, $transfersOut, $transfersIn) {
            $initial = (float) ($wallet->walletDetails?->initial_balance ?? 0.0);
            $incomeSum = (float) ($incomeSums[$wallet->id] ?? 0.0);
            $expenseSum = (float) ($expenseSums[$wallet->id] ?? 0.0);
            $transferOut = (float) ($transfersOut[$wallet->id] ?? 0.0);
            $transferIn = (float) ($transfersIn[$wallet->id] ?? 0.0);

            $wallet->current_balance = $initial + $incomeSum - $expenseSum - $transferOut + $transferIn;
            return $wallet;
        });

        // Retrieve user currency settings
        $userSettings = auth()->user()->settings ?? [];
        $baseCurrency = $userSettings['base_currency'] ?? 'BDT';
        $exchangeRates = $userSettings['exchange_rates'] ?? ['BDT' => 1.0, 'USD' => 117.0, 'EUR' => 125.0];

        // Ensure base currency has a rate of 1.0
        $exchangeRates[strtoupper($baseCurrency)] = 1.0;

        // Group Net Worth by Currency
        $netWorthByCurrency = $calculatedWallets->groupBy(function ($w) {
            return $w->walletDetails?->currency ?? 'BDT';
        })->map(function ($group) {
            return $group->sum('current_balance');
        })->toArray();

        // Calculate Unified Net Worth
        $unifiedNetWorth = 0.0;
        foreach ($calculatedWallets as $w) {
            $cur = strtoupper($w->walletDetails?->currency ?? 'BDT');
            $rate = (float) ($exchangeRates[$cur] ?? 1.0);
            $unifiedNetWorth += $w->current_balance * $rate;
        }

        // Monthly Stats (based on current month in UTC/Local depending on app settings, using occurred_on date)
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        $monthlyStatsByCurrency = DB::table('wallet_transactions')
            ->join('wallet_details', 'wallet_transactions.wallet_id', '=', 'wallet_details.entry_id')
            ->select('wallet_details.currency', 'wallet_transactions.type', DB::raw('SUM(wallet_transactions.amount) as total'))
            ->where('wallet_transactions.user_id', auth()->id())
            ->whereBetween('wallet_transactions.occurred_on', [$startOfMonth, $endOfMonth])
            ->groupBy('wallet_details.currency', 'wallet_transactions.type')
            ->get()
            ->groupBy('currency');

        $transactions = WalletTransaction::query()
            ->where('user_id', auth()->id())
            ->with(['wallet.walletDetails', 'targetWallet.walletDetails'])
            ->orderByDesc('occurred_on')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $categoryBreakdown = DB::table('wallet_transactions')
            ->select('category', DB::raw('SUM(amount) as total'))
            ->where('user_id', auth()->id())
            ->where('type', 'expense')
            ->whereBetween('occurred_on', [$startOfMonth, $endOfMonth])
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $totalExpenseForMonth = $categoryBreakdown->sum('total');

        $monthStr = now()->format('Y-m');
        $budgets = \App\Models\Budget::where('user_id', auth()->id())
            ->where('month', $monthStr)
            ->get()
            ->keyBy('category');

        $categoryBreakdownFormatted = $categoryBreakdown->map(function ($item) use ($totalExpenseForMonth, $budgets) {
            $catName = $item->category ?: 'other';
            $budgetAmount = isset($budgets[$catName]) ? (float) $budgets[$catName]->amount : null;
            $percentage = $totalExpenseForMonth > 0 ? (int) round(($item->total / $totalExpenseForMonth) * 100) : 0;
            $budgetUsage = $budgetAmount > 0 ? (int) round(($item->total / $budgetAmount) * 100) : null;

            return [
                'category' => $catName,
                'total' => (float) $item->total,
                'percentage' => $percentage,
                'budget' => $budgetAmount,
                'budgetUsage' => $budgetUsage,
            ];
        })->toArray();

        $projects = Project::query()
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->get();

        $recurring = \App\Models\RecurringTransaction::where('user_id', auth()->id())
            ->where('active', true)
            ->with('wallet')
            ->orderBy('next_due_date')
            ->get();

        return view('pages.wallet', [
            'wallets' => $calculatedWallets,
            'netWorthByCurrency' => $netWorthByCurrency,
            'monthlyStatsByCurrency' => $monthlyStatsByCurrency,
            'categoryBreakdown' => $categoryBreakdownFormatted,
            'transactions' => $transactions,
            'projects' => $projects,
            'recurring' => $recurring,
            'unifiedNetWorth' => $unifiedNetWorth,
            'baseCurrency' => $baseCurrency,
            'exchangeRates' => $exchangeRates,
        ]);
    }

    public function storeWallet(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:cash,bank,credit,investment,savings'],
            'initial_balance' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'project_id' => ['nullable', 'exists:projects,id'],
        ]);

        DB::transaction(function () use ($validated) {
            $wallet = Entry::create([
                'user_id' => auth()->id(),
                'type' => EntryType::Wallet,
                'title' => $validated['title'],
                'status' => 'active',
                'project_id' => $validated['project_id'] ?? null,
                'last_activity_at' => now(),
            ]);

            $wallet->walletDetails()->create([
                'type' => $validated['type'],
                'initial_balance' => $validated['initial_balance'],
                'currency' => strtoupper($validated['currency']),
            ]);
        });

        return redirect()->route('wallet.index')->with('success', 'Wallet created successfully!');
    }

    public function storeTransaction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'wallet_id' => ['required', 'exists:entries,id'],
            'target_wallet_id' => ['nullable', 'required_if:type,transfer', 'exists:entries,id', 'different:wallet_id'],
            'type' => ['required', 'string', 'in:income,expense,transfer'],
            'category' => ['nullable', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'occurred_on' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        // Ensure wallet_id belongs to the auth user and is of type wallet
        $wallet = Entry::where('user_id', auth()->id())
            ->wallets()
            ->findOrFail($validated['wallet_id']);

        if ($validated['type'] === 'transfer' && isset($validated['target_wallet_id'])) {
            // Ensure target_wallet_id belongs to the auth user and is of type wallet
            Entry::where('user_id', auth()->id())
                ->wallets()
                ->findOrFail($validated['target_wallet_id']);
        }

        WalletTransaction::create([
            'user_id' => auth()->id(),
            'wallet_id' => $validated['wallet_id'],
            'target_wallet_id' => $validated['type'] === 'transfer' ? $validated['target_wallet_id'] : null,
            'type' => $validated['type'],
            'category' => $validated['type'] === 'transfer' ? 'transfer' : ($validated['category'] ?? 'other'),
            'amount' => $validated['amount'],
            'occurred_on' => $validated['occurred_on'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('wallet.index')->with('success', 'Transaction logged successfully!');
    }

    public function updateTransaction(Request $request, WalletTransaction $transaction): RedirectResponse
    {
        if ($transaction->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'wallet_id' => ['required', 'exists:entries,id'],
            'target_wallet_id' => ['nullable', 'required_if:type,transfer', 'exists:entries,id', 'different:wallet_id'],
            'type' => ['required', 'string', 'in:income,expense,transfer'],
            'category' => ['nullable', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'occurred_on' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        // Ensure wallet_id belongs to the auth user and is of type wallet
        Entry::where('user_id', auth()->id())
            ->wallets()
            ->findOrFail($validated['wallet_id']);

        if ($validated['type'] === 'transfer' && isset($validated['target_wallet_id'])) {
            // Ensure target_wallet_id belongs to the auth user and is of type wallet
            Entry::where('user_id', auth()->id())
                ->wallets()
                ->findOrFail($validated['target_wallet_id']);
        }

        $transaction->update([
            'wallet_id' => $validated['wallet_id'],
            'target_wallet_id' => $validated['type'] === 'transfer' ? $validated['target_wallet_id'] : null,
            'type' => $validated['type'],
            'category' => $validated['type'] === 'transfer' ? 'transfer' : ($validated['category'] ?? 'other'),
            'amount' => $validated['amount'],
            'occurred_on' => $validated['occurred_on'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('wallet.index')->with('success', 'Transaction updated successfully!');
    }

    public function destroyTransaction(WalletTransaction $transaction): RedirectResponse
    {
        if ($transaction->user_id !== auth()->id()) {
            abort(403);
        }

        $transaction->delete();

        return redirect()->route('wallet.index')->with('success', 'Transaction deleted successfully!');
    }

    public function updateWallet(Request $request, Entry $entry): RedirectResponse
    {
        if ($entry->user_id !== auth()->id() || $entry->type !== EntryType::Wallet) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:cash,bank,credit,investment,savings'],
            'initial_balance' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'project_id' => ['nullable', 'exists:projects,id'],
        ]);

        DB::transaction(function () use ($validated, $entry) {
            $entry->update([
                'title' => $validated['title'],
                'project_id' => $validated['project_id'] ?? null,
            ]);

            $entry->walletDetails()->update([
                'type' => $validated['type'],
                'initial_balance' => $validated['initial_balance'],
                'currency' => strtoupper($validated['currency']),
            ]);
        });

        return redirect()->route('wallet.index')->with('success', 'Wallet updated successfully!');
    }

    public function destroyWallet(Entry $entry): RedirectResponse
    {
        if ($entry->user_id !== auth()->id() || $entry->type !== EntryType::Wallet) {
            abort(403);
        }

        $entry->delete();

        return redirect()->route('wallet.index')->with('success', 'Wallet account deleted successfully!');
    }

    public function storeRecurring(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'wallet_id' => ['required', 'exists:entries,id'],
            'type' => ['required', 'string', 'in:income,expense'],
            'category' => ['nullable', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string', 'max:255'],
            'frequency' => ['required', 'string', 'in:daily,weekly,monthly,yearly'],
            'next_due_date' => ['required', 'date'],
        ]);

        Entry::where('user_id', auth()->id())
            ->wallets()
            ->findOrFail($validated['wallet_id']);

        \App\Models\RecurringTransaction::create([
            'user_id' => auth()->id(),
            'wallet_id' => $validated['wallet_id'],
            'type' => $validated['type'],
            'category' => $validated['category'] ?? 'other',
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'frequency' => $validated['frequency'],
            'next_due_date' => $validated['next_due_date'],
            'active' => true,
        ]);

        return redirect()->route('wallet.index')->with('success', 'Recurring transaction template created!');
    }

    public function destroyRecurring(\App\Models\RecurringTransaction $recurring): RedirectResponse
    {
        if ($recurring->user_id !== auth()->id()) {
            abort(403);
        }

        $recurring->delete();

        return redirect()->route('wallet.index')->with('success', 'Recurring transaction template deleted!');
    }

    public function postRecurring(\App\Models\RecurringTransaction $recurring): RedirectResponse
    {
        if ($recurring->user_id !== auth()->id()) {
            abort(403);
        }

        WalletTransaction::create([
            'user_id' => auth()->id(),
            'wallet_id' => $recurring->wallet_id,
            'type' => $recurring->type,
            'category' => $recurring->category,
            'amount' => $recurring->amount,
            'occurred_on' => $recurring->next_due_date->toDateString(),
            'description' => $recurring->description . ' (Recurring)',
        ]);

        $nextDue = $recurring->next_due_date;
        switch ($recurring->frequency) {
            case 'daily':
                $nextDue = $nextDue->addDay();
                break;
            case 'weekly':
                $nextDue = $nextDue->addWeek();
                break;
            case 'monthly':
                $nextDue = $nextDue->addMonth();
                break;
            case 'yearly':
                $nextDue = $nextDue->addYear();
                break;
        }

        $recurring->update([
            'next_due_date' => $nextDue->toDateString(),
        ]);

        return redirect()->route('wallet.index')->with('success', 'Transaction posted successfully!');
    }

    public function storeBudget(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0'],
            'month' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        \App\Models\Budget::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'category' => $validated['category'],
                'month' => $validated['month'],
            ],
            [
                'amount' => $validated['amount'],
            ]
        );

        return redirect()->route('wallet.index')->with('success', 'Monthly budget updated successfully!');
    }

    public function updateCurrencySettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'base_currency' => ['required', 'string', 'max:10'],
            'rates' => ['required', 'array'],
            'rates.*' => ['required', 'numeric', 'min:0.0001'],
        ]);

        $user = auth()->user();
        $settings = $user->settings ?? [];
        $settings['base_currency'] = strtoupper($validated['base_currency']);
        
        $rates = [];
        foreach ($validated['rates'] as $currency => $rate) {
            $rates[strtoupper($currency)] = (float) $rate;
        }
        $rates[strtoupper($validated['base_currency'])] = 1.0;

        $settings['exchange_rates'] = $rates;
        
        $user->update([
            'settings' => $settings,
        ]);

        return redirect()->route('wallet.index')->with('success', 'Currency settings updated successfully!');
    }
}
