<?php

namespace App\Services;

use App\Models\User;
use App\Support\MonthRange;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MonthlyCategoryReport
{
    public function topExpenses(User $user, MonthRange $month): Collection
    {
        return DB::table('transactions')
            ->leftJoin('money_categories', function ($join) use ($user) {
                $join->on('transactions.money_category_id', '=', 'money_categories.id')
                    ->where('money_categories.user_id', '=', $user->id);
            })
            ->where('transactions.user_id', $user->id)
            ->where('transactions.type', 'expense')
            ->whereBetween('transactions.occurred_on', [
                $month->start->toDateString(),
                $month->end->toDateString(),
            ])
            ->where(function ($query) {
                $query->whereNull('transactions.source_type')
                    ->orWhereNotIn('transactions.source_type', ['loan', 'loan_repayment', 'adjustment']);
            })
            ->select('transactions.money_category_id')
            ->selectRaw(
                'COALESCE(money_categories.name, ?) AS category_name, SUM(transactions.amount) AS total',
                [__('ui.money.uncategorized')]
            )
            ->groupBy('transactions.money_category_id', 'money_categories.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
    }
}
