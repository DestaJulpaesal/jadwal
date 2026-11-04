<?php

namespace App\Http\Requests;

use App\Enums\HabitFrequency;
use App\Models\Habit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveHabitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $habit = $this->route('habit');

        return $habit instanceof Habit
            ? $this->user()->can('update', $habit)
            : $this->user()->can('create', Habit::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'frequency' => ['required', Rule::in(array_column(HabitFrequency::cases(), 'value'))],
            'weekdays' => ['nullable', 'array', 'max:7'],
            'weekdays.*' => ['integer', 'between:1,7', 'distinct'],
            'weekly_target' => ['nullable', 'integer', 'between:1,7'],
            'goal_value' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'goal_unit' => ['nullable', 'string', 'max:20'],
            'goal_unit_other' => ['nullable', 'string', 'max:20'],
            'preferred_time' => ['nullable', 'date_format:H:i'],
            'schedule_times' => ['nullable', 'array'],
            'schedule_times.*' => ['nullable', 'date_format:H:i'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('user_id', $this->user()->id),
            ],
            'category_name' => ['nullable', 'string', 'max:60'],
            'category_color' => ['nullable', 'regex:/\A#[0-9a-fA-F]{6}\z/'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('frequency') === HabitFrequency::Weekdays->value && empty($this->input('weekdays'))) {
                $validator->errors()->add('weekdays', 'Pilih setidaknya satu hari.');
            }

            if ($this->input('frequency') === HabitFrequency::WeeklyCount->value && ! $this->filled('weekly_target')) {
                $validator->errors()->add('weekly_target', 'Target mingguan wajib diisi.');
            }
        });
    }
}
