<?php

namespace App\Http\Controllers\User;

use App\Enums\MoneyCategoryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveBudgetRequest;
use App\Models\Budget;
use App\Support\Feedback;
use App\Support\MonthRange;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function store(SaveBudgetRequest $request)
    {
        $request->user()->budgets()->create($request->validated());

        return Feedback::success(back(), __('ui.feedback.budget_saved'));
    }

    public function update(SaveBudgetRequest $request, Budget $budget)
    {
        $this->authorize('update', $budget);
        abort_if($budget->user_id !== $request->user()->id, 404);
        $budget->update($request->validated());

        return Feedback::success(back(), __('ui.feedback.budget_saved'));
    }

    public function destroy(Request $request, Budget $budget)
    {
        $this->authorize('delete', $budget);
        abort_if($budget->user_id !== $request->user()->id, 404);
        $budget->delete();

        return Feedback::success(back(), __('ui.feedback.budget_deleted'));
    }

    public function copyPrevious(Request $request)
    {
        $this->authorize('create', Budget::class);
        $month = $request->validate(['month' => ['required', 'date_format:Y-m']])['month'];
        $range = MonthRange::fromString($month);
        $sourceBudgets = $request->user()->budgets()
            ->where('month', $range->previous()->key())
            ->whereHas('moneyCategory', fn ($query) => $query
                ->where('type', MoneyCategoryType::Expense->value)
                ->whereNull('archived_at'))
            ->get();

        $now = now();
        $rows = $sourceBudgets->map(fn (Budget $budget) => [
            'user_id' => $request->user()->id,
            'money_category_id' => $budget->money_category_id,
            'month' => $month,
            'amount' => $budget->amount,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($rows !== []) {
            Budget::query()->insertOrIgnore($rows);
        }

        return Feedback::success(back(), __('ui.feedback.budget_copied'));
    }
}
