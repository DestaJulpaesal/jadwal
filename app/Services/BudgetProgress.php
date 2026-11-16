<?php

namespace App\Services;

use App\Enums\MoneyCategoryType;
use App\Enums\TransactionType;
use App\Models\Budget;
use App\Models\MoneyCategory;
use App\Models\User;
use App\Support\MonthRange;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BudgetProgress
{
    public function forMonth(User $user, MonthRange $range): Collection
    {
        $spent = DB::table('transactions')
            ->join('money_categories', 'money_categories.id', '=', 'transactions.money_category_id')
            ->where('transactions.user_id', $user->id)
            ->where('money_categories.user_id', $user->id)
            ->where('money_categories.type', MoneyCategoryType::Expense->value)
            ->where('transactions.type', TransactionType::Expense->value)
            ->where(function ($query) {
                $query->whereNull('transactions.source_type')
                    ->orWhereNotIn('transactions.source_type', ['loan', 'loan_repayment', 'adjustment']);
            })
            ->whereBetween('transactions.occurred_on', [
                $range->start->toDateString(),
                $range->end->toDateString(),
            ])
            ->selectRaw('money_categories.id AS category_id, SUM(transactions.amount) AS amount')
            ->groupBy('money_categories.id')
            ->get()
            ->keyBy('category_id');

        $budgets = $user->budgets()
            ->where('month', $range->key())
            ->get()
            ->keyBy('money_category_id');
        $categoryIds = $spent->keys()->merge($budgets->keys());
        $categories = MoneyCategory::query()
            ->where('user_id', $user->id)
            ->where(function ($query) use ($categoryIds) {
                $query->where(function ($query) {
                    $query->where('type', MoneyCategoryType::Expense->value)
                        ->whereNull('archived_at');
                });
                if ($categoryIds->isNotEmpty()) {
                    $query->orWhereIn('id', $categoryIds);
                }
            })
            ->get()
            ->keyBy('id');
        $categoryIds = $categoryIds->merge($categories->keys())->unique();

        return $categoryIds->map(function ($categoryId) use ($spent, $budgets, $categories) {
            /** @var Budget|null $budget */
            $budget = $budgets->get($categoryId);
            $category = $categories->get($categoryId);
            $actual = (int) ($spent->get($categoryId)?->amount ?? 0);

            $amount = $budget?->amount;
            $rawPercent = $amount ? (int) round($actual * 100 / $amount) : null;
            $warningPercent = (int) config('budget.warning_percent', 80);
            $overPercent = (int) config('budget.over_percent', 100);
            $status = $amount === null
                ? 'unbudgeted'
                : ($actual * 100 >= $amount * $overPercent
                    ? 'over'
                    : ($actual * 100 >= $amount * $warningPercent ? 'warning' : 'ok'));

            return [
                'category' => $category,
                'budget_id' => $budget?->id,
                'budget' => $amount,
                'spent' => $actual,
                'remaining' => $amount === null ? null : max(0, $amount - $actual),
                'percent' => $rawPercent === null ? null : min(100, $rawPercent),
                'status' => $status,
            ];
        })->sortBy(fn (array $item) => $item['category']?->name)->values();
    }
}
