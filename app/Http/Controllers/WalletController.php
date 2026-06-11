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

        // Calculate balances dynamically
        $calculatedWallets = $wallets->map(function (Entry $wallet) {
            $initial = (float) ($wallet->walletDetails?->initial_balance ?? 0.0);

            $incomeSum = (float) WalletTransaction::where('wallet_id', $wallet->id)
                ->where('type', 'income')
                ->sum('amount');

            $expenseSum = (float) WalletTransaction::where('wallet_id', $wallet->id)
                ->where('type', 'expense')
                ->sum('amount');

            $transfersOut = (float) WalletTransaction::where('wallet_id', $wallet->id)
                ->where('type', 'transfer')
                ->sum('amount');

            $transfersIn = (float) WalletTransaction::where('target_wallet_id', $wallet->id)
                ->where('type', 'transfer')
                ->sum('amount');

            $wallet->current_balance = $initial + $incomeSum - $expenseSum - $transfersOut + $transfersIn;
            return $wallet;
        });

        // Group Net Worth by Currency
        $netWorthByCurrency = $calculatedWallets->groupBy(function ($w) {
            return $w->walletDetails?->currency ?? 'BDT';
        })->map(function ($group) {
            return $group->sum('current_balance');
        })->toArray();

        // If BDT is not present, default it to 0
        if (!isset($netWorthByCurrency['BDT'])) {
            $netWorthByCurrency['BDT'] = 0.00;
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

        $projects = Project::query()
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->get();

        return view('pages.wallet', [
            'wallets' => $calculatedWallets,
            'netWorthByCurrency' => $netWorthByCurrency,
            'monthlyStatsByCurrency' => $monthlyStatsByCurrency,
            'transactions' => $transactions,
            'projects' => $projects,
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
}
