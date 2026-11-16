<?php

namespace Tests\Feature;

use App\Enums\MoneyCategoryType;
use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Models\MoneyCategory;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BudgetProgress;
use App\Support\MonthRange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BudgetProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_progress_counts_manual_expenses_at_both_month_boundaries_and_excludes_other_sources(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $food = MoneyCategory::factory()->create(['user_id' => $user->id, 'name' => 'Makan']);
        $unused = MoneyCategory::factory()->create(['user_id' => $user->id, 'name' => 'Lainnya']);
        $income = MoneyCategory::factory()->create([
            'user_id' => $user->id,
            'type' => MoneyCategoryType::Income,
        ]);
        $otherCategory = MoneyCategory::factory()->create(['user_id' => $otherUser->id]);
        $wallet = $user->wallets()->create([
            'name' => 'Tunai',
            'type' => WalletType::Cash,
            'opening_balance' => 0,
        ]);
        $otherWallet = $otherUser->wallets()->create([
            'name' => 'Tunai',
            'type' => WalletType::Cash,
            'opening_balance' => 0,
        ]);
        $user->budgets()->create([
            'money_category_id' => $food->id,
            'month' => '2026-11',
            'amount' => 10_000,
        ]);

        foreach ([
            ['amount' => 4_000, 'date' => '2026-11-01', 'source' => null],
            ['amount' => 4_000, 'date' => '2026-11-30', 'source' => null],
            ['amount' => 1_000, 'date' => '2026-10-31', 'source' => null],
            ['amount' => 1_000, 'date' => '2026-12-01', 'source' => null],
            ['amount' => 1_000, 'date' => '2026-11-10', 'source' => 'loan'],
            ['amount' => 1_000, 'date' => '2026-11-10', 'source' => 'loan_repayment'],
            ['amount' => 1_000, 'date' => '2026-11-10', 'source' => 'adjustment'],
        ] as $expense) {
            Transaction::factory()->create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'money_category_id' => $food->id,
                'type' => TransactionType::Expense,
                'amount' => $expense['amount'],
                'occurred_on' => $expense['date'],
                'source_type' => $expense['source'],
            ]);
        }

        Transaction::factory()->create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'money_category_id' => $food->id,
            'type' => TransactionType::Transfer,
            'amount' => 9_000,
            'occurred_on' => '2026-11-15',
        ]);
        Transaction::factory()->create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'money_category_id' => $income->id,
            'type' => TransactionType::Expense,
            'amount' => 9_000,
            'occurred_on' => '2026-11-15',
        ]);
        Transaction::factory()->create([
            'user_id' => $otherUser->id,
            'wallet_id' => $otherWallet->id,
            'money_category_id' => $otherCategory->id,
            'type' => TransactionType::Expense,
            'amount' => 9_000,
            'occurred_on' => '2026-11-15',
        ]);

        $progress = app(BudgetProgress::class)->forMonth($user, MonthRange::fromString('2026-11'));
        $foodProgress = $progress->first(fn (array $item) => $item['category']->is($food));
        $unusedProgress = $progress->first(fn (array $item) => $item['category']->is($unused));

        $this->assertSame(8_000, $foodProgress['spent']);
        $this->assertSame(2_000, $foodProgress['remaining']);
        $this->assertSame(80, $foodProgress['percent']);
        $this->assertSame('warning', $foodProgress['status']);
        $this->assertSame(0, $unusedProgress['spent']);
        $this->assertSame('unbudgeted', $unusedProgress['status']);
        $this->assertFalse($progress->contains(fn (array $item) => $item['category']->is($otherCategory)));

        $user->transactions()->where('money_category_id', $food->id)
            ->where('occurred_on', '2026-11-30')
            ->firstOrFail()
            ->update(['amount' => 6_000]);
        $overBudgetProgress = app(BudgetProgress::class)->forMonth($user, MonthRange::fromString('2026-11'))
            ->first(fn (array $item) => $item['category']->is($food));
        $this->assertSame(10_000, $overBudgetProgress['spent']);
        $this->assertSame('over', $overBudgetProgress['status']);
    }

    public function test_progress_uses_a_fixed_number_of_queries_as_categories_grow(): void
    {
        $user = User::factory()->create();
        MoneyCategory::factory()->count(2)->create(['user_id' => $user->id]);

        DB::flushQueryLog();
        DB::enableQueryLog();
        app(BudgetProgress::class)->forMonth($user, MonthRange::fromString('2026-11'));
        $initialQueryCount = count(DB::getQueryLog());

        MoneyCategory::factory()->count(10)->create(['user_id' => $user->id]);
        DB::flushQueryLog();
        app(BudgetProgress::class)->forMonth($user, MonthRange::fromString('2026-11'));
        $largerQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(3, $initialQueryCount);
        $this->assertSame($initialQueryCount, $largerQueryCount);
    }

    public function test_budget_crud_validates_owner_expense_category_and_amount(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $expense = MoneyCategory::factory()->create(['user_id' => $user->id]);
        $income = MoneyCategory::factory()->create([
            'user_id' => $user->id,
            'type' => MoneyCategoryType::Income,
        ]);
        $otherExpense = MoneyCategory::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($user)->post(route('user.budgets.store'), [
            'money_category_id' => $expense->id,
            'month' => '2026-11',
            'amount' => '100.000',
        ])->assertRedirect();
        $budget = $user->budgets()->firstOrFail();
        $this->assertSame(100_000, $budget->amount);
        $this->get(route('user.money.index', ['month' => '2026-11']))
            ->assertOk()
            ->assertSee('Anggaran bulanan')
            ->assertSee('Ubah anggaran');
        $this->get(route('user.reports.index', ['month' => '2026-11']))
            ->assertOk()
            ->assertSee('Anggaran bulanan');

        $this->patch(route('user.budgets.update', $budget), [
            'money_category_id' => $expense->id,
            'month' => '2026-11',
            'amount' => '125000',
        ])->assertRedirect();
        $this->assertSame(125_000, $budget->fresh()->amount);

        $this->post(route('user.budgets.store'), [
            'money_category_id' => $expense->id,
            'month' => '2026-11',
            'amount' => 1,
        ])->assertSessionHasErrors('money_category_id');
        $this->post(route('user.budgets.store'), [
            'money_category_id' => $income->id,
            'month' => '2026-11',
            'amount' => 1,
        ])->assertSessionHasErrors('money_category_id');
        $this->post(route('user.budgets.store'), [
            'money_category_id' => $otherExpense->id,
            'month' => '2026-11',
            'amount' => 1,
        ])->assertSessionHasErrors('money_category_id');
        $this->post(route('user.budgets.store'), [
            'money_category_id' => $expense->id,
            'month' => '2026-11',
            'amount' => 0,
        ])->assertSessionHasErrors('amount');

        $this->delete(route('user.budgets.destroy', $budget))->assertRedirect();
        $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
    }

    public function test_copy_previous_is_idempotent_and_does_not_overwrite_existing_budgets(): void
    {
        $user = User::factory()->create();
        $first = MoneyCategory::factory()->create(['user_id' => $user->id]);
        $second = MoneyCategory::factory()->create(['user_id' => $user->id]);
        $user->budgets()->createMany([
            ['money_category_id' => $first->id, 'month' => '2026-11', 'amount' => 10_000],
            ['money_category_id' => $second->id, 'month' => '2026-11', 'amount' => 20_000],
            ['money_category_id' => $first->id, 'month' => '2026-12', 'amount' => 99_000],
        ]);

        $this->actingAs($user)->post(route('user.budgets.copy-previous'), ['month' => '2026-12'])->assertRedirect();
        $this->assertSame(99_000, $user->budgets()->where('money_category_id', $first->id)->where('month', '2026-12')->value('amount'));
        $this->assertSame(20_000, $user->budgets()->where('money_category_id', $second->id)->where('month', '2026-12')->value('amount'));

        $this->post(route('user.budgets.copy-previous'), ['month' => '2026-12'])->assertRedirect();
        $this->assertSame(2, $user->budgets()->where('month', '2026-12')->count());
    }

    public function test_dashboard_shows_budget_attention_without_financial_amounts(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-15 03:00:00', 'UTC'));
        $user = User::factory()->create([
            'timezone' => 'Asia/Jakarta',
            'hide_financial_amounts' => true,
            'dashboard_sections' => ['attention', 'finance'],
        ]);
        $category = MoneyCategory::factory()->create(['user_id' => $user->id, 'name' => 'Makan']);
        $wallet = $user->wallets()->create([
            'name' => 'Tunai',
            'type' => WalletType::Cash,
            'opening_balance' => 0,
        ]);
        $user->budgets()->create([
            'money_category_id' => $category->id,
            'month' => '2026-11',
            'amount' => 10_000,
        ]);
        Transaction::factory()->create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'money_category_id' => $category->id,
            'amount' => 8_000,
            'occurred_on' => '2026-11-15',
            'source_type' => null,
        ]);

        $response = $this->actingAs($user)->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('Anggaran: Makan')
            ->assertSee('Pengeluaran mendekati batas anggaran.');
        preg_match('/<li class="concept-item flex-wrap">(?:(?!<\\/li>).)*Anggaran: Makan(?:(?!<\\/li>).)*<\\/li>/s', $response->getContent(), $budgetAttention);
        $this->assertArrayHasKey(0, $budgetAttention);
        $this->assertStringNotContainsString('Rp', $budgetAttention[0]);
        $this->assertStringNotContainsString('%', $budgetAttention[0]);
    }

    public function test_budget_update_and_delete_are_owner_scoped(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $category = MoneyCategory::factory()->create(['user_id' => $owner->id]);
        $budget = $owner->budgets()->create([
            'money_category_id' => $category->id,
            'month' => '2026-11',
            'amount' => 10_000,
        ]);

        $this->actingAs($other)->patch(route('user.budgets.update', $budget), [
            'money_category_id' => $category->id,
            'month' => '2026-11',
            'amount' => 20_000,
        ])->assertForbidden();
        $this->delete(route('user.budgets.destroy', $budget))->assertForbidden();
        $this->assertDatabaseHas('budgets', ['id' => $budget->id, 'amount' => 10_000]);
    }

    public function test_category_in_use_by_a_budget_cannot_be_permanently_deleted(): void
    {
        $user = User::factory()->create();
        $category = MoneyCategory::factory()->create(['user_id' => $user->id]);
        $user->budgets()->create([
            'money_category_id' => $category->id,
            'month' => '2026-11',
            'amount' => 10_000,
        ]);

        $this->actingAs($user)->patch(route('user.money.categories.archive', $category))->assertRedirect();
        $this->delete(route('user.money.categories.destroy', $category))->assertUnprocessable();
        $this->assertDatabaseHas('money_categories', ['id' => $category->id]);
    }
}
