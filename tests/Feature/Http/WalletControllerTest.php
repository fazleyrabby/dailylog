<?php

use App\Enums\EntryType;
use App\Models\Budget;
use App\Models\Entry;
use App\Models\RecurringTransaction;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot access wallet', function () {
    $this->get(route('wallet.index'))
        ->assertRedirect(route('auth.login'));
});

test('user can access wallet index and sees dashboard', function () {
    $user = User::factory()->create();

    $wallet = Entry::create([
        'user_id' => $user->id,
        'type' => EntryType::Wallet,
        'title' => 'My Cash Wallet',
        'status' => 'active',
    ]);
    $wallet->walletDetails()->create([
        'type' => 'cash',
        'initial_balance' => 100.00,
        'currency' => 'BDT',
    ]);

    $this->actingAs($user)
        ->get(route('wallet.index'))
        ->assertOk()
        ->assertViewHas('wallets')
        ->assertViewHas('netWorthByCurrency')
        ->assertViewHas('monthlyStatsByCurrency')
        ->assertViewHas('transactions');
});

test('user can create wallet account', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('wallet.store'), [
            'title' => 'Main Bank Account',
            'type' => 'bank',
            'initial_balance' => 1500.50,
            'currency' => 'BDT',
        ]);

    $response->assertRedirect(route('wallet.index'));

    $this->assertDatabaseHas('entries', [
        'user_id' => $user->id,
        'type' => 'wallet',
        'title' => 'Main Bank Account',
    ]);

    $this->assertDatabaseHas('wallet_details', [
        'type' => 'bank',
        'initial_balance' => 1500.50,
        'currency' => 'BDT',
    ]);
});

test('user can log transaction and balance is computed correctly', function () {
    $user = User::factory()->create();

    // 1. Create source and target wallets
    $wallet1 = Entry::create([
        'user_id' => $user->id,
        'type' => EntryType::Wallet,
        'title' => 'Wallet A',
        'status' => 'active',
    ]);
    $wallet1->walletDetails()->create([
        'type' => 'cash',
        'initial_balance' => 500.00,
        'currency' => 'BDT',
    ]);

    $wallet2 = Entry::create([
        'user_id' => $user->id,
        'type' => EntryType::Wallet,
        'title' => 'Wallet B',
        'status' => 'active',
    ]);
    $wallet2->walletDetails()->create([
        'type' => 'bank',
        'initial_balance' => 200.00,
        'currency' => 'BDT',
    ]);

    // 2. Log Income to Wallet A
    $this->actingAs($user)
        ->post(route('wallet.transaction.store'), [
            'wallet_id' => $wallet1->id,
            'type' => 'income',
            'amount' => 100.00,
            'occurred_on' => now()->toDateString(),
            'description' => 'Freelance payout',
        ])
        ->assertRedirect();

    // 3. Log Expense from Wallet A
    $this->actingAs($user)
        ->post(route('wallet.transaction.store'), [
            'wallet_id' => $wallet1->id,
            'type' => 'expense',
            'amount' => 50.00,
            'occurred_on' => now()->toDateString(),
            'description' => 'Dinner',
        ])
        ->assertRedirect();

    // 4. Transfer from Wallet A to Wallet B
    $this->actingAs($user)
        ->post(route('wallet.transaction.store'), [
            'wallet_id' => $wallet1->id,
            'target_wallet_id' => $wallet2->id,
            'type' => 'transfer',
            'amount' => 150.00,
            'occurred_on' => now()->toDateString(),
            'description' => 'Savings deposit',
        ])
        ->assertRedirect();

    // 5. Assert database transactions exist
    $this->assertDatabaseHas('wallet_transactions', [
        'wallet_id' => $wallet1->id,
        'type' => 'income',
        'amount' => 100.00,
    ]);

    $this->assertDatabaseHas('wallet_transactions', [
        'wallet_id' => $wallet1->id,
        'type' => 'expense',
        'amount' => 50.00,
    ]);

    $this->assertDatabaseHas('wallet_transactions', [
        'wallet_id' => $wallet1->id,
        'target_wallet_id' => $wallet2->id,
        'type' => 'transfer',
        'amount' => 150.00,
    ]);

    // 6. Access index to verify balances calculation:
    // Wallet A Balance: 500 (initial) + 100 (income) - 50 (expense) - 150 (transfer out) = 400
    // Wallet B Balance: 200 (initial) + 150 (transfer in) = 350
    $response = $this->actingAs($user)->get(route('wallet.index'));
    $response->assertOk();

    $wallets = $response->viewData('wallets');
    $w1 = $wallets->firstWhere('id', $wallet1->id);
    $w2 = $wallets->firstWhere('id', $wallet2->id);

    expect($w1->current_balance)->toEqual(400.00);
    expect($w2->current_balance)->toEqual(350.00);
});

test('user can delete transaction', function () {
    $user = User::factory()->create();
    $wallet = Entry::create([
        'user_id' => $user->id,
        'type' => EntryType::Wallet,
        'title' => 'My Wallet',
        'status' => 'active',
    ]);
    $wallet->walletDetails()->create([
        'type' => 'cash',
        'initial_balance' => 100.00,
        'currency' => 'BDT',
    ]);

    $tx = WalletTransaction::create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'amount' => 30.00,
        'occurred_on' => now()->toDateString(),
    ]);

    $this->actingAs($user)
        ->delete(route('wallet.transaction.destroy', $tx))
        ->assertRedirect();

    $this->assertDatabaseMissing('wallet_transactions', [
        'id' => $tx->id,
    ]);
});

test('user can delete wallet', function () {
    $user = User::factory()->create();
    $wallet = Entry::create([
        'user_id' => $user->id,
        'type' => EntryType::Wallet,
        'title' => 'Disposable Wallet',
        'status' => 'active',
    ]);
    $wallet->walletDetails()->create([
        'type' => 'cash',
        'initial_balance' => 0.00,
        'currency' => 'BDT',
    ]);

    $this->actingAs($user)
        ->delete(route('wallet.destroy', $wallet))
        ->assertRedirect();

    $this->assertDatabaseMissing('entries', [
        'id' => $wallet->id,
    ]);
    $this->assertDatabaseMissing('wallet_details', [
        'entry_id' => $wallet->id,
    ]);
});

test('user can edit wallet account', function () {
    $user = User::factory()->create();
    $wallet = Entry::create([
        'user_id' => $user->id,
        'type' => EntryType::Wallet,
        'title' => 'Original Name',
        'status' => 'active',
    ]);
    $wallet->walletDetails()->create([
        'type' => 'cash',
        'initial_balance' => 10.00,
        'currency' => 'BDT',
    ]);

    $response = $this->actingAs($user)
        ->put(route('wallet.update', $wallet), [
            'title' => 'Modified Name',
            'type' => 'bank',
            'initial_balance' => 99.99,
            'currency' => 'USD',
        ]);

    $response->assertRedirect(route('wallet.index'));

    $this->assertDatabaseHas('entries', [
        'id' => $wallet->id,
        'title' => 'Modified Name',
    ]);

    $this->assertDatabaseHas('wallet_details', [
        'entry_id' => $wallet->id,
        'type' => 'bank',
        'initial_balance' => 99.99,
        'currency' => 'USD',
    ]);
});

test('user can edit transaction', function () {
    $user = User::factory()->create();
    $wallet = Entry::create([
        'user_id' => $user->id,
        'type' => EntryType::Wallet,
        'title' => 'My Wallet',
        'status' => 'active',
    ]);
    $wallet->walletDetails()->create([
        'type' => 'cash',
        'initial_balance' => 100.00,
        'currency' => 'BDT',
    ]);

    $tx = WalletTransaction::create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'amount' => 30.00,
        'occurred_on' => now()->toDateString(),
        'description' => 'Original description',
    ]);

    $response = $this->actingAs($user)
        ->put(route('wallet.transaction.update', $tx), [
            'wallet_id' => $wallet->id,
            'type' => 'income',
            'amount' => 50.00,
            'occurred_on' => now()->toDateString(),
            'description' => 'Updated description',
        ]);

    $response->assertRedirect(route('wallet.index'));

    $this->assertDatabaseHas('wallet_transactions', [
        'id' => $tx->id,
        'type' => 'income',
        'amount' => 50.00,
        'description' => 'Updated description',
    ]);
});

test('wallet details type integrity trigger', function () {
    $user = User::factory()->create();
    $nonWallet = Entry::create([
        'user_id' => $user->id,
        'type' => EntryType::Note,
        'title' => 'Just a Note',
        'status' => 'active',
    ]);

    $this->expectException(QueryException::class);

    // Attempting to insert a wallet_details row pointing to a Note entry must trigger PostgreSQL exception
    $nonWallet->walletDetails()->create([
        'type' => 'cash',
        'initial_balance' => 100.00,
        'currency' => 'BDT',
    ]);
});

test('user can log transaction with category', function () {
    $user = User::factory()->create();
    $wallet = Entry::create([
        'user_id' => $user->id,
        'type' => EntryType::Wallet,
        'title' => 'My Wallet',
        'status' => 'active',
    ]);
    $wallet->walletDetails()->create([
        'type' => 'cash',
        'initial_balance' => 100.00,
        'currency' => 'BDT',
    ]);

    $this->actingAs($user)
        ->post(route('wallet.transaction.store'), [
            'wallet_id' => $wallet->id,
            'type' => 'expense',
            'category' => 'food',
            'amount' => 45.50,
            'occurred_on' => now()->toDateString(),
            'description' => 'Burger',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('wallet_transactions', [
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'category' => 'food',
        'amount' => 45.50,
    ]);
});

test('user can edit transaction with category', function () {
    $user = User::factory()->create();
    $wallet = Entry::create([
        'user_id' => $user->id,
        'type' => EntryType::Wallet,
        'title' => 'My Wallet',
        'status' => 'active',
    ]);
    $wallet->walletDetails()->create([
        'type' => 'cash',
        'initial_balance' => 100.00,
        'currency' => 'BDT',
    ]);

    $tx = WalletTransaction::create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'category' => 'food',
        'amount' => 30.00,
        'occurred_on' => now()->toDateString(),
        'description' => 'Burger',
    ]);

    $this->actingAs($user)
        ->put(route('wallet.transaction.update', $tx), [
            'wallet_id' => $wallet->id,
            'type' => 'expense',
            'category' => 'travel',
            'amount' => 35.00,
            'occurred_on' => now()->toDateString(),
            'description' => 'Uber',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('wallet_transactions', [
        'id' => $tx->id,
        'category' => 'travel',
        'amount' => 35.00,
        'description' => 'Uber',
    ]);
});

test('index includes category breakdown', function () {
    $user = User::factory()->create();
    $wallet = Entry::create([
        'user_id' => $user->id,
        'type' => EntryType::Wallet,
        'title' => 'My Wallet',
        'status' => 'active',
    ]);
    $wallet->walletDetails()->create([
        'type' => 'cash',
        'initial_balance' => 1000.00,
        'currency' => 'BDT',
    ]);

    WalletTransaction::create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'category' => 'food',
        'amount' => 100.00,
        'occurred_on' => now()->toDateString(),
    ]);

    WalletTransaction::create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'category' => 'shopping',
        'amount' => 300.00,
        'occurred_on' => now()->toDateString(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('wallet.index'))
        ->assertOk()
        ->assertViewHas('categoryBreakdown');

    $breakdown = $response->viewData('categoryBreakdown');

    expect($breakdown)->toHaveCount(2);
    expect($breakdown[0]['category'])->toEqual('shopping');
    expect($breakdown[0]['percentage'])->toEqual(75);
    expect($breakdown[1]['category'])->toEqual('food');
    expect($breakdown[1]['percentage'])->toEqual(25);
});

test('user can create recurring transaction template', function () {
    $user = User::factory()->create();
    $wallet = Entry::create([
        'user_id' => $user->id,
        'type' => EntryType::Wallet,
        'title' => 'My Wallet',
        'status' => 'active',
    ]);
    $wallet->walletDetails()->create([
        'type' => 'cash',
        'initial_balance' => 100.00,
        'currency' => 'BDT',
    ]);

    $this->actingAs($user)
        ->post(route('wallet.recurring.store'), [
            'wallet_id' => $wallet->id,
            'type' => 'expense',
            'category' => 'bills',
            'amount' => 12.99,
            'description' => 'Spotify Subscription',
            'frequency' => 'monthly',
            'next_due_date' => now()->toDateString(),
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('recurring_transactions', [
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'amount' => 12.99,
        'frequency' => 'monthly',
        'description' => 'Spotify Subscription',
    ]);
});

test('user can post recurring transaction and advance due date', function () {
    $user = User::factory()->create();
    $wallet = Entry::create([
        'user_id' => $user->id,
        'type' => EntryType::Wallet,
        'title' => 'My Wallet',
        'status' => 'active',
    ]);
    $wallet->walletDetails()->create([
        'type' => 'cash',
        'initial_balance' => 100.00,
        'currency' => 'BDT',
    ]);

    $today = now()->startOfDay();

    $rec = RecurringTransaction::create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'category' => 'bills',
        'amount' => 50.00,
        'description' => 'Internet Bill',
        'frequency' => 'monthly',
        'next_due_date' => $today->toDateString(),
    ]);

    $this->actingAs($user)
        ->post(route('wallet.recurring.post', $rec))
        ->assertRedirect();

    // Check transaction was logged
    $this->assertDatabaseHas('wallet_transactions', [
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'amount' => 50.00,
        'description' => 'Internet Bill (Recurring)',
    ]);

    // Check next due date was advanced by 1 month
    $this->assertDatabaseHas('recurring_transactions', [
        'id' => $rec->id,
        'next_due_date' => $today->addMonth()->toDateString(),
    ]);
});

test('user can delete recurring transaction template', function () {
    $user = User::factory()->create();
    $wallet = Entry::create([
        'user_id' => $user->id,
        'type' => EntryType::Wallet,
        'title' => 'My Wallet',
        'status' => 'active',
    ]);
    $wallet->walletDetails()->create([
        'type' => 'cash',
        'initial_balance' => 100.00,
        'currency' => 'BDT',
    ]);

    $rec = RecurringTransaction::create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'category' => 'bills',
        'amount' => 50.00,
        'description' => 'Internet Bill',
        'frequency' => 'monthly',
        'next_due_date' => now()->toDateString(),
    ]);

    $this->actingAs($user)
        ->delete(route('wallet.recurring.destroy', $rec))
        ->assertRedirect();

    $this->assertDatabaseMissing('recurring_transactions', [
        'id' => $rec->id,
    ]);
});

test('user can set and update category budget', function () {
    $user = User::factory()->create();
    $monthStr = now()->format('Y-m');

    $this->actingAs($user)
        ->post(route('wallet.budget.store'), [
            'category' => 'food',
            'amount' => 500.00,
            'month' => $monthStr,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('budgets', [
        'user_id' => $user->id,
        'category' => 'food',
        'amount' => 500.00,
        'month' => $monthStr,
    ]);

    // Update existing budget
    $this->actingAs($user)
        ->post(route('wallet.budget.store'), [
            'category' => 'food',
            'amount' => 600.00,
            'month' => $monthStr,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('budgets', [
        'user_id' => $user->id,
        'category' => 'food',
        'amount' => 600.00,
        'month' => $monthStr,
    ]);
});

test('index calculates and returns budget usage', function () {
    $user = User::factory()->create();
    $monthStr = now()->format('Y-m');

    $wallet = Entry::create([
        'user_id' => $user->id,
        'type' => EntryType::Wallet,
        'title' => 'My Wallet',
        'status' => 'active',
    ]);
    $wallet->walletDetails()->create([
        'type' => 'cash',
        'initial_balance' => 1000.00,
        'currency' => 'BDT',
    ]);

    // Create budget for food
    Budget::create([
        'user_id' => $user->id,
        'category' => 'food',
        'amount' => 200.00,
        'month' => $monthStr,
    ]);

    WalletTransaction::create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'category' => 'food',
        'amount' => 150.00,
        'occurred_on' => now()->toDateString(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('wallet.index'))
        ->assertOk()
        ->assertViewHas('categoryBreakdown');

    $breakdown = $response->viewData('categoryBreakdown');
    $foodStat = collect($breakdown)->firstWhere('category', 'food');

    expect($foodStat)->not->toBeNull();
    expect($foodStat['budget'])->toEqual(200.00);
    expect($foodStat['budgetUsage'])->toEqual(75);
    // 150 / 200 * 100
});

test('user can update currency settings', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('wallet.settings.update'), [
            'base_currency' => 'USD',
            'rates' => [
                'BDT' => 0.0085,
                'EUR' => 1.08,
            ],
        ]);

    $response->assertRedirect(route('wallet.index'));

    $user->refresh();
    expect($user->settings['base_currency'])->toEqual('USD');
    expect($user->settings['exchange_rates']['BDT'])->toEqual(0.0085);
    expect($user->settings['exchange_rates']['USD'])->toEqual(1.0);
});

test('index aggregates net worth with exchange rates', function () {
    $user = User::factory()->create();

    // Save settings to user (base is USD, rates: BDT is 0.01)
    $user->update([
        'settings' => [
            'base_currency' => 'USD',
            'exchange_rates' => [
                'BDT' => 0.01,
                'USD' => 1.0,
            ],
        ],
    ]);

    $wallet1 = Entry::create([
        'user_id' => $user->id,
        'type' => EntryType::Wallet,
        'title' => 'Wallet A',
        'status' => 'active',
    ]);
    $wallet1->walletDetails()->create([
        'type' => 'cash',
        'initial_balance' => 500.00, // 500 BDT = 5 USD
        'currency' => 'BDT',
    ]);

    $wallet2 = Entry::create([
        'user_id' => $user->id,
        'type' => EntryType::Wallet,
        'title' => 'Wallet B',
        'status' => 'active',
    ]);
    $wallet2->walletDetails()->create([
        'type' => 'bank',
        'initial_balance' => 200.00, // 200 USD = 200 USD
        'currency' => 'USD',
    ]);

    $response = $this->actingAs($user)
        ->get(route('wallet.index'))
        ->assertOk()
        ->assertViewHas('unifiedNetWorth');

    expect($response->viewData('unifiedNetWorth'))->toEqual(205.00);
    // 5 + 200
});
