<?php

namespace Tests\Feature\Http;

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_wallet(): void
    {
        $this->get(route('wallet.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_user_can_access_wallet_index_and_sees_dashboard(): void
    {
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
    }

    public function test_user_can_create_wallet_account(): void
    {
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
    }

    public function test_user_can_log_transaction_and_balance_is_computed_correctly(): void
    {
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

        $this->assertEquals(400.00, $w1->current_balance);
        $this->assertEquals(350.00, $w2->current_balance);
    }

    public function test_user_can_delete_transaction(): void
    {
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
    }

    public function test_user_can_delete_wallet(): void
    {
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
    }

    public function test_user_can_edit_wallet_account(): void
    {
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
    }

    public function test_user_can_edit_transaction(): void
    {
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
    }

    public function test_wallet_details_type_integrity_trigger(): void
    {
        $user = User::factory()->create();
        $nonWallet = Entry::create([
            'user_id' => $user->id,
            'type' => EntryType::Note,
            'title' => 'Just a Note',
            'status' => 'active',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        // Attempting to insert a wallet_details row pointing to a Note entry must trigger PostgreSQL exception
        $nonWallet->walletDetails()->create([
            'type' => 'cash',
            'initial_balance' => 100.00,
            'currency' => 'BDT',
        ]);
    }

    public function test_user_can_log_transaction_with_category(): void
    {
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
    }

    public function test_user_can_edit_transaction_with_category(): void
    {
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
    }

    public function test_index_includes_category_breakdown(): void
    {
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

        $this->assertCount(2, $breakdown);
        $this->assertEquals('shopping', $breakdown[0]['category']);
        $this->assertEquals(75, $breakdown[0]['percentage']);
        $this->assertEquals('food', $breakdown[1]['category']);
        $this->assertEquals(25, $breakdown[1]['percentage']);
    }

    public function test_user_can_create_recurring_transaction_template(): void
    {
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
    }

    public function test_user_can_post_recurring_transaction_and_advance_due_date(): void
    {
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

        $rec = \App\Models\RecurringTransaction::create([
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
    }

    public function test_user_can_delete_recurring_transaction_template(): void
    {
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

        $rec = \App\Models\RecurringTransaction::create([
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
    }

    public function test_user_can_set_and_update_category_budget(): void
    {
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
    }

    public function test_index_calculates_and_returns_budget_usage(): void
    {
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
        \App\Models\Budget::create([
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

        $this->assertNotNull($foodStat);
        $this->assertEquals(200.00, $foodStat['budget']);
        $this->assertEquals(75, $foodStat['budgetUsage']); // 150 / 200 * 100
    }

    public function test_user_can_update_currency_settings(): void
    {
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
        $this->assertEquals('USD', $user->settings['base_currency']);
        $this->assertEquals(0.0085, $user->settings['exchange_rates']['BDT']);
        $this->assertEquals(1.0, $user->settings['exchange_rates']['USD']);
    }

    public function test_index_aggregates_net_worth_with_exchange_rates(): void
    {
        $user = User::factory()->create();
        
        // Save settings to user (base is USD, rates: BDT is 0.01)
        $user->update([
            'settings' => [
                'base_currency' => 'USD',
                'exchange_rates' => [
                    'BDT' => 0.01,
                    'USD' => 1.0,
                ]
            ]
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

        $this->assertEquals(205.00, $response->viewData('unifiedNetWorth')); // 5 + 200
    }
}

