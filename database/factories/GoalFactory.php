<?php

namespace Database\Factories;

use App\Enums\GoalStatus;
use App\Enums\GoalType;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GoalFactory extends Factory
{
    protected $model = Goal::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'type' => GoalType::Number,
            'target_value' => 10,
            'start_value' => 0,
            'status' => GoalStatus::Active,
        ];
    }
}
