<?php

namespace Database\Factories;

use App\Models\Obligation;
use App\Models\ObligationPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ObligationPaymentFactory extends Factory
{
    protected $model = ObligationPayment::class;

    public function definition(): array
    {
        return [
            'obligation_id' => Obligation::factory(),
            'user_id' => fn (array $attributes) => Obligation::query()->whereKey($attributes['obligation_id'])->value('user_id'),
            'due_on' => now()->toDateString(),
            'paid_on' => now()->toDateString(),
            'amount_paid' => 100_000,
        ];
    }
}
