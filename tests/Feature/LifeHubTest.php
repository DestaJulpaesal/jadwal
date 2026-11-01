<?php

namespace Tests\Feature;

use App\Enums\GoalStatus;
use App\Enums\HabitFrequency;
use App\Enums\HabitLogStatus;
use App\Enums\MoneyCategoryType;
use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Mail\DailyDigestMail;
use App\Models\Goal;
use App\Models\GoalUpdate;
use App\Models\Habit;
use App\Models\HabitLog;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\MoneyCategory;
use App\Models\Obligation;
use App\Models\ObligationPayment;
use App\Models\Transaction;
use App\Models\User;
use App\Services\GoalProgress;
use App\Services\MonthlySummary;
use App\Services\WalletBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LifeHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_habits_are_owner_scoped_and_daily_check_ins_can_be_cancelled(): void
    {
        $owner = User::factory()->create(['timezone' => 'Asia/Jakarta']);
        $other = User::factory()->create();
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-01 03:00:00', 'UTC'));

        $this->actingAs($owner)->post(route('user.habits.store'), [
            'title' => 'Belajar bahasa Inggris',
            'frequency' => 'daily',
            'starts_on' => '2026-11-01',
            'goal_value' => 30,
            'goal_unit' => 'menit',
        ])->assertRedirect(route('user.habits.index'));

        $habit = $owner->habits()->firstOrFail();
        $this->actingAs($other)->get(route('user.habits.edit', $habit))->assertForbidden();

        $this->actingAs($owner)->post(route('user.habits.check-in', $habit), [
            'status' => 'done',
            'log_date' => '2026-11-01',
        ])->assertRedirect();
        $this->assertDatabaseHas('habit_logs', [
            'user_id' => $owner->id,
            'habit_id' => $habit->id,
            'log_date' => '2026-11-01',
            'status' => HabitLogStatus::Done->value,
            'value' => 30,
        ]);

        $this->delete(route('user.habits.check-in.cancel', $habit), ['log_date' => '2026-11-01'])
            ->assertRedirect();
        $this->assertDatabaseMissing('habit_logs', ['habit_id' => $habit->id, 'log_date' => '2026-11-01']);
    }

    public function test_habit_check_ins_reject_future_dates_and_dates_more_than_seven_days_old(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-01 03:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
        $habit = $user->habits()->create([
            'title' => 'Olahraga',
            'frequency' => HabitFrequency::Daily,
            'starts_on' => '2026-10-01',
        ]);

        foreach (['2026-11-02', '2026-10-24'] as $date) {
            $this->actingAs($user)->post(route('user.habits.check-in', $habit), [
                'status' => 'done',
                'log_date' => $date,
            ])->assertUnprocessable();
        }
        $this->assertDatabaseCount('habit_logs', 0);
    }

    public function test_wallet_transactions_transfers_and_monthly_summary_use_integer_rupiah(): void
    {
        $user = User::factory()->create();
        $cash = $user->wallets()->create(['name' => 'Tunai', 'type' => WalletType::Cash, 'opening_balance' => 2_000_000]);
        $bank = $user->wallets()->create(['name' => 'Bank', 'type' => WalletType::Bank]);
        $food = $user->moneyCategories()->create(['name' => 'Makan', 'type' => MoneyCategoryType::Expense]);
        $income = $user->moneyCategories()->create(['name' => 'Gaji', 'type' => MoneyCategoryType::Income]);

        $this->actingAs($user)->post(route('user.money.transactions.store'), [
            'type' => 'expense',
            'amount' => '150.000',
            'wallet_id' => $cash->id,
            'money_category_id' => $food->id,
            'occurred_on' => '2026-11-01',
        ])->assertRedirect();
        $this->post(route('user.money.transactions.store'), [
            'type' => 'income',
            'amount' => '1.500.000',
            'wallet_id' => $cash->id,
            'money_category_id' => $income->id,
            'occurred_on' => '2026-11-02',
        ])->assertRedirect();
        $this->post(route('user.money.transactions.store'), [
            'type' => 'transfer',
            'amount' => '500.000',
            'wallet_id' => $cash->id,
            'to_wallet_id' => $bank->id,
            'occurred_on' => '2026-11-03',
        ])->assertRedirect();

        $balances = app(WalletBalance::class);
        $this->assertSame(2_850_000, $balances->forWallet($cash));
        $this->assertSame(500_000, $balances->forWallet($bank));
        $this->assertSame(
            ['income' => 1_500_000, 'expense' => 150_000, 'net' => 1_350_000],
            app(MonthlySummary::class)->forMonth($user, '2026-11')
        );
    }

    public function test_monthly_report_compares_months_and_lists_largest_expense_categories(): void
    {
        $user = User::factory()->create();
        $wallet = $user->wallets()->create(['name' => 'Bank', 'type' => WalletType::Bank]);
        $food = $user->moneyCategories()->create(['name' => 'Makan', 'type' => MoneyCategoryType::Expense]);
        $travel = $user->moneyCategories()->create(['name' => 'Transportasi', 'type' => MoneyCategoryType::Expense]);
        $user->transactions()->create([
            'wallet_id' => $wallet->id,
            'money_category_id' => $food->id,
            'type' => TransactionType::Expense,
            'amount' => 100_000,
            'occurred_on' => '2026-10-10',
        ]);
        $user->transactions()->create([
            'wallet_id' => $wallet->id,
            'money_category_id' => $food->id,
            'type' => TransactionType::Expense,
            'amount' => 120_000,
            'occurred_on' => '2026-11-10',
        ]);
        $user->transactions()->create([
            'wallet_id' => $wallet->id,
            'money_category_id' => $travel->id,
            'type' => TransactionType::Expense,
            'amount' => 80_000,
            'occurred_on' => '2026-11-11',
        ]);

        $this->actingAs($user)->get(route('user.reports.index', ['month' => '2026-11']))
            ->assertOk()
            ->assertSee('Makan')
            ->assertSee('Transportasi')
            ->assertSee('100% lebih tinggi');
    }

    public function test_wallets_allow_a_negative_opening_balance(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('user.money.wallets.store'), [
            'name' => 'Kartu kredit',
            'type' => WalletType::Bank->value,
            'opening_balance' => '-150.000',
        ])->assertRedirect();

        $wallet = $user->wallets()->firstOrFail();
        $this->assertSame(-150_000, $wallet->opening_balance);
        $this->assertSame(-150_000, app(WalletBalance::class)->forWallet($wallet));
    }

    public function test_users_cannot_attach_other_users_wallets_or_categories(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $wallet = $owner->wallets()->create(['name' => 'Private', 'type' => WalletType::Bank]);
        $category = $owner->moneyCategories()->create(['name' => 'Private', 'type' => MoneyCategoryType::Expense]);

        $this->actingAs($user)->post(route('user.money.transactions.store'), [
            'type' => TransactionType::Expense->value,
            'amount' => 1000,
            'wallet_id' => $wallet->id,
            'money_category_id' => $category->id,
            'occurred_on' => '2026-11-01',
        ])->assertSessionHasErrors(['wallet_id', 'money_category_id']);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_obligation_payments_and_transactions_are_atomic_and_reversible(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-15 03:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
        $wallet = $user->wallets()->create(['name' => 'Bank', 'type' => WalletType::Bank, 'opening_balance' => 2_000_000]);
        $category = $user->moneyCategories()->create(['name' => 'Tagihan', 'type' => MoneyCategoryType::Expense]);

        $this->actingAs($user)->post(route('user.obligations.store'), [
            'kind' => 'bill',
            'title' => 'Listrik',
            'amount' => '250.000',
            'due_day' => 15,
            'starts_on' => '2026-11-01',
            'remind_days_before' => 3,
            'wallet_id' => $wallet->id,
            'money_category_id' => $category->id,
        ])->assertRedirect();
        $obligation = $user->obligations()->firstOrFail();

        $this->post(route('user.obligations.pay', $obligation), [
            'due_on' => '2026-10-15',
            'paid_on' => '2026-11-01',
            'amount_paid' => '100.000',
        ])->assertUnprocessable();
        $this->post(route('user.obligations.pay', $obligation), [
            'due_on' => '2026-11-15',
            'paid_on' => '2026-11-15',
            'amount_paid' => '250.000',
            'wallet_id' => $wallet->id,
        ])->assertRedirect();
        $payment = $user->obligationPayments()->firstOrFail();
        $this->assertSame('obligation_payment', $payment->transaction->source_type);
        $this->assertSame(1_750_000, app(WalletBalance::class)->forWallet($wallet));
        $this->assertSame(250_000, app(MonthlySummary::class)->forMonth($user, '2026-11')['expense']);

        $this->delete(route('user.obligations.payments.cancel', [$obligation, $payment]))->assertRedirect();
        $this->assertDatabaseMissing('obligation_payments', ['id' => $payment->id]);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame(2_000_000, app(WalletBalance::class)->forWallet($wallet));
    }

    public function test_obligation_periods_show_status_and_only_allow_the_oldest_due_period(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-01 03:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
        $this->actingAs($user)->post(route('user.obligations.store'), [
            'kind' => 'bill',
            'title' => 'Tagihan bulanan',
            'amount' => '100.000',
            'due_day' => 15,
            'starts_on' => '2026-09-01',
            'remind_days_before' => 3,
        ])->assertRedirect();
        $obligation = $user->obligations()->firstOrFail();

        $this->get(route('user.obligations.index'))
            ->assertOk()
            ->assertSee('Terlambat')
            ->assertSee('Belum jatuh tempo')
            ->assertSee('value="2026-09-15"', false);

        $this->post(route('user.obligations.pay', $obligation), [
            'due_on' => '2026-11-15',
            'paid_on' => '2026-11-01',
            'amount_paid' => '100.000',
        ])->assertUnprocessable();
        $this->assertDatabaseCount('obligation_payments', 0);
    }

    public function test_all_life_hub_pages_render_for_an_active_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        foreach ([
            'user.habits.index',
            'user.money.index',
            'user.obligations.index',
            'user.loans.index',
            'user.goals.index',
            'user.reports.index',
        ] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_money_page_query_count_does_not_grow_with_transaction_rows(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-01 03:00:00', 'UTC'));
        $user = User::factory()->create();
        $wallet = $user->wallets()->create(['name' => 'Tunai', 'type' => WalletType::Cash]);
        $category = $user->moneyCategories()->create(['name' => 'Makan', 'type' => MoneyCategoryType::Expense]);
        $user->transactions()->create([
            'wallet_id' => $wallet->id,
            'money_category_id' => $category->id,
            'type' => TransactionType::Expense,
            'amount' => 1000,
            'occurred_on' => '2026-11-01',
        ]);
        foreach (config('presets.money_categories') as $type => $names) {
            foreach ($names as $name) {
                $user->moneyCategories()->firstOrCreate(['type' => $type, 'name' => $name]);
            }
        }
        $connection = DB::connection();
        $connection->enableQueryLog();
        $connection->flushQueryLog();

        $this->actingAs($user)->get(route('user.money.index'))->assertOk();
        $initialQueryCount = count($connection->getQueryLog());
        $connection->flushQueryLog();

        foreach (range(1, 25) as $day) {
            $user->transactions()->create([
                'wallet_id' => $wallet->id,
                'money_category_id' => $category->id,
                'type' => TransactionType::Expense,
                'amount' => 1000,
                'occurred_on' => sprintf('2026-11-%02d', $day),
            ]);
        }
        $connection->flushQueryLog();
        $this->get(route('user.money.index'))->assertOk();
        $populatedQueryCount = count($connection->getQueryLog());

        $this->assertSame($initialQueryCount, $populatedQueryCount);
        $connection->disableQueryLog();
    }

    public function test_domain_factories_keep_child_records_with_their_owner(): void
    {
        $habit = Habit::factory()->create();
        $habitLog = HabitLog::factory()->for($habit)->create();
        $transaction = Transaction::factory()->create();
        $category = MoneyCategory::factory()->create();
        $obligation = Obligation::factory()->create();
        $payment = ObligationPayment::factory()->for($obligation)->create();
        $loan = Loan::factory()->create();
        $repayment = LoanRepayment::factory()->for($loan)->create();
        $goal = Goal::factory()->create();
        $update = GoalUpdate::factory()->for($goal)->create();

        $this->assertSame($habit->user_id, $habitLog->user_id);
        $this->assertSame($transaction->user_id, $transaction->wallet->user_id);
        $this->assertNotNull($category->user_id);
        $this->assertSame($obligation->user_id, $payment->user_id);
        $this->assertSame($loan->user_id, $repayment->user_id);
        $this->assertSame($goal->user_id, $update->user_id);
    }

    public function test_loan_payments_cannot_exceed_remaining_principal_and_are_excluded_from_income_totals(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-11 03:00:00', 'UTC'));
        $user = User::factory()->create();
        $wallet = $user->wallets()->create(['name' => 'Bank', 'type' => WalletType::Bank, 'opening_balance' => 1_000_000]);
        $this->actingAs($user)->post(route('user.loans.store'), [
            'direction' => 'lent',
            'counterparty' => 'Budi',
            'amount' => '500.000',
            'loan_date' => '2026-11-01',
            'due_on' => '2026-11-15',
            'wallet_id' => $wallet->id,
        ])->assertRedirect();
        $loan = $user->loans()->firstOrFail();
        $this->post(route('user.loans.repay', $loan), [
            'amount' => '200.000',
            'paid_on' => '2026-11-10',
            'wallet_id' => $wallet->id,
        ])->assertRedirect();
        $this->assertSame(700_000, app(WalletBalance::class)->forWallet($wallet));
        $this->assertSame(0, app(MonthlySummary::class)->forMonth($user, '2026-11')['income']);

        $this->post(route('user.loans.repay', $loan), [
            'amount' => '400.000',
            'paid_on' => '2026-11-11',
        ])->assertUnprocessable();
        $this->assertDatabaseCount('loan_repayments', 1);
    }

    public function test_admin_is_forbidden_from_all_private_modules(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        foreach ([
            'user.habits.index',
            'user.money.index',
            'user.obligations.index',
            'user.loans.index',
            'user.goals.index',
        ] as $routeName) {
            $this->get(route($routeName))->assertForbidden();
        }
    }

    public function test_deleting_a_user_cascades_through_wallet_transaction_foreign_keys(): void
    {
        $user = User::factory()->create();
        $wallet = $user->wallets()->create(['name' => 'Bank', 'type' => WalletType::Bank]);
        $user->transactions()->create([
            'wallet_id' => $wallet->id,
            'type' => TransactionType::Income,
            'amount' => 100,
            'occurred_on' => '2026-11-01',
        ]);

        $user->delete();

        $this->assertDatabaseMissing('wallets', ['id' => $wallet->id]);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_number_goal_progress_requires_explicit_completion_confirmation(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-01 03:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
        $this->actingAs($user)->post(route('user.goals.store'), [
            'title' => 'Baca buku',
            'type' => 'number',
            'target_value' => 10,
            'start_value' => 0,
            'unit' => 'buku',
            'due_on' => '2026-11-30',
        ])->assertRedirect();
        $goal = $user->goals()->firstOrFail();

        $this->post(route('user.goals.updates.store', $goal), [
            'amount' => 10,
            'recorded_on' => '2026-11-01',
        ])->assertRedirect();
        $this->assertSame(GoalStatus::Active, $goal->fresh()->status);
        $this->assertSame(100, app(GoalProgress::class)->percent($goal->fresh()));

        $this->patch(route('user.goals.achieve', $goal))->assertRedirect();
        $this->assertSame(GoalStatus::Achieved, $goal->fresh()->status);
    }

    public function test_daily_digest_sends_once_after_local_seven_and_records_the_business_date(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-01 00:00:00', 'UTC'));
        Mail::fake();
        $user = User::factory()->create([
            'timezone' => 'Asia/Jakarta',
            'digest_enabled' => true,
        ]);
        $user->tasks()->create([
            'title' => 'Agenda email',
            'starts_at' => '2026-11-01 01:00:00',
        ]);

        $this->artisan('app:send-daily-digest')->assertExitCode(0);
        Mail::assertSent(DailyDigestMail::class, fn ($mail) => $mail->name === $user->name
            && collect($mail->tasks)->contains(fn ($task) => $task['title'] === 'Agenda email'));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'digest_last_sent_on' => '2026-11-01']);

        $this->artisan('app:send-daily-digest')->assertExitCode(0);
        Mail::assertSent(DailyDigestMail::class, 1);
    }
}
