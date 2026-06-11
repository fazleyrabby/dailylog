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
}
