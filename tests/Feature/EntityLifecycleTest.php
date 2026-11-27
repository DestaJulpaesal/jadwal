<?php

namespace Tests\Feature;

use App\Enums\GoalStatus;
use App\Enums\HabitFrequency;
use App\Enums\LoanDirection;
use App\Enums\MoneyCategoryType;
use App\Enums\ObligationKind;
use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Models\User;
use App\Services\AgendaService;
use App\Services\WalletBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntityLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_transactions_can_be_deleted_but_source_transactions_cannot(): void
    {
        $user = User::factory()->create();
        $wallet = $user->wallets()->create(['name' => 'Tunai', 'type' => WalletType::Cash]);
        $manual = $user->transactions()->create([
            'wallet_id' => $wallet->id,
            'type' => TransactionType::Expense,
            'amount' => 10_000,
            'occurred_on' => '2026-11-16',
        ]);
        $source = $user->transactions()->create([
            'wallet_id' => $wallet->id,
            'type' => TransactionType::Expense,
            'amount' => 20_000,
            'occurred_on' => '2026-11-16',
            'source_type' => 'loan',
            'source_id' => 123,
        ]);

        $this->actingAs($user)->delete(route('user.money.transactions.destroy', $manual))->assertRedirect();
        $this->delete(route('user.money.transactions.destroy', $source))->assertNotFound();

        $this->assertDatabaseMissing('transactions', ['id' => $manual->id]);
        $this->assertDatabaseHas('transactions', ['id' => $source->id]);
    }

    public function test_obligations_can_be_archived_restored_and_deleted_only_without_payments(): void
    {
        $user = User::factory()->create();
        $obligation = $user->obligations()->create([
            'kind' => ObligationKind::Bill,
            'title' => 'Internet',
            'amount' => 100_000,
            'due_day' => 16,
            'starts_on' => '2026-11-01',
            'remind_days_before' => 3,
        ]);

        $this->actingAs($user)->patch(route('user.obligations.archive', $obligation))->assertRedirect();
        $agenda = app(AgendaService::class);
        $this->assertFalse($agenda->forDate($user, '2026-11-16')->contains(fn ($item) => $item['title'] === 'Internet'));
        $this->assertFalse($agenda->attentionFor($user, '2026-11-16')->contains(fn ($item) => $item['title'] === 'Internet'));
        $this->get(route('user.obligations.index'))->assertViewHas('obligations', fn ($items) => $items->isEmpty());
        $this->get(route('user.obligations.index', ['status' => 'archived']))
            ->assertViewHas('obligations', fn ($items) => $items->pluck('id')->all() === [$obligation->id]);
        $this->patch(route('user.obligations.restore', $obligation))->assertRedirect();
        $agendaItem = $agenda->forDate($user, '2026-11-16')->first(fn ($item) => $item['title'] === 'Internet');
        $this->assertNotNull($agendaItem);
        $this->assertSame(route('user.obligations.pay', $obligation), $agendaItem['quick_action']);
        $this->assertTrue($agenda->attentionFor($user, '2026-11-16')->contains(fn ($item) => $item['title'] === 'Internet'));
        $this->patch(route('user.obligations.archive', $obligation))->assertRedirect();
        $this->delete(route('user.obligations.destroy', $obligation))->assertRedirect();

        $paid = $user->obligations()->create([
            'kind' => ObligationKind::Bill,
            'title' => 'Listrik',
            'amount' => 100_000,
            'due_day' => 16,
            'starts_on' => '2026-11-01',
            'remind_days_before' => 3,
        ]);
        $user->obligationPayments()->create([
            'obligation_id' => $paid->id,
            'due_on' => '2026-11-16',
            'paid_on' => '2026-11-16',
            'amount_paid' => 100_000,
        ]);
        $this->patch(route('user.obligations.archive', $paid))->assertRedirect();
        $this->delete(route('user.obligations.destroy', $paid))->assertUnprocessable();
        $this->assertDatabaseHas('obligations', ['id' => $paid->id]);
    }

    public function test_loan_archive_restore_and_delete_preserve_source_transaction_rules(): void
    {
        $user = User::factory()->create();
        $loan = $user->loans()->create([
            'direction' => LoanDirection::Lent,
            'counterparty' => 'Budi',
            'amount' => 100_000,
            'loan_date' => '2026-11-01',
        ]);
        $source = $user->transactions()->create([
            'wallet_id' => $user->wallets()->create(['name' => 'Tunai', 'type' => WalletType::Cash])->id,
            'type' => TransactionType::Expense,
            'amount' => 100_000,
            'occurred_on' => '2026-11-01',
            'source_type' => 'loan',
            'source_id' => $loan->id,
        ]);

        $this->actingAs($user)->patch(route('user.loans.archive', $loan))->assertRedirect();
        $this->get(route('user.loans.index'))->assertViewHas('loans', fn ($items) => $items->isEmpty());
        $this->get(route('user.loans.index', ['status' => 'archived']))
            ->assertViewHas('loans', fn ($items) => $items->pluck('id')->all() === [$loan->id]);
        $this->patch(route('user.loans.restore', $loan))->assertRedirect();
        $this->patch(route('user.loans.archive', $loan))->assertRedirect();
        $this->delete(route('user.loans.destroy', $loan))->assertRedirect();

        $this->assertDatabaseMissing('transactions', ['id' => $source->id]);
        $this->assertDatabaseMissing('loans', ['id' => $loan->id]);

        $paid = $user->loans()->create([
            'direction' => LoanDirection::Borrowed,
            'counterparty' => 'Sari',
            'amount' => 100_000,
            'loan_date' => '2026-11-01',
        ]);
        $user->loanRepayments()->create([
            'loan_id' => $paid->id,
            'amount' => 10_000,
            'paid_on' => '2026-11-02',
        ]);
        $this->patch(route('user.loans.archive', $paid))->assertRedirect();
        $this->delete(route('user.loans.destroy', $paid))->assertUnprocessable();
        $this->assertDatabaseHas('loans', ['id' => $paid->id]);
    }

    public function test_wallets_and_money_categories_can_be_archived_restored_and_safely_deleted(): void
    {
        $user = User::factory()->create();
        $wallet = $user->wallets()->create([
            'name' => 'Tunai',
            'type' => WalletType::Cash,
            'opening_balance' => 50_000,
        ]);
        $category = $user->moneyCategories()->create([
            'name' => 'Makan',
            'type' => MoneyCategoryType::Expense,
        ]);
        $transaction = $user->transactions()->create([
            'wallet_id' => $wallet->id,
            'money_category_id' => $category->id,
            'type' => TransactionType::Expense,
            'amount' => 10_000,
            'occurred_on' => '2026-11-16',
        ]);

        $this->actingAs($user)->patch(route('user.money.wallets.archive', $wallet))->assertRedirect();
        $this->get(route('user.money.index', ['section' => 'wallets']))
            ->assertViewHas('wallets', fn ($items) => $items->isEmpty());
        $this->get(route('user.money.index', ['section' => 'wallets', 'status' => 'archived']))
            ->assertViewHas('wallets', fn ($items) => $items->pluck('id')->all() === [$wallet->id]);
        $this->patch(route('user.money.wallets.restore', $wallet))->assertRedirect();
        $this->patch(route('user.money.wallets.archive', $wallet))->assertRedirect();
        $this->assertFalse(app(WalletBalance::class)->forUser($user)->contains('id', $wallet->id));
        $this->assertSame(40_000, app(WalletBalance::class)->forUser($user, true)->firstWhere('id', $wallet->id)->calculated_balance);
        $this->delete(route('user.money.wallets.destroy', $wallet))->assertUnprocessable();
        $unusedWallet = $user->wallets()->create([
            'name' => 'Tabungan lama',
            'type' => WalletType::Savings,
            'opening_balance' => 25_000,
        ]);
        $this->patch(route('user.money.wallets.archive', $unusedWallet))->assertRedirect();
        $this->delete(route('user.money.wallets.destroy', $unusedWallet))->assertRedirect();
        $this->assertDatabaseMissing('wallets', ['id' => $unusedWallet->id]);

        $this->patch(route('user.money.categories.archive', $category))->assertRedirect();
        $this->get(route('user.money.index', ['section' => 'wallets']))
            ->assertViewHas('categories', fn ($items) => $items->isEmpty());
        $this->get(route('user.money.index', ['section' => 'wallets', 'status' => 'archived']))
            ->assertViewHas('categories', fn ($items) => $items->pluck('id')->all() === [$category->id]);
        $this->patch(route('user.money.categories.update', $category), ['name' => 'Makan keluarga'])->assertRedirect();
        $this->assertDatabaseHas('money_categories', ['id' => $category->id, 'name' => 'Makan keluarga']);
        $this->patch(route('user.money.categories.restore', $category))->assertRedirect();
        $this->patch(route('user.money.categories.archive', $category))->assertRedirect();
        $this->delete(route('user.money.categories.destroy', $category))->assertUnprocessable();

        $unusedCategory = $user->moneyCategories()->create([
            'name' => 'Transportasi',
            'type' => MoneyCategoryType::Expense,
        ]);
        $this->patch(route('user.money.categories.archive', $unusedCategory))->assertRedirect();
        $this->delete(route('user.money.categories.destroy', $unusedCategory))->assertRedirect();
        $this->assertDatabaseMissing('money_categories', ['id' => $unusedCategory->id]);
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'money_category_id' => $category->id]);
    }

    public function test_wallet_cannot_be_deleted_while_obligations_or_loans_reference_it(): void
    {
        $user = User::factory()->create();
        $obligationWallet = $user->wallets()->create([
            'name' => 'Dompet tagihan',
            'type' => WalletType::Cash,
        ]);
        $user->obligations()->create([
            'kind' => 'bill',
            'title' => 'Tagihan tertaut',
            'amount' => 100_000,
            'due_day' => 15,
            'starts_on' => '2026-11-01',
            'wallet_id' => $obligationWallet->id,
        ]);
        $obligationWallet->update(['archived_at' => now()]);

        $this->actingAs($user)
            ->delete(route('user.money.wallets.destroy', $obligationWallet))
            ->assertUnprocessable();
        $this->assertDatabaseHas('obligations', ['wallet_id' => $obligationWallet->id]);

        $loanWallet = $user->wallets()->create([
            'name' => 'Dompet pinjaman',
            'type' => WalletType::Bank,
        ]);
        $user->loans()->create([
            'direction' => 'borrowed',
            'counterparty' => 'Pemberi pinjaman',
            'amount' => 100_000,
            'loan_date' => '2026-11-01',
            'wallet_id' => $loanWallet->id,
        ]);
        $loanWallet->update(['archived_at' => now()]);

        $this->delete(route('user.money.wallets.destroy', $loanWallet))
            ->assertUnprocessable();
        $this->assertDatabaseHas('loans', ['wallet_id' => $loanWallet->id]);
    }

    public function test_habits_and_goals_can_be_restored_and_deleted_with_their_children(): void
    {
        $user = User::factory()->create();
        $habit = $user->habits()->create([
            'title' => 'Jalan kaki',
            'frequency' => HabitFrequency::Daily,
            'starts_on' => '2026-11-01',
        ]);
        $log = $user->habitLogs()->create([
            'habit_id' => $habit->id,
            'log_date' => '2026-11-02',
            'status' => 'done',
        ]);
        $goal = $user->goals()->create([
            'title' => 'Baca buku',
            'type' => 'number',
            'target_value' => 10,
            'start_value' => 0,
            'status' => GoalStatus::Active,
        ]);
        $achievedGoal = $user->goals()->create([
            'title' => 'Lari 5 km',
            'type' => 'number',
            'target_value' => 5,
            'start_value' => 0,
            'status' => GoalStatus::Achieved,
        ]);
        $update = $user->goalUpdates()->create([
            'goal_id' => $goal->id,
            'amount' => 3,
            'recorded_on' => '2026-11-02',
        ]);
        $this->actingAs($user)->get(route('user.goals.index', ['status' => 'achieved']))
            ->assertViewHas('goals', fn ($items) => $items->pluck('id')->all() === [$achievedGoal->id]);

        $this->actingAs($user)->patch(route('user.habits.archive', $habit))->assertRedirect();
        $this->patch(route('user.habits.restore', $habit))->assertRedirect();
        $this->patch(route('user.habits.archive', $habit))->assertRedirect();
        $this->delete(route('user.habits.destroy', $habit))->assertRedirect();
        $this->assertDatabaseMissing('habit_logs', ['id' => $log->id]);

        $this->patch(route('user.goals.archive', $goal))->assertRedirect();
        $this->get(route('user.goals.index', ['status' => 'archived']))
            ->assertViewHas('goals', fn ($items) => $items->pluck('id')->all() === [$goal->id]);
        $this->patch(route('user.goals.restore', $goal))->assertRedirect();
        $this->patch(route('user.goals.archive', $goal))->assertRedirect();
        $this->delete(route('user.goals.destroy', $goal))->assertRedirect();
        $this->assertDatabaseMissing('goals', ['id' => $goal->id]);
        $this->assertDatabaseMissing('goal_updates', ['id' => $update->id]);

        $otherUser = User::factory()->create();
        $otherHabit = $otherUser->habits()->create([
            'title' => 'Milik orang lain',
            'frequency' => HabitFrequency::Daily,
            'starts_on' => '2026-11-01',
        ]);
        $this->delete(route('user.habits.destroy', $otherHabit))->assertForbidden();
    }

    public function test_lifecycle_routes_reject_resources_owned_by_another_user(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $wallet = $owner->wallets()->create(['name' => 'Tunai', 'type' => WalletType::Cash]);
        $category = $owner->moneyCategories()->create(['name' => 'Makan', 'type' => MoneyCategoryType::Expense]);
        $transaction = $owner->transactions()->create([
            'wallet_id' => $wallet->id,
            'type' => TransactionType::Expense,
            'amount' => 10_000,
            'occurred_on' => '2026-11-16',
        ]);
        $obligation = $owner->obligations()->create([
            'kind' => ObligationKind::Bill,
            'title' => 'Internet',
            'amount' => 100_000,
            'due_day' => 16,
            'starts_on' => '2026-11-01',
            'remind_days_before' => 3,
        ]);
        $loan = $owner->loans()->create([
            'direction' => LoanDirection::Lent,
            'counterparty' => 'Budi',
            'amount' => 100_000,
            'loan_date' => '2026-11-01',
        ]);
        $habit = $owner->habits()->create([
            'title' => 'Jalan kaki',
            'frequency' => HabitFrequency::Daily,
            'starts_on' => '2026-11-01',
        ]);
        $goal = $owner->goals()->create([
            'title' => 'Baca buku',
            'type' => 'number',
            'target_value' => 10,
            'start_value' => 0,
            'status' => GoalStatus::Active,
        ]);

        $this->actingAs($otherUser);
        $this->patch(route('user.money.wallets.archive', $wallet))->assertForbidden();
        $this->patch(route('user.money.categories.archive', $category))->assertForbidden();
        $this->delete(route('user.money.transactions.destroy', $transaction))->assertForbidden();
        $this->patch(route('user.obligations.archive', $obligation))->assertForbidden();
        $this->patch(route('user.loans.archive', $loan))->assertForbidden();
        $this->patch(route('user.habits.archive', $habit))->assertForbidden();
        $this->patch(route('user.goals.archive', $goal))->assertForbidden();
    }
}
