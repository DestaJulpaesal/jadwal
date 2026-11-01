<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\LoanRepayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanRepaymentFactory extends Factory
{
    protected $model = LoanRepayment::class;

    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'user_id' => fn (array $attributes) => Loan::query()->whereKey($attributes['loan_id'])->value('user_id'),
            'amount' => 10_000,
            'paid_on' => now()->toDateString(),
        ];
    }
}
