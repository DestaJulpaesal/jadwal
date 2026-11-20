<?php

namespace Tests\Feature;

use App\Enums\MoneyCategoryType;
use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoneyTransactionArchivedEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_direct_edit_of_transaction_with_archived_wallet_shows_feedback(): void
    {
        $user = User::factory()->create();
        $wallet = $user->wallets()->create(['name' => 'Tunai', 'type' => WalletType::Cash]);
        $category = $user->moneyCategories()->create([
            'name' => 'Makan',
            'type' => MoneyCategoryType::Expense,
        ]);
        $transaction = $user->transactions()->create([
            'wallet_id' => $wallet->id,
            'money_category_id' => $category->id,
            'type' => TransactionType::Expense,
            'amount' => 25_000,
            'occurred_on' => '2026-11-15',
            'note' => 'Makan siang',
        ]);
        $wallet->update(['archived_at' => now()]);
        $index = route('user.money.index', ['section' => 'transactions', 'month' => '2026-11']);

        $this->actingAs($user)
            ->get(route('user.money.transactions.edit', $transaction))
            ->assertRedirect($index)
            ->assertSessionHas('feedback.message', __('ui.feedback.transaction_edit_archived'));

        $this->patch(route('user.money.transactions.update', $transaction), ['note' => 'Catatan baru'])
            ->assertRedirect($index)
            ->assertSessionHas('feedback.message', __('ui.feedback.transaction_edit_archived'));

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'amount' => 25_000,
            'note' => 'Makan siang',
        ]);
    }
}
