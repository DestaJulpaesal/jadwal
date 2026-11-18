<?php

namespace Tests\Feature;

use App\Enums\MoneyCategoryType;
use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Models\User;
use App\Services\MonthlyCategoryReport;
use App\Services\MonthlySummary;
use App\Services\WalletBalance;
use App\Support\MonthRange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_balance_can_be_adjusted_and_the_adjustment_is_accounted_for(): void
    {
        $user = User::factory()->create();
        $wallet = $user->wallets()->create([
            'name' => 'Tunai',
            'type' => WalletType::Cash,
            'opening_balance' => 100_000,
        ]);
        $this->actingAs($user);
        $this->get(route('user.money.index', ['section' => 'wallets']))
            ->assertOk()
            ->assertSee(__('ui.wallet.adjustment.action'))
            ->assertSee(route('user.money.wallets.adjustments.store', $wallet));

        $this->post(route('user.money.wallets.adjustments.store', $wallet), [
            'actual_balance' => '125000',
        ])->assertRedirect()
            ->assertSessionHas('feedback.message', __('ui.feedback.wallet_balance_adjusted'));

        $incomeAdjustment = $user->transactions()->latest('id')->firstOrFail();
        $this->assertSame(TransactionType::Income, $incomeAdjustment->type);
        $this->assertSame(25_000, $incomeAdjustment->amount);
        $this->assertSame('adjustment', $incomeAdjustment->source_type);
        $this->assertSame($wallet->id, $incomeAdjustment->source_id);
        $this->assertSame(__('ui.money.adjustment_note'), $incomeAdjustment->note);
        $this->assertSame(125_000, app(WalletBalance::class)->forWallet($wallet));

        $this->post(route('user.money.wallets.adjustments.store', $wallet), [
            'actual_balance' => '90.000',
        ])->assertRedirect()
            ->assertSessionHas('feedback.message', __('ui.feedback.wallet_balance_adjusted'));

        $expenseAdjustment = $user->transactions()->latest('id')->firstOrFail();
        $this->assertSame(TransactionType::Expense, $expenseAdjustment->type);
        $this->assertSame(35_000, $expenseAdjustment->amount);
        $this->assertSame(90_000, app(WalletBalance::class)->forWallet($wallet));
    }

    public function test_adjusting_to_the_current_balance_does_not_create_a_transaction(): void
    {
        $user = User::factory()->create();
        $wallet = $user->wallets()->create([
            'name' => 'Bank',
            'type' => WalletType::Bank,
            'opening_balance' => 40_000,
        ]);

        $this->actingAs($user)
            ->post(route('user.money.wallets.adjustments.store', $wallet), [
                'actual_balance' => '40.000',
            ])
            ->assertRedirect()
            ->assertSessionHas('feedback.message', __('ui.feedback.wallet_balance_unchanged'));

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_adjustment_transactions_are_omitted_from_monthly_income_expense_and_category_reports(): void
    {
        $user = User::factory()->create();
        $wallet = $user->wallets()->create(['name' => 'Bank', 'type' => WalletType::Bank]);
        $food = $user->moneyCategories()->create([
            'name' => 'Makan',
            'type' => MoneyCategoryType::Expense,
        ]);
        $user->transactions()->create([
            'wallet_id' => $wallet->id,
            'type' => TransactionType::Income,
            'amount' => 200_000,
            'occurred_on' => '2026-11-02',
            'note' => 'Gaji',
        ]);
        $user->transactions()->create([
            'wallet_id' => $wallet->id,
            'type' => TransactionType::Expense,
            'amount' => 50_000,
            'money_category_id' => $food->id,
            'occurred_on' => '2026-11-03',
        ]);
        $user->transactions()->create([
            'wallet_id' => $wallet->id,
            'type' => TransactionType::Income,
            'amount' => 10_000,
            'occurred_on' => '2026-11-04',
            'source_type' => 'adjustment',
            'source_id' => $wallet->id,
        ]);
        $user->transactions()->create([
            'wallet_id' => $wallet->id,
            'type' => TransactionType::Expense,
            'amount' => 15_000,
            'money_category_id' => $food->id,
            'occurred_on' => '2026-11-05',
            'source_type' => 'adjustment',
            'source_id' => $wallet->id,
        ]);

        $this->assertSame(
            ['income' => 200_000, 'expense' => 50_000, 'net' => 150_000],
            app(MonthlySummary::class)->forMonth($user, '2026-11')
        );
        $categories = app(MonthlyCategoryReport::class)->topExpenses(
            $user,
            MonthRange::fromString('2026-11')
        );
        $this->assertSame(50_000, (int) $categories->first()->total);
    }

    public function test_adjustments_cannot_be_edited_but_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $wallet = $user->wallets()->create([
            'name' => 'Bank',
            'type' => WalletType::Bank,
            'opening_balance' => 100_000,
        ]);
        $adjustment = $user->transactions()->create([
            'wallet_id' => $wallet->id,
            'type' => TransactionType::Expense,
            'amount' => 20_000,
            'occurred_on' => '2026-11-05',
            'source_type' => 'adjustment',
            'source_id' => $wallet->id,
            'note' => __('ui.money.adjustment_note'),
        ]);

        $this->actingAs($user)
            ->get(route('user.money.index', ['section' => 'transactions', 'month' => '2026-11']))
            ->assertOk()
            ->assertSee(__('ui.money.adjustment_label'));

        $this->get(route('user.money.transactions.edit', $adjustment))->assertNotFound();

        $this->patch(route('user.money.transactions.update', $adjustment), [
            'type' => TransactionType::Income->value,
            'amount' => 20_000,
            'wallet_id' => $wallet->id,
            'occurred_on' => '2026-11-05',
        ])->assertNotFound();

        $this->delete(route('user.money.transactions.destroy', $adjustment))
            ->assertRedirect()
            ->assertSessionHas('feedback.message', __('ui.feedback.transaction_deleted'));

        $this->assertDatabaseMissing('transactions', ['id' => $adjustment->id]);
        $this->assertSame(100_000, app(WalletBalance::class)->forWallet($wallet));
    }

    public function test_adjustments_reject_foreign_archived_and_out_of_range_wallets_or_amounts(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $foreignWallet = $otherUser->wallets()->create([
            'name' => 'Milik orang lain',
            'type' => WalletType::Cash,
        ]);
        $archivedWallet = $owner->wallets()->create([
            'name' => 'Arsip',
            'type' => WalletType::Cash,
            'archived_at' => now(),
        ]);
        $wallet = $owner->wallets()->create(['name' => 'Tunai', 'type' => WalletType::Cash]);

        $this->actingAs($owner)
            ->post(route('user.money.wallets.adjustments.store', $foreignWallet), [
                'actual_balance' => 1_000,
            ])
            ->assertForbidden();

        $this->post(route('user.money.wallets.adjustments.store', $archivedWallet), [
            'actual_balance' => 1_000,
        ])->assertNotFound();

        $this->from(route('user.money.index', ['section' => 'wallets']))
            ->post(route('user.money.wallets.adjustments.store', $wallet), [
                'actual_balance' => '1.000.000.000.000',
            ])
            ->assertRedirect(route('user.money.index', ['section' => 'wallets']))
            ->assertSessionHasErrors('actual_balance');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_difference_larger_than_one_transaction_is_rejected_without_changing_the_balance(): void
    {
        $user = User::factory()->create();
        $wallet = $user->wallets()->create([
            'name' => 'Saldo negatif',
            'type' => WalletType::Cash,
            'opening_balance' => -999_999_999_999,
        ]);

        $this->actingAs($user)
            ->from(route('user.money.index', ['section' => 'wallets']))
            ->post(route('user.money.wallets.adjustments.store', $wallet), [
                'actual_balance' => '999999999999',
            ])
            ->assertRedirect(route('user.money.index', ['section' => 'wallets']))
            ->assertSessionHasErrors('actual_balance');

        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame(-999_999_999_999, app(WalletBalance::class)->forWallet($wallet));
    }
}
