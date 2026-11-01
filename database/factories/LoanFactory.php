<?php

namespace Database\Factories;

use App\Enums\LoanDirection;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanFactory extends Factory
{
    protected $model = Loan::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'direction' => LoanDirection::Borrowed,
            'counterparty' => fake()->name(),
            'amount' => 100_000,
            'loan_date' => now()->toDateString(),
        ];
    }
}
