<?php

namespace App\Http\Requests;

use App\Enums\GoalDirection;
use App\Enums\GoalType;
use App\Models\Goal;
use App\Support\Rupiah;
use App\Support\UserClock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $goal = $this->route('goal');

        return $goal instanceof Goal
            ? $this->user()->can('update', $goal)
            : $this->user()->can('create', Goal::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['target_value' => Rupiah::normalize($this->input('target_value'))]);
    }

    public function rules(UserClock $clock): array
    {
        $dueOnRules = ['nullable', 'date_format:Y-m-d'];
        $goal = $this->route('goal');
        if (! $goal instanceof Goal || $this->input('due_on') !== $goal->due_on?->toDateString()) {
            $dueOnRules[] = 'after_or_equal:'.$clock->date($this->user());
        }

        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'type' => ['required', Rule::in(array_column(GoalType::cases(), 'value'))],
            'direction' => ['sometimes', 'required', Rule::in(array_column(GoalDirection::cases(), 'value'))],
            'target_value' => ['required', 'integer', 'between:1,'.Rupiah::MAX_AMOUNT],
            'start_value' => ['nullable', 'integer', 'min:0'],
            'unit' => ['nullable', 'string', 'max:40'],
            'due_on' => $dueOnRules,
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(
                    fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $this->user()->id)
                ),
            ],
            'habit_id' => ['nullable', 'integer', Rule::exists('habits', 'id')->where('user_id', $this->user()->id)->whereNull('archived_at')],
            'wallet_id' => ['nullable', 'integer', Rule::exists('wallets', 'id')->where('user_id', $this->user()->id)->whereNull('archived_at')],
        ];
    }

    public function messages(): array
    {
        return __('validation.catalog.goal');
    }

    public function attributes(): array
    {
        return [
            'title' => 'nama target',
            'description' => 'catatan',
            'type' => 'jenis target',
            'direction' => 'arah target',
            'target_value' => 'nilai target',
            'start_value' => 'nilai awal',
            'unit' => 'satuan',
            'due_on' => 'tanggal target',
            'category_id' => 'kategori',
            'habit_id' => 'kebiasaan',
            'wallet_id' => 'dompet',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type = $this->input('type');
            if ($type === GoalType::Savings->value && ! $this->filled('wallet_id')) {
                $validator->errors()->add('wallet_id', __('validation.catalog.goal.wallet_id.required'));
            }
            if ($type === GoalType::Habit->value && ! $this->filled('habit_id')) {
                $validator->errors()->add('habit_id', __('validation.catalog.goal.habit_id.required'));
            }

            if ($validator->errors()->has('target_value') || $validator->errors()->has('start_value')) {
                return;
            }

            $goal = $this->route('goal');
            $rawDirection = $this->input(
                'direction',
                $goal instanceof Goal ? $goal->direction->value : GoalDirection::Increase->value
            );
            $direction = is_string($rawDirection) ? GoalDirection::tryFrom($rawDirection) : null;
            if ($direction === null || $validator->errors()->has('direction')) {
                return;
            }

            $start = $this->exists('start_value')
                ? (int) ($this->input('start_value') ?? 0)
                : ($goal instanceof Goal ? $goal->start_value : 0);
            $target = (int) $this->input('target_value');

            if ($direction === GoalDirection::Increase && $start > $target) {
                $validator->errors()->add('start_value', __('validation.catalog.goal.start_value.direction_increase'));
            } elseif ($direction === GoalDirection::Decrease && $start < $target) {
                $validator->errors()->add('start_value', __('validation.catalog.goal.start_value.direction_decrease'));
            }
        });
    }
}
