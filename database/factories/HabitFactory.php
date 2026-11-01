<?php

namespace Database\Factories;

use App\Enums\HabitFrequency;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class HabitFactory extends Factory
{
    protected $model = Habit::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->unique()->sentence(3),
            'frequency' => HabitFrequency::Daily,
            'starts_on' => now()->toDateString(),
        ];
    }
}
