<?php

namespace Database\Factories;

use App\Models\Goal;
use App\Models\GoalUpdate;
use Illuminate\Database\Eloquent\Factories\Factory;

class GoalUpdateFactory extends Factory
{
    protected $model = GoalUpdate::class;

    public function definition(): array
    {
        return [
            'goal_id' => Goal::factory(),
            'user_id' => fn (array $attributes) => Goal::query()->whereKey($attributes['goal_id'])->value('user_id'),
            'amount' => 1,
            'recorded_on' => now()->toDateString(),
        ];
    }
}
