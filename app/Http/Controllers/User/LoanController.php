<?php

namespace App\Http\Controllers\User;

use App\Enums\LoanDirection;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Services\LoanBalance;
use App\Support\Db as DatabaseTransaction;
use App\Support\Feedback;
use App\Support\Rupiah;
use App\Support\UserClock;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LoanController extends Controller
{
    public function index(Request $request, LoanBalance $balances, UserClock $clock)
    {
        $this->authorize('viewAny', Loan::class);
        $statusOptions = [
            'active' => __('ui.entity_status.active'),
            'archived' => __('ui.entity_status.archived'),
        ];
        $status = $request->validate([
            'status' => ['nullable', Rule::in(array_keys($statusOptions))],
        ])['status'] ?? 'active';
        $loans = $request->user()->loans()
            ->when($status === 'archived', fn ($query) => $query->whereNotNull('archived_at'), fn ($query) => $query->whereNull('archived_at'))
            ->with('repayments')->orderByDesc('loan_date')->get();
        $today = $clock->date($request->user());
        $loans->each(function (Loan $loan) use ($balances, $today) {
            $remaining = $balances->remaining($loan);
            $loan->setAttribute('remaining_amount', $remaining);
            $loan->setAttribute('due_label', match (true) {
                $remaining === 0 => __('ui.agenda.loan_paid'),
                ! $loan->due_on => __('ui.agenda.loan_running'),
                $loan->due_on->toDateString() < $today => __('ui.agenda.late_days', [
                    'days' => Carbon::parse($loan->due_on->toDateString())->diffInDays(Carbon::parse($today)),
                ]),
                $loan->due_on->toDateString() === $today => __('ui.agenda.due_today'),
                $loan->due_on->lte(Carbon::parse($today)->addDays(3)) => __('ui.agenda.loan_due_in_days', [
                    'days' => $loan->due_on->diffInDays(Carbon::parse($today)),
                ]),
                default => __('ui.agenda.loan_running'),
            });
            if ($loan->direction === LoanDirection::Lent && $loan->due_on && $remaining > 0) {
                $loan->setAttribute('reminder_message', __('messages.loan_reminder', [
                    'name' => $loan->counterparty,
                    'amount' => rupiah($remaining),
                    'date' => $loan->due_on->translatedFormat('j F Y'),
                ]));
            }
        });
        $wallets = $request->user()->wallets()->whereNull('archived_at')->orderBy('name')->get();

        $lent = $loans->where('direction', LoanDirection::Lent)->sum('remaining_amount');
        $borrowed = $loans->where('direction', LoanDirection::Borrowed)->sum('remaining_amount');

        return view('user.loans.index', compact('loans', 'wallets', 'today', 'lent', 'borrowed', 'status', 'statusOptions'));
    }

    public function archive(Request $request, Loan $loan)
    {
        $this->authorize('update', $loan);
        abort_if($loan->user_id !== $request->user()->id || $loan->archived_at, 404);
        $loan->update(['archived_at' => now()]);

        return Feedback::success(back(), __('ui.feedback.loan_archived'));
    }

    public function restore(Request $request, Loan $loan)
    {
        $this->authorize('update', $loan);
        abort_if($loan->user_id !== $request->user()->id || ! $loan->archived_at, 404);
        $loan->update(['archived_at' => null]);

        return Feedback::success(back(), __('ui.feedback.record_restored'));
    }

    public function destroy(Request $request, Loan $loan)
    {
        $this->authorize('delete', $loan);
        abort_if($loan->user_id !== $request->user()->id || ! $loan->archived_at, 404);
        DatabaseTransaction::immediate(function () use ($request, $loan) {
            $locked = $request->user()->loans()->lockForUpdate()->findOrFail($loan->id);
            abort_if($locked->repayments()->exists(), 422, __('ui.feedback.loan_has_repayments'));
            $request->user()->transactions()
                ->where('source_type', 'loan')
                ->where('source_id', $locked->id)
                ->delete();
            $locked->delete();
        });

        return Feedback::success(back(), __('ui.feedback.record_deleted'));
    }

    public function store(Request $request, UserClock $clock)
    {
        $this->authorize('create', Loan::class);
        $request->merge(['amount' => Rupiah::normalize($request->input('amount'))]);
        $data = $request->validate([
            'direction' => ['required', Rule::in(array_column(LoanDirection::cases(), 'value'))],
            'counterparty' => ['required', 'string', 'max:80'],
            'amount' => ['required', 'integer', 'between:1,'.Rupiah::MAX_AMOUNT],
            'loan_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$clock->date($request->user())],
            'due_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:loan_date'],
            'wallet_id' => ['nullable', 'integer', Rule::exists('wallets', 'id')->where('user_id', $request->user()->id)->whereNull('archived_at')],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        DB::transaction(function () use ($request, $data) {
            $loan = $request->user()->loans()->create($data);
            if (! empty($data['wallet_id'])) {
                $request->user()->transactions()->create([
                    'wallet_id' => $data['wallet_id'],
                    'type' => $data['direction'] === LoanDirection::Borrowed->value ? TransactionType::Income : TransactionType::Expense,
                    'amount' => $data['amount'],
                    'occurred_on' => $data['loan_date'],
                    'note' => ($data['direction'] === LoanDirection::Borrowed->value ? 'Pinjaman dari ' : 'Piutang kepada ').$data['counterparty'],
                    'source_type' => 'loan',
                    'source_id' => $loan->id,
                ]);
            }
        });

        return Feedback::success(back(), __('ui.feedback.loan_created'));
    }

    public function edit(Request $request, Loan $loan, UserClock $clock)
    {
        $this->authorize('update', $loan);
        abort_if($loan->user_id !== $request->user()->id || $loan->archived_at, 404);
        $wallets = $request->user()->wallets()->whereNull('archived_at')->orderBy('name')->get();
        $lockedByRepayments = $loan->repayments()->exists();
        $today = $clock->date($request->user());

        return view('user.loans.edit', compact('loan', 'wallets', 'lockedByRepayments', 'today'));
    }

    public function update(Request $request, Loan $loan, UserClock $clock)
    {
        $this->authorize('update', $loan);
        abort_if($loan->user_id !== $request->user()->id || $loan->archived_at, 404);
        $hasRepayments = $loan->repayments()->exists();
        $rules = [
            'counterparty' => ['required', 'string', 'max:80'],
            'due_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:loan_date'],
            'note' => ['nullable', 'string', 'max:255'],
        ];

        if (! $hasRepayments) {
            $request->merge(['amount' => Rupiah::normalize($request->input('amount'))]);
            $rules += [
                'direction' => ['required', Rule::in(array_column(LoanDirection::cases(), 'value'))],
                'amount' => ['required', 'integer', 'between:1,'.Rupiah::MAX_AMOUNT],
                'loan_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$clock->date($request->user())],
                'wallet_id' => ['nullable', 'integer', Rule::exists('wallets', 'id')->where('user_id', $request->user()->id)->whereNull('archived_at')],
            ];
            $rules['due_on'] = ['nullable', 'date_format:Y-m-d', 'after_or_equal:loan_date'];
        } else {
            $rules['due_on'] = ['nullable', 'date_format:Y-m-d', 'after_or_equal:'.$loan->loan_date->toDateString()];
        }

        $data = $request->validate($rules);
        DatabaseTransaction::immediate(function () use ($request, $loan, $data) {
            $lockedLoan = $request->user()->loans()->lockForUpdate()->findOrFail($loan->id);
            $hasRepayments = $lockedLoan->repayments()->lockForUpdate()->exists();
            $lockedFields = ['direction', 'amount', 'loan_date', 'wallet_id'];

            if ($hasRepayments) {
                foreach ($lockedFields as $field) {
                    if (! array_key_exists($field, $data)) {
                        continue;
                    }

                    $currentValue = match ($field) {
                        'direction' => $lockedLoan->direction->value,
                        'amount' => (string) $lockedLoan->amount,
                        'loan_date' => $lockedLoan->loan_date->toDateString(),
                        'wallet_id' => (string) ($lockedLoan->wallet_id ?? ''),
                    };

                    if ((string) $data[$field] !== $currentValue) {
                        abort(422, __('ui.agenda.loan_due_locked'));
                    }
                }

                $data = array_diff_key($data, array_flip($lockedFields));
            }

            $lockedLoan->update($data);
            $source = $request->user()->transactions()
                ->where('source_type', 'loan')
                ->where('source_id', $lockedLoan->id)
                ->first();

            if ($lockedLoan->wallet_id === null) {
                $source?->delete();

                return;
            }

            $sourceData = [
                'wallet_id' => $lockedLoan->wallet_id,
                'type' => $lockedLoan->direction === LoanDirection::Borrowed ? TransactionType::Income : TransactionType::Expense,
                'amount' => $lockedLoan->amount,
                'occurred_on' => $lockedLoan->loan_date,
                'note' => ($lockedLoan->direction === LoanDirection::Borrowed ? 'Pinjaman dari ' : 'Piutang kepada ').$lockedLoan->counterparty,
            ];

            if ($source) {
                $source->update($sourceData);
            } else {
                $request->user()->transactions()->create($sourceData + [
                    'source_type' => 'loan',
                    'source_id' => $lockedLoan->id,
                ]);
            }
        });

        return Feedback::success(redirect()->route('user.loans.index'), __('ui.feedback.loan_updated'));
    }

    public function repay(Request $request, Loan $loan, LoanBalance $balances, UserClock $clock)
    {
        $this->authorize('update', $loan);
        abort_if($loan->user_id !== $request->user()->id || $loan->archived_at, 404);
        $request->merge(['amount' => Rupiah::normalize($request->input('amount'))]);
        $data = $request->validate([
            'amount' => ['required', 'integer', 'between:1,'.Rupiah::MAX_AMOUNT],
            'paid_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$clock->date($request->user())],
            'wallet_id' => ['nullable', 'integer', Rule::exists('wallets', 'id')->where('user_id', $request->user()->id)->whereNull('archived_at')],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        DatabaseTransaction::immediate(function () use ($request, $loan, $data, $balances) {
            $lockedLoan = $request->user()->loans()->lockForUpdate()->findOrFail($loan->id);
            $balance = $balances->remaining($lockedLoan);
            abort_if($data['amount'] > $balance, 422, __('ui.feedback.loan_payment_exceeds_remaining'));
            $repayment = $request->user()->loanRepayments()->create([
                'loan_id' => $lockedLoan->id,
                'amount' => $data['amount'],
                'paid_on' => $data['paid_on'],
                'wallet_id' => $data['wallet_id'] ?? null,
                'note' => $data['note'] ?? null,
            ]);
            if (! empty($data['wallet_id'])) {
                $transaction = $request->user()->transactions()->create([
                    'wallet_id' => $data['wallet_id'],
                    'type' => $lockedLoan->direction === LoanDirection::Borrowed ? TransactionType::Expense : TransactionType::Income,
                    'amount' => $data['amount'],
                    'occurred_on' => $data['paid_on'],
                    'note' => 'Pembayaran: '.$lockedLoan->counterparty,
                    'source_type' => 'loan_repayment',
                    'source_id' => $repayment->id,
                ]);
                $repayment->update(['transaction_id' => $transaction->id]);
            }
        });

        return Feedback::success(back(), __('ui.feedback.payment_recorded'));
    }

    public function cancelRepayment(Request $request, Loan $loan, \App\Models\LoanRepayment $repayment)
    {
        $this->authorize('update', $loan);
        abort_if($loan->user_id !== $request->user()->id
            || $loan->archived_at
            || $repayment->loan_id !== $loan->id
            || $repayment->user_id !== $request->user()->id, 404);
        DB::transaction(function () use ($repayment) {
            if ($repayment->transaction_id) {
                \App\Models\Transaction::whereKey($repayment->transaction_id)->delete();
            }
            $repayment->delete();
        });

        return Feedback::success(back(), __('ui.feedback.loan_payment_cancelled'));
    }
}
