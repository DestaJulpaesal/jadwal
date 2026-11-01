<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\MonthlySummary;
use App\Support\UserClock;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request, UserClock $clock, MonthlySummary $summary)
    {
        $this->authorize('viewAny', Transaction::class);
        $today = $clock->date($request->user());
        $month = $request->validate(['month' => ['nullable', 'date_format:Y-m']])['month'] ?? substr($today, 0, 7);
        $current = $summary->forMonth($request->user(), $month);
        $previousMonth = Carbon::createFromFormat('Y-m', $month)->subMonthNoOverflow()->format('Y-m');
        $previous = $summary->forMonth($request->user(), $previousMonth);
        $categories = DB::table('transactions')
            ->leftJoin('money_categories', function ($join) use ($request) {
                $join->on('transactions.money_category_id', '=', 'money_categories.id')
                    ->where('money_categories.user_id', '=', $request->user()->id);
            })
            ->where('transactions.user_id', $request->user()->id)
            ->where('transactions.type', 'expense')
            ->whereBetween('transactions.occurred_on', [
                Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString(),
                Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString(),
            ])
            ->where(function ($query) {
                $query->whereNull('transactions.source_type')
                    ->orWhereNotIn('transactions.source_type', ['loan', 'loan_repayment']);
            })
            ->selectRaw('COALESCE(money_categories.name, ?) AS category_name, SUM(transactions.amount) AS total', ['Tanpa kategori'])
            ->groupBy('money_categories.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
        $largest = (int) $categories->max('total');
        $comparison = $previous['expense'] === 0
            ? null
            : (int) round(($current['expense'] - $previous['expense']) * 100 / $previous['expense']);

        return view('user.reports.index', compact('month', 'current', 'previous', 'categories', 'largest', 'comparison', 'previousMonth'));
    }
}
