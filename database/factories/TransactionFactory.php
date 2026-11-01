<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'wallet_id' => Wallet::factory(),
            'user_id' => fn (array $attributes) => Wallet::query()->whereKey($attributes['wallet_id'])->value('user_id'),
            'type' => TransactionType::Expense,
            'amount' => 10_000,
            'occurred_on' => now()->toDateString(),
        ];
    }
}
