<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MonthlySummary
{
    public function forMonth(User $user, string $month): array
    {
        $start = \Illuminate\Support\Carbon::parse($month)->startOfMonth()->toDateString();
        $end = \Illuminate\Support\Carbon::parse($month)->endOfMonth()->toDateString();
        $totals = DB::table('transactions')
            ->where('user_id', $user->id)
            ->whereBetween('occurred_on', [$start, $end])
            ->where(function ($query) {
                $query->whereNull('source_type')->orWhereNotIn('source_type', ['loan', 'loan_repayment']);
            })
            ->selectRaw(
                'SUM(CASE WHEN type = ? THEN amount ELSE 0 END) AS income,
                SUM(CASE WHEN type = ? THEN amount ELSE 0 END) AS expense',
                [TransactionType::Income->value, TransactionType::Expense->value]
            )
            ->first();
        $income = (int) ($totals->income ?? 0);
        $expense = (int) ($totals->expense ?? 0);

        return ['income' => $income, 'expense' => $expense, 'net' => $income - $expense];
    }
}
