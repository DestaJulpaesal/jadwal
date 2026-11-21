<?php

namespace App\Http\Controllers\User;

use App\Enums\LoanDirection;
use App\Enums\MoneyCategoryType;
use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Http\Controllers\Controller;
use App\Models\MoneyCategory;
use App\Models\Obligation;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\BudgetProgress;
use App\Services\LoanBalance;
use App\Services\MonthlyCategoryReport;
use App\Services\MonthlySummary;
use App\Services\ObligationSchedule;
use App\Services\WalletBalance;
use App\Support\Db as DatabaseTransaction;
use App\Support\Feedback;
use App\Support\MonthRange;
use App\Support\Rupiah;
use App\Support\UserClock;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MoneyController extends Controller
{
    public function index(
        Request $request,
        WalletBalance $balances,
        MonthlySummary $summary,
        MonthlyCategoryReport $categoryReport,
        ObligationSchedule $obligationSchedule,
        LoanBalance $loanBalances,
        BudgetProgress $budgetProgressService,
        UserClock $clock
    ) {
        $this->authorize('viewAny', Transaction::class);
        $user = $request->user();
        $today = $clock->date($user);
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'section' => ['nullable', Rule::in(['overview', 'transactions', 'wallets', 'reports'])],
            'status' => ['nullable', Rule::in(['active', 'archived'])],
        ]);
        $month = $filters['month'] ?? substr($today, 0, 7);
        $monthRange = MonthRange::fromString($month);
        $previousRange = $monthRange->previous();
        $section = $filters['section'] ?? 'overview';
        $status = $filters['status'] ?? 'active';
        $statusOptions = [
            'active' => __('ui.entity_status.active'),
            'archived' => __('ui.entity_status.archived'),
        ];
        $needsWalletData = in_array($section, ['transactions', 'wallets'], true);
        $wallets = $needsWalletData
            ? $balances->forUser($user, $section === 'wallets' && $status === 'archived')
            : collect();
        $negativeWallets = $section === 'wallets' && $status === 'archived'
            ? collect()
            : $wallets->filter(fn (Wallet $wallet) => $wallet->calculated_balance < 0)->values();
        $transactions = $section === 'transactions'
            ? $user->transactions()->with(['wallet', 'destinationWallet', 'moneyCategory'])
                ->whereBetween('occurred_on', [
                    $monthRange->start->toDateString(),
                    $monthRange->end->toDateString(),
                ])->orderByDesc('occurred_on')->orderByDesc('id')->paginate(15)->withQueryString()
            : collect();
        $categories = $needsWalletData
            ? $user->moneyCategories()
                ->when($section === 'wallets' && $status === 'archived',
                    fn ($query) => $query->whereNotNull('archived_at'),
                    fn ($query) => $query->whereNull('archived_at'))
                ->withCount(['transactions', 'obligations', 'budgets'])
                ->orderBy('type')->orderBy('name')->get()
            : collect();
        $previousMonth = $previousRange->key();
        $monthLabel = $monthRange->start->translatedFormat('F Y');
        $previousMonthLabel = $previousRange->start->translatedFormat('F');
        $needsSummary = in_array($section, ['overview', 'reports'], true);
        $totals = $needsSummary ? $summary->forMonth($user, $month) : ['income' => 0, 'expense' => 0, 'net' => 0];
        $previous = $section === 'reports'
            ? $summary->forMonth($user, $previousMonth)
            : ['income' => 0, 'expense' => 0, 'net' => 0];
        $budgetProgress = collect();
        $budgetCategories = collect();
        if ($section === 'overview') {
            $budgetProgress = $budgetProgressService->forMonth($user, $monthRange);
            $budgetCategories = $user->moneyCategories()
                ->where('type', MoneyCategoryType::Expense->value)
                ->whereNull('archived_at')
                ->orderBy('name')
                ->get();
        }
        $plannedObligations = collect();
        if ($section === 'overview') {
            $plannedObligations = $user->obligations()
                ->whereNull('archived_at')
                ->with(['payments', 'wallet', 'moneyCategory'])
                ->get()
                ->map(function (Obligation $obligation) use ($monthRange, $obligationSchedule) {
                    $dueDate = $obligationSchedule->duePeriods(
                        $obligation,
                        $monthRange->start->toDateString(),
                        $monthRange->end->toDateString()
                    )[0] ?? null;

                    if ($dueDate === null) {
                        return null;
                    }

                    $obligation->setAttribute('planned_due_on', $dueDate);

                    return $obligation;
                })
                ->filter()
                ->sortBy('planned_due_on')
                ->values();
        }
        $plannedTotal = (int) $plannedObligations->sum('amount');
        $plannedLoans = collect();
        if ($section === 'overview') {
            $plannedLoans = $user->loans()
                ->whereNull('archived_at')
                ->whereNotNull('due_on')
                ->with('repayments')
                ->get()
                ->map(function ($loan) use ($monthRange, $loanBalances, $today) {
                    $monthDate = $monthRange->start;
                    $remaining = $loanBalances->remaining($loan);
                    $isOverdue = $monthRange->key() === substr($today, 0, 7)
                        && $loan->due_on->toDateString() < $today;

                    if ($remaining === 0 || (! $loan->due_on->isSameMonth($monthDate) && ! $isOverdue)) {
                        return null;
                    }

                    $loan->setAttribute('planned_amount', $remaining);
                    $loan->setAttribute('planned_status', $isOverdue ? 'overdue' : 'due');

                    return $loan;
                })
                ->filter()
                ->sortBy('due_on')
                ->values();
        }
        $plannedLoanIncome = (int) $plannedLoans
            ->where('direction', LoanDirection::Lent)
            ->sum('planned_amount');
        $plannedLoanExpense = (int) $plannedLoans
            ->where('direction', LoanDirection::Borrowed)
            ->sum('planned_amount');
        $reportCategories = $section === 'reports'
            ? $categoryReport->topExpenses($user, $monthRange)
            : collect();
        $largest = (int) $reportCategories->max('total');
        $comparison = $section !== 'reports' || $previous['expense'] === 0
            ? null
            : (int) round(($totals['expense'] - $previous['expense']) * 100 / $previous['expense']);
        $walletTypes = WalletType::cases();

        return view('user.money.index', compact(
            'wallets',
            'negativeWallets',
            'transactions',
            'categories',
            'totals',
            'month',
            'monthLabel',
            'today',
            'walletTypes',
            'previous',
            'previousMonth',
            'previousMonthLabel',
            'reportCategories',
            'largest',
            'comparison',
            'plannedObligations',
            'plannedTotal',
            'plannedLoans',
            'plannedLoanIncome',
            'plannedLoanExpense',
            'section',
            'status',
            'statusOptions',
            'budgetProgress',
            'budgetCategories'
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

        return Feedback::success(back(), __('ui.feedback.wallet_created'));
    }

    public function archiveWallet(Request $request, Wallet $wallet)
    {
        $this->authorize('update', $wallet);
        abort_if($wallet->user_id !== $request->user()->id || $wallet->archived_at, 404);
        $wallet->update(['archived_at' => now()]);

        return Feedback::success(back(), __('ui.feedback.wallet_archived'));
    }

    public function restoreWallet(Request $request, Wallet $wallet)
    {
        $this->authorize('update', $wallet);
        abort_if($wallet->user_id !== $request->user()->id || ! $wallet->archived_at, 404);
        $wallet->update(['archived_at' => null]);

        return Feedback::success(back(), __('ui.feedback.record_restored'));
    }

    public function destroyWallet(Request $request, Wallet $wallet)
    {
        $this->authorize('delete', $wallet);
        abort_if($wallet->user_id !== $request->user()->id || ! $wallet->archived_at, 404);
        DatabaseTransaction::immediate(function () use ($request, $wallet) {
            $locked = $request->user()->wallets()->lockForUpdate()->findOrFail($wallet->id);
            $hasTransactions = Transaction::query()
                ->where('user_id', $request->user()->id)
                ->where(fn ($query) => $query->where('wallet_id', $locked->id)->orWhere('to_wallet_id', $locked->id))
                ->exists();
            $hasObligations = $request->user()->obligations()->where('wallet_id', $locked->id)->exists();
            $hasLoans = $request->user()->loans()->where('wallet_id', $locked->id)->exists();
            abort_if($hasTransactions || $hasObligations || $hasLoans, 422, __('ui.feedback.wallet_in_use'));
            $locked->delete();
        });

        return Feedback::success(back(), __('ui.feedback.wallet_deleted'));
    }

    public function updateWallet(Request $request, Wallet $wallet)
    {
        $this->authorize('update', $wallet);
        abort_if($wallet->user_id !== $request->user()->id || $wallet->archived_at, 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
        ]);
        $wallet->update($data);

        return Feedback::success(back(), __('ui.feedback.wallet_updated'));
    }

    public function adjustWalletBalance(
        Request $request,
        Wallet $wallet,
        WalletBalance $balances,
        UserClock $clock
    ) {
        $this->authorize('update', $wallet);
        abort_if($wallet->user_id !== $request->user()->id || $wallet->archived_at, 404);
        $request->merge(['actual_balance' => Rupiah::normalize($request->input('actual_balance'))]);
        $data = $request->validate([
            'actual_balance' => [
                'required',
                'integer',
                'between:-'.Rupiah::MAX_AMOUNT.','.Rupiah::MAX_AMOUNT,
            ],
        ], [], ['actual_balance' => __('ui.wallet.adjustment.actual_balance')]);

        $adjusted = DatabaseTransaction::immediate(function () use ($request, $wallet, $data, $balances, $clock): bool {
            $lockedWallet = $request->user()->wallets()
                ->whereNull('archived_at')
                ->lockForUpdate()
                ->findOrFail($wallet->id);
            $difference = $data['actual_balance'] - $balances->forWallet($lockedWallet);

            if ($difference === 0) {
                return false;
            }

            if (abs($difference) > Rupiah::MAX_AMOUNT) {
                throw ValidationException::withMessages([
                    'actual_balance' => __('ui.feedback.wallet_adjustment_too_large'),
                ]);
            }

            $request->user()->transactions()->create([
                'wallet_id' => $lockedWallet->id,
                'type' => $difference > 0 ? TransactionType::Income : TransactionType::Expense,
                'amount' => abs($difference),
                'occurred_on' => $clock->date($request->user()),
                'note' => __('ui.money.adjustment_note'),
                'source_type' => 'adjustment',
                'source_id' => $lockedWallet->id,
            ]);

            return true;
        });

        return $adjusted
            ? Feedback::success(back(), __('ui.feedback.wallet_balance_adjusted'))
            : Feedback::info(back(), __('ui.feedback.wallet_balance_unchanged'));
    }

    public function storeCategory(Request $request)
    {
        $this->authorize('create', MoneyCategory::class);
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:60',
                $this->uniqueCategoryNameRule($request, (string) $request->input('type'), allowArchived: true),
            ],
            'type' => ['required', Rule::in(array_column(MoneyCategoryType::cases(), 'value'))],
        ]);
        $normalizedName = mb_strtolower($data['name'], 'UTF-8');
        $restored = DatabaseTransaction::immediate(function () use ($request, $data, $normalizedName): bool {
            $category = $request->user()->moneyCategories()
                ->where('type', $data['type'])
                ->where('name_normalized', $normalizedName)
                ->lockForUpdate()
                ->first();

            if ($category) {
                if (! $category->archived_at) {
                    throw ValidationException::withMessages([
                        'name' => __('validation.unique', ['attribute' => 'nama kategori']),
                    ]);
                }

                $category->update(['archived_at' => null]);

                return true;
            }

            $request->user()->moneyCategories()->create($data);

            return false;
        });

        if ($restored) {
            return Feedback::success(back(), __('ui.feedback.record_restored'));
        }

        return Feedback::success(back(), __('ui.feedback.money_category_created'));
    }

    public function updateCategory(Request $request, MoneyCategory $moneyCategory)
    {
        $this->authorize('update', $moneyCategory);
        abort_if($moneyCategory->user_id !== $request->user()->id, 404);
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:60',
                $this->uniqueCategoryNameRule($request, $moneyCategory->type->value, $moneyCategory->id),
            ],
        ]);
        $moneyCategory->update($data);

        return Feedback::success(back(), __('ui.feedback.money_category_updated'));
    }

    public function archiveCategory(Request $request, MoneyCategory $moneyCategory)
    {
        $this->authorize('update', $moneyCategory);
        abort_if($moneyCategory->user_id !== $request->user()->id || $moneyCategory->archived_at, 404);
        $moneyCategory->update(['archived_at' => now()]);

        return Feedback::success(back(), __('ui.feedback.money_category_archived'));
    }

    public function restoreCategory(Request $request, MoneyCategory $moneyCategory)
    {
        $this->authorize('update', $moneyCategory);
        abort_if($moneyCategory->user_id !== $request->user()->id || ! $moneyCategory->archived_at, 404);
        $moneyCategory->update(['archived_at' => null]);

        return Feedback::success(back(), __('ui.feedback.record_restored'));
    }

    public function destroyCategory(Request $request, MoneyCategory $moneyCategory)
    {
        $this->authorize('delete', $moneyCategory);
        abort_if($moneyCategory->user_id !== $request->user()->id || ! $moneyCategory->archived_at, 404);
        DatabaseTransaction::immediate(function () use ($request, $moneyCategory) {
            $locked = $request->user()->moneyCategories()->lockForUpdate()->findOrFail($moneyCategory->id);
            abort_if(
                $locked->transactions()->exists() || $locked->obligations()->exists() || $locked->budgets()->exists(),
                422,
                __('ui.feedback.money_category_in_use')
            );
            $locked->delete();
        });

        return Feedback::success(back(), __('ui.feedback.record_deleted'));
    }

    public function storeTransaction(Request $request)
    {
        $this->authorize('create', Transaction::class);
        $data = $this->validatedTransactionData($request);

        DB::transaction(fn () => $request->user()->transactions()->create($data));

        return Feedback::success(back(), __('ui.feedback.transaction_created'));
    }

    public function editTransaction(Request $request, Transaction $transaction)
    {
        $this->authorize('update', $transaction);
        abort_if($transaction->user_id !== $request->user()->id || $transaction->source_type !== null, 404);
        if ($this->transactionUsesArchivedRecord($transaction)) {
            return Feedback::error(
                $this->transactionIndexRedirect($transaction),
                __('ui.feedback.transaction_edit_archived')
            );
        }
        $wallets = $request->user()->wallets()
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get();
        $categories = $request->user()->moneyCategories()
            ->whereNull('archived_at')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('user.money.transactions.edit', compact('transaction', 'wallets', 'categories'));
    }

    public function updateTransaction(Request $request, Transaction $transaction)
    {
        $this->authorize('update', $transaction);
        abort_if($transaction->user_id !== $request->user()->id || $transaction->source_type !== null, 404);
        if ($this->transactionUsesArchivedRecord($transaction)) {
            return Feedback::error(
                $this->transactionIndexRedirect($transaction),
                __('ui.feedback.transaction_edit_archived')
            );
        }

        $data = $this->validatedTransactionData($request);
        DB::transaction(fn () => $transaction->update($data));

        return Feedback::success(redirect()->route('user.money.index', [
            'section' => 'transactions',
            'month' => substr($data['occurred_on'], 0, 7),
        ]), __('ui.feedback.transaction_updated'));
    }

    public function destroyTransaction(Request $request, Transaction $transaction)
    {
        $this->authorize('delete', $transaction);
        abort_if(
            $transaction->user_id !== $request->user()->id
                || ! in_array($transaction->source_type, [null, 'adjustment'], true),
            404
        );
        DatabaseTransaction::immediate(function () use ($request, $transaction) {
            $locked = $request->user()->transactions()->lockForUpdate()->findOrFail($transaction->id);
            abort_if(! in_array($locked->source_type, [null, 'adjustment'], true), 404);
            $locked->delete();
        });

        return Feedback::success(back(), __('ui.feedback.transaction_deleted'));
    }

    private function transactionDateLimit(Request $request): string
    {
        return app(UserClock::class)->today($request->user())->addYear()->toDateString();
    }

    private function validatedTransactionData(Request $request): array
    {
        $request->merge(['amount' => Rupiah::normalize($request->input('amount'))]);
        $data = $request->validate([
            'type' => ['required', Rule::in(array_column(TransactionType::cases(), 'value'))],
            'amount' => ['required', 'integer', 'between:1,'.Rupiah::MAX_AMOUNT],
            'wallet_id' => ['required', 'integer', Rule::exists('wallets', 'id')->where('user_id', $request->user()->id)->whereNull('archived_at')],
            'to_wallet_id' => ['nullable', 'integer', Rule::exists('wallets', 'id')->where('user_id', $request->user()->id)->whereNull('archived_at')],
            'money_category_id' => ['nullable', 'integer', Rule::exists('money_categories', 'id')->where('user_id', $request->user()->id)->whereNull('archived_at')],
            'occurred_on' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:2000-01-01',
                'before_or_equal:'.$this->transactionDateLimit($request),
            ],
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
            abort_unless($category->type->value === $data['type'], 422, __('ui.feedback.money_category_type_mismatch'));
        }

        return $data;
    }

    private function uniqueCategoryNameRule(
        Request $request,
        string $type,
        ?int $ignoreId = null,
        bool $allowArchived = false
    ): Closure {
        return function (string $attribute, mixed $value, Closure $fail) use ($request, $type, $ignoreId, $allowArchived): void {
            if (! is_string($value)) {
                return;
            }

            $query = $request->user()->moneyCategories()
                ->where('type', $type)
                ->where('name_normalized', mb_strtolower($value, 'UTF-8'));

            if ($ignoreId !== null) {
                $query->where('id', '!=', $ignoreId);
            }

            if ($allowArchived) {
                $query->whereNull('archived_at');
            }

            if ($query->exists()) {
                $fail(__('validation.unique', ['attribute' => 'nama kategori']));
            }
        };
    }

    private function transactionUsesArchivedRecord(Transaction $transaction): bool
    {
        return (bool) (
            $transaction->wallet?->archived_at
            || $transaction->destinationWallet?->archived_at
            || $transaction->moneyCategory?->archived_at
        );
    }

    private function transactionIndexRedirect(Transaction $transaction): RedirectResponse
    {
        return redirect()->route('user.money.index', [
            'section' => 'transactions',
            'month' => substr($transaction->occurred_on->toDateString(), 0, 7),
        ]);
    }
}
