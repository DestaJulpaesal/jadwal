<?php

namespace App\Http\Requests;

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
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'type' => ['required', Rule::in(array_column(GoalType::cases(), 'value'))],
            'target_value' => ['required', 'integer', 'between:1,'.Rupiah::MAX_AMOUNT],
            'start_value' => ['nullable', 'integer', 'min:0', 'lte:target_value'],
            'unit' => ['nullable', 'string', 'max:40'],
            'due_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:'.$clock->date($this->user())],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('user_id', $this->user()->id)],
            'habit_id' => ['nullable', 'integer', Rule::exists('habits', 'id')->where('user_id', $this->user()->id)],
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
        });
    }
}
