<?php

namespace Database\Factories;

use App\Enums\HabitLogStatus;
use App\Models\Habit;
use App\Models\HabitLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class HabitLogFactory extends Factory
{
    protected $model = HabitLog::class;

    public function definition(): array
    {
        return [
            'habit_id' => Habit::factory(),
            'user_id' => fn (array $attributes) => Habit::query()->whereKey($attributes['habit_id'])->value('user_id'),
            'log_date' => now()->toDateString(),
            'status' => HabitLogStatus::Done,
        ];
    }
}
