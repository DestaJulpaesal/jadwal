<?php

namespace Tests\Feature;

use App\Enums\MoneyCategoryType;
use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Models\User;
use Illuminate\Support\Carbon;
use App\Models\Wallet;
use App\Services\BudgetProgress;
use App\Services\LoanBalance;
use App\Services\MonthlyCategoryReport;
use App\Services\MonthlySummary;
use App\Services\ObligationSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MoneyControllerSectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_section_does_not_compute_other_monthly_summaries(): void
    {
        foreach ([
            MonthlySummary::class => 'forMonth',
            BudgetProgress::class => 'forMonth',
            MonthlyCategoryReport::class => 'topExpenses',
            ObligationSchedule::class => 'duePeriods',
            LoanBalance::class => 'remaining',
        ] as $service => $method) {
            $mock = Mockery::mock($service);
            $mock->shouldNotReceive($method);
            $this->app->instance($service, $mock);
        }

        $this->actingAs(User::factory()->create())
            ->get(route('user.money.index', ['section' => 'wallets']))
            ->assertOk();
    }

    public function test_monthly_net_explains_why_it_differs_from_wallet_balance_changes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('user.money.index', ['section' => 'overview']))
            ->assertOk()
            ->assertSee(__('ui.money.monthly_net_help'));
    }

    public function test_active_negative_wallets_show_a_warning_but_archived_wallets_do_not(): void
    {
        $user = User::factory()->create();
        Wallet::factory()->for($user)->create([
            'name' => 'Dompet minus',
            'opening_balance' => -5000,
        ]);
        Wallet::factory()->for($user)->create([
            'name' => 'Dompet arsip',
            'opening_balance' => -2000,
            'archived_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('user.money.index', ['section' => 'wallets']))
            ->assertOk()
            ->assertSee(__('ui.money.negative_wallets_title'))
            ->assertSee('Dompet minus')
            ->assertDontSee('Dompet arsip');

        $this->get(route('user.money.index', ['section' => 'wallets', 'status' => 'archived']))
            ->assertOk()
            ->assertDontSee(__('ui.money.negative_wallets_title'));
    }

    public function test_transaction_dates_stay_within_supported_range(): void
    {
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
        $wallet = $user->wallets()->create(['name' => 'Tunai', 'type' => WalletType::Cash]);
        $payload = [
            'wallet_id' => $wallet->id,
            'type' => TransactionType::Expense->value,
            'amount' => '10000',
            'occurred_on' => '1999-12-31',
        ];

        $this->actingAs($user)->post(route('user.money.transactions.store'), $payload)
            ->assertSessionHasErrors('occurred_on');
        $this->assertDatabaseCount('transactions', 0);

        $payload['occurred_on'] = now('Asia/Jakarta')->addYear()->addDay()->toDateString();
        $this->actingAs($user)->post(route('user.money.transactions.store'), $payload)
            ->assertSessionHasErrors('occurred_on');
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_transactions_are_grouped_by_date_and_actions_are_in_a_bottom_sheet(): void
    {
        $user = User::factory()->create();
        $wallet = $user->wallets()->create(['name' => 'Tunai', 'type' => WalletType::Cash]);
        $category = $user->moneyCategories()->create([
            'name' => 'Makan',
            'type' => MoneyCategoryType::Expense,
        ]);
        foreach (['2026-11-18' => 'Makan malam', '2026-11-17' => 'Sarapan'] as $date => $note) {
            $user->transactions()->create([
                'wallet_id' => $wallet->id,
                'money_category_id' => $category->id,
                'type' => TransactionType::Expense,
                'amount' => 25_000,
                'occurred_on' => $date,
                'note' => $note,
            ]);
        }

        $this->actingAs($user)
            ->get(route('user.money.index', ['section' => 'transactions', 'month' => '2026-11']))
            ->assertOk()
            ->assertSeeInOrder([
                Carbon::parse('2026-11-18')->translatedFormat('l, j F Y'),
                'Makan malam',
                Carbon::parse('2026-11-17')->translatedFormat('l, j F Y'),
                'Sarapan',
            ])
            ->assertSee('Aksi transaksi: Makan')
            ->assertSee('tabular-nums', false);
    }
}
