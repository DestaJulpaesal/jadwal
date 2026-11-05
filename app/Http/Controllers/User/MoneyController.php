<?php

namespace App\Http\Controllers\User;

use App\Enums\MoneyCategoryType;
use App\Enums\LoanDirection;
use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Http\Controllers\Controller;
use App\Models\MoneyCategory;
use App\Models\Obligation;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\ObligationScheduler;
use App\Services\MonthlySummary;
use App\Services\LoanBalance;
use App\Services\WalletBalance;
use App\Support\Rupiah;
use App\Support\UserClock;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MoneyController extends Controller
{
    public function index(
        Request $request,
        WalletBalance $balances,
        MonthlySummary $summary,
        ObligationScheduler $scheduler,
        LoanBalance $loanBalances,
        UserClock $clock
    )
    {
        $this->authorize('viewAny', Transaction::class);
        $user = $request->user();
        foreach (config('presets.money_categories') as $type => $names) {
            foreach ($names as $name) {
                $user->moneyCategories()->firstOrCreate(['type' => $type, 'name' => $name]);
            }
        }
        $today = $clock->date($user);
        $month = $request->validate(['month' => ['nullable', 'date_format:Y-m']])['month'] ?? substr($today, 0, 7);
        $wallets = $balances->forUser($user);
        $transactions = $user->transactions()->with(['wallet', 'destinationWallet', 'moneyCategory'])
            ->whereBetween('occurred_on', [
                \Illuminate\Support\Carbon::parse($month)->startOfMonth()->toDateString(),
                \Illuminate\Support\Carbon::parse($month)->endOfMonth()->toDateString(),
            ])->orderByDesc('occurred_on')->orderByDesc('id')->paginate(15)->withQueryString();
        $categories = $user->moneyCategories()->whereNull('archived_at')->orderBy('type')->orderBy('name')->get();
        $totals = $summary->forMonth($user, $month);
        $previousMonth = Carbon::createFromFormat('Y-m', $month)->subMonthNoOverflow()->format('Y-m');
        $previous = $summary->forMonth($user, $previousMonth);
        $plannedObligations = $user->obligations()
            ->whereNull('archived_at')
            ->with(['payments', 'wallet', 'moneyCategory'])
            ->get()
            ->map(function (Obligation $obligation) use ($month, $scheduler) {
                $monthDate = Carbon::createFromFormat('Y-m', $month);
                $dueDate = $scheduler->dueDate($obligation->due_day, $monthDate->year, $monthDate->month);

                if ($obligation->starts_on->gt($monthDate->endOfMonth())
                    || $dueDate->lt($obligation->starts_on)
                    || $obligation->payments->contains(fn ($payment) => $payment->due_on->isSameDay($dueDate))
                    || ($obligation->kind->value === 'installment'
                        && $obligation->payments->count() >= $obligation->installments_total)) {
                    return null;
                }

                $obligation->setAttribute('planned_due_on', $dueDate);

                return $obligation;
            })
            ->filter()
            ->sortBy('planned_due_on')
            ->values();
        $plannedTotal = (int) $plannedObligations->sum('amount');
        $plannedLoans = $user->loans()
            ->whereNull('archived_at')
            ->whereNotNull('due_on')
            ->with('repayments')
            ->get()
            ->map(function ($loan) use ($month, $loanBalances) {
                $monthDate = Carbon::createFromFormat('Y-m', $month);
                $remaining = $loanBalances->remaining($loan);

                if ($remaining === 0 || ! $loan->due_on->isSameMonth($monthDate)) {
                    return null;
                }

                $loan->setAttribute('planned_amount', $remaining);

                return $loan;
            })
            ->filter()
            ->sortBy('due_on')
            ->values();
        $plannedLoanIncome = (int) $plannedLoans
            ->where('direction', LoanDirection::Lent)
            ->sum('planned_amount');
        $plannedLoanExpense = (int) $plannedLoans
            ->where('direction', LoanDirection::Borrowed)
            ->sum('planned_amount');
        $reportCategories = DB::table('transactions')
            ->leftJoin('money_categories', function ($join) use ($user) {
                $join->on('transactions.money_category_id', '=', 'money_categories.id')
                    ->where('money_categories.user_id', '=', $user->id);
            })
            ->where('transactions.user_id', $user->id)
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
        $largest = (int) $reportCategories->max('total');
        $comparison = $previous['expense'] === 0
            ? null
            : (int) round(($totals['expense'] - $previous['expense']) * 100 / $previous['expense']);
        $walletTypes = WalletType::cases();

        return view('user.money.index', compact(
            'wallets',
            'transactions',
            'categories',
            'totals',
            'month',
            'today',
            'walletTypes',
            'previous',
            'previousMonth',
            'reportCategories',
            'largest',
            'comparison',
            'plannedObligations',
            'plannedTotal',
            'plannedLoans',
            'plannedLoanIncome',
            'plannedLoanExpense'
        ));
    }

    public function storeWallet(Request $request)
    {
        $this->authorize('create', Wallet::class);
        $request->merge(['opening_balance' => Rupiah::normalize($request->input('opening_balance'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'type' => ['required', Rule::in(array_column(WalletType::cases(), 'value'))],
            'opening_balance' => ['required', 'integer', 'between:-'.Rupiah::MAX_AMOUNT.','.Rupiah::MAX_AMOUNT],
        ]);
        $request->user()->wallets()->create($data);

        return back()->with('success', 'Dompet berhasil ditambahkan.');
    }

    public function archiveWallet(Request $request, Wallet $wallet)
    {
        $this->authorize('update', $wallet);
        abort_if($wallet->user_id !== $request->user()->id, 404);
        $wallet->update(['archived_at' => now()]);

        return back()->with('success', 'Dompet diarsipkan.');
    }

    public function storeCategory(Request $request)
    {
        $this->authorize('create', MoneyCategory::class);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'type' => ['required', Rule::in(array_column(MoneyCategoryType::cases(), 'value'))],
        ]);
        $request->user()->moneyCategories()->firstOrCreate($data);

        return back()->with('success', 'Kategori berhasil disimpan.');
    }

    public function storeTransaction(Request $request)
    {
        $this->authorize('create', Transaction::class);
        $request->merge(['amount' => Rupiah::normalize($request->input('amount'))]);
        $data = $request->validate([
            'type' => ['required', Rule::in(array_column(TransactionType::cases(), 'value'))],
            'amount' => ['required', 'integer', 'between:1,'.Rupiah::MAX_AMOUNT],
            'wallet_id' => ['required', 'integer', Rule::exists('wallets', 'id')->where('user_id', $request->user()->id)->whereNull('archived_at')],
            'to_wallet_id' => ['nullable', 'integer', Rule::exists('wallets', 'id')->where('user_id', $request->user()->id)->whereNull('archived_at')],
            'money_category_id' => ['nullable', 'integer', Rule::exists('money_categories', 'id')->where('user_id', $request->user()->id)->whereNull('archived_at')],
            'occurred_on' => ['required', 'date_format:Y-m-d'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        if ($data['type'] === TransactionType::Transfer->value) {
            $request->validate(['to_wallet_id' => ['required', 'different:wallet_id']]);
            $data['money_category_id'] = null;
        } else {
            $data['to_wallet_id'] = null;
        }
        if (! empty($data['money_category_id'])) {
            $category = $request->user()->moneyCategories()->findOrFail($data['money_category_id']);
            abort_unless($category->type->value === $data['type'], 422, 'Jenis kategori tidak sesuai dengan transaksi.');
        }

        DB::transaction(fn () => $request->user()->transactions()->create($data));

        return back()->with('success', 'Transaksi berhasil dicatat.');
    }
}
