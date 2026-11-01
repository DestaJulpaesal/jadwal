<?php

namespace Tests\Feature;

use App\Enums\GoalStatus;
use App\Enums\GoalType;
use App\Enums\HabitFrequency;
use App\Enums\LoanDirection;
use App\Enums\MoneyCategoryType;
use App\Enums\ObligationKind;
use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_search_finds_each_supported_record_type_for_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $user->tasks()->create(['title' => 'needle task']);
        $user->habits()->create(['title' => 'needle habit', 'frequency' => HabitFrequency::Daily, 'starts_on' => now()->toDateString()]);
        $wallet = $user->wallets()->create(['name' => 'needle wallet', 'type' => WalletType::Cash]);
        $category = $user->moneyCategories()->create(['name' => 'needle category', 'type' => MoneyCategoryType::Expense]);
        $user->transactions()->create([
            'wallet_id' => $wallet->id,
            'type' => TransactionType::Expense,
            'amount' => 10_000,
            'money_category_id' => $category->id,
            'occurred_on' => now()->toDateString(),
        ]);
        $user->obligations()->create([
            'kind' => ObligationKind::Bill,
            'title' => 'needle bill',
            'amount' => 10_000,
            'due_day' => 10,
            'starts_on' => now()->toDateString(),
        ]);
        $user->loans()->create([
            'direction' => LoanDirection::Borrowed,
            'counterparty' => 'needle person',
            'amount' => 10_000,
            'loan_date' => now()->toDateString(),
        ]);
        $user->goals()->create([
            'title' => 'needle goal',
            'type' => GoalType::Number,
            'target_value' => 10,
            'status' => GoalStatus::Active,
        ]);
        $otherUser->tasks()->create(['title' => 'needle private task']);

        $this->actingAs($user)
            ->get(route('user.search', ['q' => 'needle']))
            ->assertOk()
            ->assertSee('needle task')
            ->assertSee('needle habit')
            ->assertSee('needle category')
            ->assertSee('needle bill')
            ->assertSee('needle person')
            ->assertSee('needle goal')
            ->assertDontSee('needle private task');
    }

    public function test_global_search_requires_a_query_of_at_most_one_hundred_characters(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('user.search'))
            ->assertOk()
            ->assertSee('Isi kata kunci pencarian.');

        $this->get(route('user.search', ['q' => str_repeat('a', 101)]))
            ->assertOk()
            ->assertSee('Kata kunci pencarian maksimal 100 karakter.');
    }
}
