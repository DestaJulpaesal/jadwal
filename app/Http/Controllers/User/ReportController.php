<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\MonthlyCategoryReport;
use App\Services\MonthlySummary;
use App\Support\MonthRange;
use App\Support\UserClock;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(
        Request $request,
        UserClock $clock,
        MonthlySummary $summary,
        MonthlyCategoryReport $categoryReport
    ) {
        $this->authorize('viewAny', Transaction::class);
        $today = $clock->date($request->user());
        $month = $request->validate(['month' => ['nullable', 'date_format:Y-m']])['month'] ?? substr($today, 0, 7);
        $monthRange = MonthRange::fromString($month);
        $previousRange = $monthRange->previous();
        $current = $summary->forMonth($request->user(), $month);
        $previousMonth = $previousRange->key();
        $previousMonthLabel = $previousRange->start->translatedFormat('F');
        $previous = $summary->forMonth($request->user(), $previousMonth);
        $categories = $categoryReport->topExpenses($request->user(), $monthRange);
        $largest = (int) $categories->max('total');
        $comparison = $previous['expense'] === 0
            ? null
            : (int) round(($current['expense'] - $previous['expense']) * 100 / $previous['expense']);

        return view('user.reports.index', compact(
            'month',
            'current',
            'previous',
            'categories',
            'largest',
            'comparison',
            'previousMonth',
            'previousMonthLabel'
        ));
    }
}
