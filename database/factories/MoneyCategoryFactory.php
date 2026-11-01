<?php

namespace Database\Factories;

use App\Enums\MoneyCategoryType;
use App\Models\MoneyCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MoneyCategoryFactory extends Factory
{
    protected $model = MoneyCategory::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->word(),
            'type' => MoneyCategoryType::Expense,
        ];
    }
}
