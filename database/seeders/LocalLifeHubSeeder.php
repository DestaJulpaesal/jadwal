<?php

namespace Database\Seeders;

use App\Enums\GoalType;
use App\Enums\MoneyCategoryType;
use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Models\User;
use App\Support\UserClock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LocalLifeHubSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'demo@example.test'],
            [
                'name' => 'Akun Demo',
                'password' => Hash::make('password'),
                'timezone' => 'Asia/Jakarta',
            ]
        );
        $today = app(UserClock::class)->date($user);

        foreach (config('presets.habits') as $preset) {
            $user->habits()->firstOrCreate(
                ['title' => $preset['title']],
                array_merge($preset, ['starts_on' => $today])
            );
        }

        $wallet = $user->wallets()->firstOrCreate(
            ['name' => 'Dompet tunai'],
            ['type' => WalletType::Cash, 'opening_balance' => 500_000]
        );
        $food = $user->moneyCategories()->firstOrCreate(
            ['name' => 'Makan & minum', 'type' => MoneyCategoryType::Expense]
        );
        $user->transactions()->firstOrCreate(
            ['note' => 'Contoh makan siang', 'occurred_on' => $today],
            [
                'wallet_id' => $wallet->id,
                'type' => TransactionType::Expense,
                'amount' => 35_000,
                'money_category_id' => $food->id,
            ]
        );
        $user->goals()->firstOrCreate(
            ['title' => 'Dana darurat'],
            [
                'type' => GoalType::Savings,
                'target_value' => 10_000_000,
                'start_value' => 0,
                'wallet_id' => $wallet->id,
                'due_on' => now($user->timezone)->addMonths(6)->toDateString(),
            ]
        );
    }
}
