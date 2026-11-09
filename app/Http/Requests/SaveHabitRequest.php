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
                Rule::exists('categories', 'id')->where(
                    fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $this->user()->id)
                ),
            ],
            'category_name' => ['nullable', 'string', 'max:60'],
            'category_color' => ['nullable', 'regex:/\A#[0-9a-fA-F]{6}\z/'],
        ];
    }

    public function messages(): array
    {
        return __('validation.catalog.habit');
    }

    public function attributes(): array
    {
        return [
            'title' => 'nama kebiasaan',
            'description' => 'catatan',
            'frequency' => 'frekuensi',
            'weekdays' => 'hari',
            'weekdays.*' => 'hari',
            'weekly_target' => 'target mingguan',
            'goal_value' => 'target nilai',
            'goal_unit' => 'satuan target',
            'goal_unit_other' => 'satuan target',
            'preferred_time' => 'jam pilihan',
            'schedule_times' => 'waktu pengingat',
            'schedule_times.*' => 'waktu pengingat',
            'starts_on' => 'tanggal mulai',
            'ends_on' => 'tanggal berakhir',
            'category_id' => 'kategori',
            'category_name' => 'nama kategori',
            'category_color' => 'warna kategori',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('frequency') === HabitFrequency::Weekdays->value && empty($this->input('weekdays'))) {
                $validator->errors()->add('weekdays', __('validation.catalog.habit.weekdays_empty'));
            }

            if ($this->input('frequency') === HabitFrequency::WeeklyCount->value && ! $this->filled('weekly_target')) {
                $validator->errors()->add('weekly_target', __('validation.catalog.habit.weekly_target_empty'));
            }
        });
    }
}
