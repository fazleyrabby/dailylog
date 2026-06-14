@extends('layouts.app')

@section('title', 'Wealth & Wallet Tracker')
@section('header_breadcrumbs', 'DAILYLOG // WEALTH // WALLETS')

@section('content')
<div 
    x-data="{
        txType: 'income',
        txWalletId: '',
        txTargetWalletId: '',
        txCategory: 'other',
        txAmount: '',
        txOccurredOn: '{{ now()->format('Y-m-d') }}',
        txDescription: '',
        walletTitle: '',
        walletType: 'cash',
        walletInitialBalance: '0.00',
        walletCurrency: 'BDT',
        walletProjectId: '',
        isEditingWallet: false,
        editWalletId: '',
        isEditingTransaction: false,
        editTransactionId: '',
        selectedWalletId: {{ $wallets->first()?->id ?? 'null' }},
        filterType: 'all',
        filterCategory: 'all',
        filterStartDate: '',
        filterEndDate: '',
        budgetCategory: '',
        budgetAmount: '',
        budgetMonth: '{{ now()->format('Y-m') }}',

        openBudgetModal(category, currentBudget = '') {
            this.budgetCategory = category;
            this.budgetAmount = currentBudget;
            this.budgetMonth = '{{ now()->format('Y-m') }}';
            this.$dispatch('open-modal', { name: 'budget-modal' });
        },

        matchesFilters(occurredOn, type, walletId, targetWalletId, category) {
            if (this.selectedWalletId !== null && this.selectedWalletId !== 'null' && this.selectedWalletId !== '') {
                const selId = parseInt(this.selectedWalletId);
                if (walletId !== selId && targetWalletId !== selId) {
                    return false;
                }
            }
            if (this.filterType !== 'all') {
                if (type !== this.filterType) {
                    return false;
                }
            }
            if (this.filterCategory !== 'all') {
                if (category !== this.filterCategory) {
                    return false;
                }
            }
            if (this.filterStartDate !== '') {
                if (occurredOn < this.filterStartDate) {
                    return false;
                }
            }
            if (this.filterEndDate !== '') {
                if (occurredOn > this.filterEndDate) {
                    return false;
                }
            }
            return true;
        },

        openNewWallet() {
            this.isEditingWallet = false;
            this.editWalletId = '';
            this.walletTitle = '';
            this.walletType = 'cash';
            this.walletInitialBalance = '0.00';
            this.walletCurrency = 'BDT';
            this.walletProjectId = '';
            this.$dispatch('open-modal', { name: 'wallet-modal' });
        },

        openEditWallet(wallet) {
            this.isEditingWallet = true;
            this.editWalletId = wallet.id;
            this.walletTitle = wallet.title;
            this.walletType = wallet.wallet_details.type;
            this.walletInitialBalance = parseFloat(wallet.wallet_details.initial_balance).toFixed(2);
            this.walletCurrency = wallet.wallet_details.currency;
            this.walletProjectId = wallet.project_id || '';
            this.$dispatch('open-modal', { name: 'wallet-modal' });
        },

        openNewTransaction() {
            this.isEditingTransaction = false;
            this.editTransactionId = '';
            this.txType = 'income';
            this.txWalletId = '';
            this.txTargetWalletId = '';
            this.txCategory = 'other';
            this.txAmount = '';
            this.txOccurredOn = '{{ now()->format('Y-m-d') }}';
            this.txDescription = '';
            this.$dispatch('open-modal', { name: 'transaction-modal' });
        },

        openEditTransaction(tx) {
            this.isEditingTransaction = true;
            this.editTransactionId = tx.id;
            this.txType = tx.type;
            this.txWalletId = tx.wallet_id;
            this.txTargetWalletId = tx.target_wallet_id || '';
            this.txCategory = tx.category || 'other';
            this.txAmount = parseFloat(tx.amount).toFixed(2);
            this.txOccurredOn = tx.occurred_on;
            this.txDescription = tx.description || '';
            this.$dispatch('open-modal', { name: 'transaction-modal' });
        }
    }" 
    class="max-w-6xl mx-auto space-y-6 pb-12"
>
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-4 border-b border-border">
        <div>
            <div class="text-[10px] font-bold text-accent font-mono uppercase tracking-widest">// wealth & financial ledger</div>
            <h1 class="text-xl font-bold tracking-tight text-text-main mt-1">Wallet Tracker</h1>
            <p class="text-xs text-text-muted mt-0.5">Track your net worth, manage financial accounts, and log everyday transactions.</p>
        </div>
        <div class="mt-3 md:mt-0 flex space-x-2">
            <x-ui.button variant="secondary" @click="openNewWallet()">
                + New Wallet
            </x-ui.button>
            <x-ui.button variant="primary" @click="openNewTransaction()">
                ⚡ Add Transaction
            </x-ui.button>
        </div>
    </div>

    <!-- Summary Widgets & Net Worth -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        
        <!-- Net Worth Card -->
        <div class="bg-surface border border-border p-5 rounded-xs flex flex-col justify-between shadow-xs group relative">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-text-subtle font-mono">Total Net Worth</span>
                    <button 
                        @click="$dispatch('open-modal', { name: 'currency-settings-modal' })"
                        class="text-text-subtle hover:text-accent cursor-pointer opacity-0 group-hover:opacity-100 transition-opacity p-0.5 focus:outline-none"
                        title="Currency Settings"
                    >
                        ⚙ Settings
                    </button>
                </div>
                
                <div class="mt-3 space-y-2">
                    <!-- Unified Total -->
                    <div class="flex items-baseline justify-between border-b border-border/40 pb-1.5">
                        <span class="text-xs font-mono font-bold text-accent">{{ $baseCurrency }} (Base)</span>
                        <span class="text-xl font-bold tracking-tight text-text-main">
                            {{ number_format($unifiedNetWorth, 2) }}
                        </span>
                    </div>

                    <!-- Breakdown -->
                    <div class="space-y-1">
                        @foreach($netWorthByCurrency as $currency => $balance)
                            <div class="flex items-baseline justify-between">
                                <span class="text-[10px] font-mono text-text-muted">{{ $currency }}</span>
                                <span class="text-xs font-mono text-text-muted">
                                    {{ number_format($balance, 2) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="text-[9px] font-mono text-text-subtle mt-4">// converted unified net worth</div>
        </div>

        <!-- Monthly Income -->
        <div class="bg-surface border border-border p-5 rounded-xs flex flex-col justify-between shadow-xs">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-success font-mono">This Month Income</span>
                <div class="mt-3 space-y-1.5">
                    @php $hasIncome = false; @endphp
                    @foreach($monthlyStatsByCurrency as $currency => $stats)
                        @foreach($stats->where('type', 'income') as $stat)
                            @php $hasIncome = true; @endphp
                            <div class="flex items-baseline justify-between">
                                <span class="text-xs font-mono font-semibold text-text-muted">{{ $currency }}</span>
                                <span class="text-lg font-bold text-success">
                                    +{{ number_format($stat->total, 2) }}
                                </span>
                            </div>
                        @endforeach
                    @endforeach
                    @if(!$hasIncome)
                        <span class="text-xs text-text-muted italic block py-2">No income logged this month.</span>
                    @endif
                </div>
            </div>
            <div class="text-[9px] font-mono text-text-subtle mt-4">// current calendar month</div>
        </div>

        <!-- Monthly Expense -->
        <div class="bg-surface border border-border p-5 rounded-xs flex flex-col justify-between shadow-xs">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-danger font-mono">This Month Expenses</span>
                <div class="mt-3 space-y-1.5">
                    @php $hasExpense = false; @endphp
                    @foreach($monthlyStatsByCurrency as $currency => $stats)
                        @foreach($stats->where('type', 'expense') as $stat)
                            @php $hasExpense = true; @endphp
                            <div class="flex items-baseline justify-between">
                                <span class="text-xs font-mono font-semibold text-text-muted">{{ $currency }}</span>
                                <span class="text-lg font-bold text-danger">
                                    -{{ number_format($stat->total, 2) }}
                                </span>
                            </div>
                        @endforeach
                    @endforeach
                    @if(!$hasExpense)
                        <span class="text-xs text-text-muted italic block py-2">No expenses logged this month.</span>
                    @endif
                </div>
            </div>
            <div class="text-[9px] font-mono text-text-subtle mt-4">// current calendar month</div>
        </div>

        <!-- Monthly Category Breakdown Card -->
        <div class="bg-surface border border-border p-5 rounded-xs flex flex-col justify-between shadow-xs">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-accent font-mono">Expenses Breakdown</span>
                <div class="mt-3 space-y-3 max-h-[120px] overflow-y-auto pr-1">
                    @forelse($categoryBreakdown as $item)
                        <div class="space-y-1 group">
                            <div class="flex items-center justify-between text-[10px]">
                                <div class="flex items-center space-x-1">
                                    <span class="font-mono uppercase text-text-muted">{{ $item['category'] }}</span>
                                    <button 
                                        @click="openBudgetModal('{{ $item['category'] }}', '{{ $item['budget'] }}')"
                                        class="text-text-subtle hover:text-accent cursor-pointer opacity-0 group-hover:opacity-100 transition-opacity ml-1 p-0.5 focus:outline-none"
                                        title="Set category budget"
                                    >
                                        ✎
                                    </button>
                                </div>
                                <span class="font-mono text-text-muted">
                                    @if($item['budget'] > 0)
                                        <span class="font-bold text-text-main">{{ number_format($item['total'], 2) }}</span> / {{ number_format($item['budget'], 2) }}
                                        <span class="text-[8px] font-bold {{ $item['budgetUsage'] > 100 ? 'text-danger' : ($item['budgetUsage'] > 80 ? 'text-warning' : 'text-success') }}">
                                            ({{ $item['budgetUsage'] }}%)
                                        </span>
                                    @else
                                        <span class="font-bold text-text-main">{{ number_format($item['total'], 2) }}</span>
                                    @endif
                                </span>
                            </div>
                            <div class="w-full bg-surface-2 h-1 rounded-full overflow-hidden border border-border/40">
                                @php
                                    $usagePercent = $item['budget'] > 0 ? min(100, $item['budgetUsage']) : $item['percentage'];
                                    $barColorClass = $item['budget'] > 0 
                                        ? ($item['budgetUsage'] > 100 ? 'bg-danger' : ($item['budgetUsage'] > 80 ? 'bg-warning' : 'bg-success')) 
                                        : 'bg-accent';
                                @endphp
                                <div class="{{ $barColorClass }} h-full transition-all" style="width: {{ $usagePercent }}%"></div>
                            </div>
                        </div>
                    @empty
                        <span class="text-xs text-text-muted italic block py-2">No expenses categorized this month.</span>
                    @endforelse
                </div>
            </div>
            <div class="text-[9px] font-mono text-text-subtle mt-4">// breakdown & budgets for current month</div>
        </div>

    </div>

    <!-- Active Wallets Grid -->
    <div>
        <h3 class="text-xs font-bold uppercase tracking-wider text-text-subtle border-b border-border pb-2 mb-4 font-mono">// Active Wallet Accounts</h3>
        
        @if($wallets->isEmpty())
            <div class="py-12 text-center border border-dashed border-border rounded-sm bg-surface-2/10">
                <span class="text-xl">💳</span>
                <h4 class="text-xs font-bold text-text-main mt-2 uppercase font-mono">No accounts yet</h4>
                <p class="text-xs text-text-muted mt-1">Create your first wallet to start tracking your wealth.</p>
                <x-ui.button variant="secondary" class="mt-4" @click="openNewWallet()">
                    Create Wallet
                </x-ui.button>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($wallets as $wallet)
                    <div 
                        @click="selectedWalletId = (selectedWalletId === {{ $wallet->id }} ? null : {{ $wallet->id }})"
                        :class="selectedWalletId === {{ $wallet->id }} ? 'border-accent ring-1 ring-accent bg-accent-subtle-bg/10' : 'hover:border-accent/30 bg-surface'"
                        class="border border-border rounded-xs p-4 flex flex-col justify-between transition-all group cursor-pointer relative"
                    >
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-[9px] font-bold font-mono px-2 py-0.5 rounded-xs uppercase tracking-wide border border-border bg-surface-2 text-text-muted">
                                    {{ $wallet->walletDetails?->type }}
                                </span>
                                
                                <div class="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity" @click.stop>
                                    <button 
                                        @click="openEditWallet(@js($wallet))" 
                                        class="text-text-subtle hover:text-accent cursor-pointer text-[11px] p-0.5"
                                    >
                                        ✎ Edit
                                    </button>
                                    <span class="text-text-subtle/30 text-[10px]">|</span>
                                    <form action="{{ route('wallet.destroy', $wallet) }}" method="POST" onsubmit="return confirm('Delete this wallet? All transactions associated will be lost.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-text-subtle hover:text-danger cursor-pointer text-[11px] p-0.5">
                                            &times; Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <h4 class="font-bold text-sm text-text-main mt-3 truncate">{{ $wallet->title }}</h4>
                            
                            @if($wallet->project)
                                <div class="text-[10px] text-text-subtle mt-1 font-mono">
                                    @span &middot; @ {{ $wallet->project->name }}
                                </div>
                            @endif
                        </div>
                        
                        <div class="mt-6 pt-3 border-t border-border/40 flex items-baseline justify-between">
                            <span class="text-[10px] font-mono font-bold text-text-muted">{{ $wallet->walletDetails?->currency }}</span>
                            <span class="text-base font-bold font-mono text-text-main">
                                {{ number_format($wallet->current_balance, 2) }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Ledger & Recurring Split Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Transaction Ledger (2/3 width) -->
        <div class="lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between border-b border-border pb-2">
                <h3 class="text-xs font-bold uppercase tracking-wider text-text-subtle font-mono">// Transaction Ledger</h3>
                <template x-if="selectedWalletId !== null || filterType !== 'all' || filterCategory !== 'all' || filterStartDate !== '' || filterEndDate !== ''">
                    <button 
                        @click="selectedWalletId = null; filterType = 'all'; filterCategory = 'all'; filterStartDate = ''; filterEndDate = ''" 
                        class="text-[10px] font-bold uppercase font-mono text-accent hover:underline cursor-pointer"
                    >
                        Clear Filters (Show All)
                    </button>
                </template>
            </div>

            <!-- Filter Controls -->
            <div class="grid grid-cols-1 sm:grid-cols-5 gap-3 mb-4 bg-surface p-3 border border-border rounded-xs">
                <div>
                    <label class="block text-[9px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1">Account Filter</label>
                    <select 
                        x-model="selectedWalletId" 
                        class="w-full text-xs bg-surface-2 border border-border rounded-xs p-1.5 text-text-main focus:outline-none focus:ring-1 focus:ring-accent"
                    >
                        <option :value="null">All Accounts</option>
                        @foreach($wallets as $w)
                            <option value="{{ $w->id }}">{{ $w->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[9px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1">Type Filter</label>
                    <select 
                        x-model="filterType" 
                        class="w-full text-xs bg-surface-2 border border-border rounded-xs p-1.5 text-text-main focus:outline-none focus:ring-1 focus:ring-accent"
                    >
                        <option value="all">All Types</option>
                        <option value="income">Income</option>
                        <option value="expense">Expense</option>
                        <option value="transfer">Transfer</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[9px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1">Category Filter</label>
                    <select 
                        x-model="filterCategory" 
                        class="w-full text-xs bg-surface-2 border border-border rounded-xs p-1.5 text-text-main focus:outline-none focus:ring-1 focus:ring-accent"
                    >
                        <option value="all">All Categories</option>
                        <option value="food">Food</option>
                        <option value="shopping">Shopping</option>
                        <option value="bills">Bills</option>
                        <option value="entertainment">Entertainment</option>
                        <option value="salary">Salary</option>
                        <option value="investment">Investment</option>
                        <option value="travel">Travel</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[9px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1">From Date</label>
                    <input 
                        type="date" 
                        x-model="filterStartDate" 
                        class="w-full text-xs bg-surface-2 border border-border rounded-xs p-1.5 text-text-main focus:outline-none focus:ring-1 focus:ring-accent"
                    />
                </div>

                <div>
                    <label class="block text-[9px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1">To Date</label>
                    <input 
                        type="date" 
                        x-model="filterEndDate" 
                        class="w-full text-xs bg-surface-2 border border-border rounded-xs p-1.5 text-text-main focus:outline-none focus:ring-1 focus:ring-accent"
                    />
                </div>
            </div>
            
            <x-ui.table>
                <x-slot name="thead">
                    <tr>
                        <th class="w-24 px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-text-muted font-mono">Date</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-text-muted font-mono">Type</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-text-muted font-mono">Account</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-text-muted font-mono">Details</th>
                        <th class="text-right px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-text-muted font-mono">Amount</th>
                        <th class="w-16 px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-text-muted font-mono"></th>
                    </tr>
                </x-slot>
                
                @if($transactions->isEmpty())
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-xs text-text-muted italic">
                            No transactions logged yet. Click "Add Transaction" to create one.
                        </td>
                    </tr>
                @else
                    @foreach($transactions as $tx)
                        <tr 
                            x-show="matchesFilters('{{ $tx->occurred_on->format('Y-m-d') }}', '{{ $tx->type }}', {{ $tx->wallet_id }}, {{ $tx->target_wallet_id ?? 'null' }}, '{{ $tx->category ?? 'other' }}')"
                            class="hover:bg-surface-2/10"
                        >
                            <td class="px-4 py-2.5 font-mono text-xxs whitespace-nowrap text-text-subtle align-middle">
                                {{ $tx->occurred_on->format('Y-m-d') }}
                            </td>
                            <td class="px-4 py-2.5 align-middle">
                                @if($tx->type === 'income')
                                     <span class="bg-success/5 text-success border border-success/20 px-1.5 py-0.2 rounded-xs text-[9px] uppercase font-bold font-mono">Income</span>
                                @elseif($tx->type === 'expense')
                                     <span class="bg-danger/5 text-danger border border-danger/20 px-1.5 py-0.2 rounded-xs text-[9px] uppercase font-bold font-mono">Expense</span>
                                @else
                                     <span class="bg-info/5 text-info border border-info/20 px-1.5 py-0.2 rounded-xs text-[9px] uppercase font-bold font-mono">Transfer</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-xs font-semibold text-text-main truncate max-w-[150px] align-middle">
                                @if($tx->type === 'transfer')
                                    <div class="flex items-center space-x-1">
                                        <span class="truncate">{{ $tx->wallet?->title }}</span>
                                        <span class="text-text-subtle font-mono text-[9px]">&rarr;</span>
                                        <span class="truncate">{{ $tx->targetWallet?->title }}</span>
                                    </div>
                                @else
                                    {{ $tx->wallet?->title }}
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-xs text-text-muted truncate max-w-[200px] align-middle" title="{{ $tx->description }}">
                                @if($tx->category && $tx->category !== 'other')
                                    <span class="bg-surface-2 text-text-subtle border border-border px-1.5 py-0.2 rounded-xs text-[8px] uppercase font-bold font-mono mr-1.5">{{ $tx->category }}</span>
                                @endif
                                {{ $tx->description ?: '-' }}
                            </td>
                            <td class="px-4 py-2.5 text-right font-mono text-xs font-bold whitespace-nowrap align-middle">
                                @if($tx->type === 'income')
                                    <span class="text-success">+{{ number_format($tx->amount, 2) }}</span>
                                @elseif($tx->type === 'expense')
                                    <span class="text-danger">-{{ number_format($tx->amount, 2) }}</span>
                                @else
                                    <span class="text-text-main">{{ number_format($tx->amount, 2) }}</span>
                                @endif
                                <span class="text-[9px] font-normal text-text-subtle ml-1 font-sans">
                                    {{ $tx->wallet?->walletDetails?->currency }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-right align-middle" @click.stop>
                                <div class="flex items-center justify-end space-x-1.5">
                                    <button 
                                        @click="openEditTransaction(@js($tx))" 
                                        class="text-text-subtle hover:text-accent cursor-pointer text-xs font-semibold focus:outline-none"
                                        title="Edit transaction"
                                    >
                                        ✎
                                    </button>
                                    <span class="text-text-subtle/30 text-[10px]">|</span>
                                    <form action="{{ route('wallet.transaction.destroy', $tx) }}" method="POST" onsubmit="return confirm('Delete this transaction?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-text-subtle hover:text-danger cursor-pointer text-xs font-semibold px-1 focus:outline-none">
                                            &times;
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @endif
            </x-ui.table>
        </div>

        <!-- Right: Recurring & Subscriptions (1/3 width) -->
        <div class="space-y-4">
            <div class="flex items-center justify-between border-b border-border pb-2">
                <h3 class="text-xs font-bold uppercase tracking-wider text-text-subtle font-mono">// Upcoming & Bills</h3>
                <button 
                    @click="$dispatch('open-modal', { name: 'recurring-modal' })" 
                    class="text-[10px] font-bold uppercase font-mono text-accent hover:underline cursor-pointer"
                >
                    + Add template
                </button>
            </div>

            <div class="space-y-3">
                @forelse($recurring as $rec)
                    <div class="bg-surface border border-border rounded-xs p-3.5 flex flex-col justify-between space-y-3 relative shadow-xs group">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="text-[8px] font-mono font-bold px-1.5 py-0.2 rounded-xs uppercase tracking-wide border border-border bg-surface-2 text-text-muted">
                                    {{ $rec->frequency }}
                                </span>
                                @if($rec->category && $rec->category !== 'other')
                                    <span class="text-[8px] font-mono font-bold px-1.5 py-0.2 rounded-xs uppercase tracking-wide border border-border bg-surface-2 text-text-muted ml-1">
                                        {{ $rec->category }}
                                    </span>
                                @endif
                                <h4 class="font-bold text-xs text-text-main mt-2 truncate">{{ $rec->description }}</h4>
                                <span class="text-[9px] text-text-subtle font-mono block mt-1">
                                    Due: {{ $rec->next_due_date->format('Y-m-d') }} &bull; {{ $rec->wallet?->title }}
                                </span>
                            </div>

                            <form action="{{ route('wallet.recurring.destroy', $rec) }}" method="POST" onsubmit="return confirm('Delete this recurring transaction?');" class="opacity-0 group-hover:opacity-100 focus-within:opacity-100 transition-opacity absolute top-2 right-2">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-text-subtle hover:text-danger cursor-pointer text-xs font-bold px-1 focus:outline-none">
                                    &times;
                                </button>
                            </form>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-border/40">
                            <span class="font-mono text-xs font-bold text-text-main">
                                {{ number_format($rec->amount, 2) }}
                                <span class="text-[9px] font-normal text-text-subtle">{{ $rec->wallet?->walletDetails?->currency }}</span>
                            </span>
                            
                            <form action="{{ route('wallet.recurring.post', $rec) }}" method="POST" class="inline">
                                @csrf
                                <x-ui.button type="submit" variant="secondary" class="font-semibold px-2 py-0.5 text-[10px]">
                                    ⚡ Log Now
                                </x-ui.button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center border border-dashed border-border rounded-xs bg-surface-2/10">
                        <p class="text-xxs text-text-muted italic">No recurring bills or subscriptions scheduled.</p>
                        <x-ui.button variant="secondary" class="mt-3 text-[10px] font-semibold" @click="$dispatch('open-modal', { name: 'recurring-modal' })">
                            Schedule Bill
                        </x-ui.button>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Modals -->

    <!-- Wallet Creation/Editing Modal -->
    <x-ui.modal name="wallet-modal" maxWidth="sm">
        <x-slot:title>
            <span x-text="isEditingWallet ? 'Edit Wallet Account' : 'Create Wallet Account'"></span>
        </x-slot:title>
        
        <form :action="isEditingWallet ? '/wallet/' + editWalletId : '{{ route('wallet.store') }}'" method="POST" id="wallet-form" class="space-y-4">
            @csrf
            <template x-if="isEditingWallet">
                <input type="hidden" name="_method" value="PUT" />
            </template>
            
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1">Account Title</label>
                <x-ui.input 
                    type="text" 
                    name="title" 
                    x-model="walletTitle" 
                    placeholder="e.g. Mutual Trust Bank checking" 
                    required 
                />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1">Account Type</label>
                    <select 
                        name="type" 
                        x-model="walletType" 
                        class="w-full text-xs bg-surface border border-border rounded-xs p-2 text-text-main focus:outline-none focus:ring-1 focus:ring-accent"
                    >
                        <option value="cash">Cash</option>
                        <option value="bank">Bank Account</option>
                        <option value="credit">Credit Card</option>
                        <option value="savings">Savings</option>
                        <option value="investment">Investment</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1">Currency Code</label>
                    <x-ui.input 
                        type="text" 
                        name="currency" 
                        x-model="walletCurrency" 
                        placeholder="BDT" 
                        required 
                    />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1">Initial Balance</label>
                    <x-ui.input 
                        type="number" 
                        step="0.01" 
                        name="initial_balance" 
                        x-model="walletInitialBalance" 
                        required 
                    />
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1">Related Project</label>
                    <select 
                        name="project_id" 
                        x-model="walletProjectId" 
                        class="w-full text-xs bg-surface border border-border rounded-xs p-2 text-text-main focus:outline-none focus:ring-1 focus:ring-accent"
                    >
                        <option value="">None</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>

        <x-slot:footer>
            <x-ui.button type="button" variant="secondary" @click="$dispatch('close-modal', { name: 'wallet-modal' })" class="font-bold cursor-pointer">
                Cancel
            </x-ui.button>
            <x-ui.button type="submit" form="wallet-form" variant="primary" class="font-bold cursor-pointer">
                <span x-text="isEditingWallet ? 'Update Wallet' : 'Create Wallet'"></span>
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <!-- Transaction Modal -->
    <x-ui.modal name="transaction-modal" maxWidth="sm">
        <x-slot:title>
            <span x-text="isEditingTransaction ? 'Edit Ledger Transaction' : 'Log Ledger Transaction'"></span>
        </x-slot:title>
        
        <form :action="isEditingTransaction ? '/wallet/transaction/' + editTransactionId : '{{ route('wallet.transaction.store') }}'" method="POST" id="transaction-form" class="space-y-4">
            @csrf
            <template x-if="isEditingTransaction">
                <input type="hidden" name="_method" value="PUT" />
            </template>
            
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1">Transaction Type</label>
                <div class="grid grid-cols-3 gap-2">
                    <label :class="txType === 'income' ? 'bg-success/10 border-success/40 text-success' : 'bg-surface-2 border-border text-text-muted'" class="flex items-center justify-center py-2 border rounded-xs text-xs font-bold font-mono cursor-pointer transition-all">
                        <input type="radio" name="type" value="income" x-model="txType" class="sr-only" />
                        <span>Income</span>
                    </label>
                    <label :class="txType === 'expense' ? 'bg-danger/10 border-danger/40 text-danger' : 'bg-surface-2 border-border text-text-muted'" class="flex items-center justify-center py-2 border rounded-xs text-xs font-bold font-mono cursor-pointer transition-all">
                        <input type="radio" name="type" value="expense" x-model="txType" class="sr-only" />
                        <span>Expense</span>
                    </label>
                    <label :class="txType === 'transfer' ? 'bg-info/10 border-info/40 text-info' : 'bg-surface-2 border-border text-text-muted'" class="flex items-center justify-center py-2 border rounded-xs text-xs font-bold font-mono cursor-pointer transition-all">
                        <input type="radio" name="type" value="transfer" x-model="txType" class="sr-only" />
                        <span>Transfer</span>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label x-text="txType === 'transfer' ? 'Source Wallet' : 'Wallet Account'" class="block text-[10px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1"></label>
                    <select 
                        name="wallet_id" 
                        x-model="txWalletId" 
                        required 
                        class="w-full text-xs bg-surface border border-border rounded-xs p-2 text-text-main focus:outline-none focus:ring-1 focus:ring-accent"
                    >
                        <option value="">Select Account</option>
                        @foreach($wallets as $w)
                            <option value="{{ $w->id }}">{{ $w->title }} ({{ number_format($w->current_balance, 2) }} {{ $w->walletDetails?->currency }})</option>
                        @endforeach
                    </select>
                </div>

                <div x-show="txType === 'transfer'">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1">Destination Wallet</label>
                    <select 
                        name="target_wallet_id" 
                        x-model="txTargetWalletId" 
                        ::required="txType === 'transfer'" 
                        class="w-full text-xs bg-surface border border-border rounded-xs p-2 text-text-main focus:outline-none focus:ring-1 focus:ring-accent"
                    >
                        <option value="">Select Account</option>
                        @foreach($wallets as $w)
                            <option value="{{ $w->id }}">{{ $w->title }} ({{ number_format($w->current_balance, 2) }} {{ $w->walletDetails?->currency }})</option>
                        @endforeach
                    </select>
                </div>
                
                <div x-show="txType !== 'transfer'">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1">Date Occurred</label>
                    <x-ui.input 
                        type="date" 
                        name="occurred_on" 
                        x-model="txOccurredOn" 
                        required 
                    />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1">Amount</label>
                    <x-ui.input 
                        type="number" 
                        step="0.01" 
                        name="amount" 
                        x-model="txAmount" 
                        placeholder="0.00" 
                        required 
                    />
                </div>

                <div x-show="txType === 'transfer'">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1">Date Occurred</label>
                    <x-ui.input 
                        type="date" 
                        name="occurred_on" 
                        x-model="txOccurredOn" 
                        ::required="txType === 'transfer'" 
                    />
                </div>

                <div x-show="txType !== 'transfer'">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1">Category</label>
                    <select 
                        name="category" 
                        x-model="txCategory" 
                        class="w-full text-xs bg-surface border border-border rounded-xs p-2 text-text-main focus:outline-none focus:ring-1 focus:ring-accent"
                    >
                        <option value="other">Other</option>
                        <option value="food">Food</option>
                        <option value="shopping">Shopping</option>
                        <option value="bills">Bills</option>
                        <option value="entertainment">Entertainment</option>
                        <option value="salary">Salary</option>
                        <option value="investment">Investment</option>
                        <option value="travel">Travel</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1">Description / Notes</label>
                <x-ui.input 
                    type="text" 
                    name="description" 
                    x-model="txDescription" 
                    placeholder="e.g. Grocery shopping, salary payout" 
                />
            </div>
        </form>

        <x-slot:footer>
            <x-ui.button type="button" variant="secondary" @click="$dispatch('close-modal', { name: 'transaction-modal' })" class="font-bold cursor-pointer">
                Cancel
            </x-ui.button>
            <x-ui.button type="submit" form="transaction-form" variant="primary" class="font-bold cursor-pointer">
                <span x-text="isEditingTransaction ? 'Update Transaction' : 'Log Transaction'"></span>
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <!-- Recurring Template Modal -->
    <x-ui.modal name="recurring-modal" maxWidth="sm">
        <x-slot:title>
            <span>Schedule Recurring Transaction</span>
        </x-slot:title>
        
        <form action="{{ route('wallet.recurring.store') }}" method="POST" id="recurring-form" class="space-y-4">
            @csrf
            
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1">Description / Bill Name</label>
                <x-ui.input 
                    type="text" 
                    name="description" 
                    placeholder="e.g. Netflix Subscription, Office Rent" 
                    required 
                />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1">Transaction Type</label>
                    <select 
                        name="type" 
                        class="w-full text-xs bg-surface border border-border rounded-xs p-2 text-text-main focus:outline-none focus:ring-1 focus:ring-accent"
                    >
                        <option value="expense">Expense / Bill</option>
                        <option value="income">Income / Salary</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1">Category</label>
                    <select 
                        name="category" 
                        class="w-full text-xs bg-surface border border-border rounded-xs p-2 text-text-main focus:outline-none focus:ring-1 focus:ring-accent"
                    >
                        <option value="bills">Bills</option>
                        <option value="food">Food</option>
                        <option value="shopping">Shopping</option>
                        <option value="entertainment">Entertainment</option>
                        <option value="salary">Salary</option>
                        <option value="investment">Investment</option>
                        <option value="travel">Travel</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1">Amount</label>
                    <x-ui.input 
                        type="number" 
                        step="0.01" 
                        name="amount" 
                        placeholder="0.00" 
                        required 
                    />
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1">Wallet Account</label>
                    <select 
                        name="wallet_id" 
                        required 
                        class="w-full text-xs bg-surface border border-border rounded-xs p-2 text-text-main focus:outline-none focus:ring-1 focus:ring-accent"
                    >
                        <option value="">Select Account</option>
                        @foreach($wallets as $w)
                            <option value="{{ $w->id }}">{{ $w->title }} ({{ $w->walletDetails?->currency }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1">Frequency</label>
                    <select 
                        name="frequency" 
                        required 
                        class="w-full text-xs bg-surface border border-border rounded-xs p-2 text-text-main focus:outline-none focus:ring-1 focus:ring-accent"
                    >
                        <option value="monthly">Monthly</option>
                        <option value="weekly">Weekly</option>
                        <option value="daily">Daily</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1">Next Due Date</label>
                    <x-ui.input 
                        type="date" 
                        name="next_due_date" 
                        value="{{ now()->format('Y-m-d') }}" 
                        required 
                    />
                </div>
            </div>
        </form>

        <x-slot:footer>
            <x-ui.button type="button" variant="secondary" @click="$dispatch('close-modal', { name: 'recurring-modal' })" class="font-bold cursor-pointer">
                Cancel
            </x-ui.button>
            <x-ui.button type="submit" form="recurring-form" variant="primary" class="font-bold cursor-pointer">
                Schedule
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <!-- Budget Modal -->
    <x-ui.modal name="budget-modal" maxWidth="sm">
        <x-slot:title>
            <span>Set Category Budget Limit</span>
        </x-slot:title>
        
        <form action="{{ route('wallet.budget.store') }}" method="POST" id="budget-form" class="space-y-4">
            @csrf
            
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1">Category</label>
                <input type="text" name="category" x-model="budgetCategory" readonly class="w-full text-xs bg-surface-2 border border-border rounded-xs p-2 text-text-muted select-none focus:outline-none uppercase font-bold font-mono" />
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1">Monthly Limit (BDT)</label>
                <x-ui.input 
                    type="number" 
                    step="0.01" 
                    name="amount" 
                    x-model="budgetAmount"
                    placeholder="0.00" 
                    required 
                />
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1">Target Month</label>
                <x-ui.input 
                    type="month" 
                    name="month" 
                    x-model="budgetMonth"
                    required 
                />
            </div>
        </form>

        <x-slot:footer>
            <x-ui.button type="button" variant="secondary" @click="$dispatch('close-modal', { name: 'budget-modal' })" class="font-bold cursor-pointer">
                Cancel
            </x-ui.button>
            <x-ui.button type="submit" form="budget-form" variant="primary" class="font-bold cursor-pointer">
                Save Budget
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <!-- Currency Settings Modal -->
    <x-ui.modal name="currency-settings-modal" maxWidth="sm">
        <x-slot:title>
            <span>Multi-Currency Settings</span>
        </x-slot:title>
        
        <form action="{{ route('wallet.settings.update') }}" method="POST" id="currency-settings-form" class="space-y-4">
            @csrf
            
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1">Base Currency</label>
                <x-ui.input 
                    type="text" 
                    name="base_currency" 
                    value="{{ $baseCurrency }}" 
                    placeholder="e.g. BDT, USD"
                    required 
                />
                <span class="text-[9px] text-text-subtle block mt-1">All accounts will be converted to this base currency.</span>
            </div>

            <div class="border-t border-border pt-4 space-y-3">
                <span class="block text-[10px] font-bold uppercase tracking-wider text-text-subtle font-mono">// Exchange Rates (Multiplier to Base)</span>
                
                @foreach(['USD', 'EUR', 'BDT'] as $curr)
                    @if($curr !== $baseCurrency)
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-text-subtle font-mono mb-1">1 {{ $curr }} = </label>
                            <div class="flex items-center space-x-2">
                                <x-ui.input 
                                    type="number" 
                                    step="0.0001" 
                                    name="rates[{{ $curr }}]" 
                                    value="{{ $exchangeRates[$curr] ?? 1.0 }}" 
                                    required 
                                />
                                <span class="text-xs text-text-muted font-mono">{{ $baseCurrency }}</span>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </form>

        <x-slot:footer>
            <x-ui.button type="button" variant="secondary" @click="$dispatch('close-modal', { name: 'currency-settings-modal' })" class="font-bold cursor-pointer">
                Cancel
            </x-ui.button>
            <x-ui.button type="submit" form="currency-settings-form" variant="primary" class="font-bold cursor-pointer">
                Save Settings
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

</div>
@endsection
