<?php

namespace App\Http\Requests;

use App\Enums\MoneyCategoryType;
use App\Models\Budget;
use App\Support\Rupiah;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $budget = $this->route('budget');

        return $budget instanceof Budget
            ? $this->user()->can('update', $budget)
            : $this->user()->can('create', Budget::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['amount' => Rupiah::normalize($this->input('amount'))]);
    }

    public function rules(): array
    {
        $budget = $this->route('budget');
        $categoryId = $this->input('money_category_id');
        $month = $this->input('month');
        $uniqueCategoryForMonth = Rule::unique('budgets')->where(fn ($query) => $query
            ->where('user_id', $this->user()->id)
            ->where('month', $month)
            ->where('money_category_id', $categoryId));

        if ($budget instanceof Budget) {
            $uniqueCategoryForMonth->ignore($budget->id);
        }

        return [
            'money_category_id' => [
                'required',
                'integer',
                Rule::exists('money_categories', 'id')
                    ->where('user_id', $this->user()->id)
                    ->where('type', MoneyCategoryType::Expense->value)
                    ->whereNull('archived_at'),
                $uniqueCategoryForMonth,
            ],
            'month' => ['required', 'date_format:Y-m'],
            'amount' => ['required', 'integer', 'between:1,'.Rupiah::MAX_AMOUNT],
        ];
    }

    public function attributes(): array
    {
        return [
            'money_category_id' => 'kategori pengeluaran',
            'month' => 'bulan anggaran',
            'amount' => 'nominal anggaran',
        ];
    }
}
