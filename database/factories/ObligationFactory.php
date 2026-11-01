<?php

namespace Database\Factories;

use App\Enums\ObligationKind;
use App\Models\Obligation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ObligationFactory extends Factory
{
    protected $model = Obligation::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'kind' => ObligationKind::Bill,
            'title' => fake()->sentence(3),
            'amount' => 100_000,
            'due_day' => now()->day,
            'starts_on' => now()->toDateString(),
            'remind_days_before' => 3,
        ];
    }
}
