<?php

namespace App\Http\Controllers\User;

use App\Enums\LoanDirection;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Services\LoanBalance;
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
        $loans = $request->user()->loans()->whereNull('archived_at')->with('repayments')->orderByDesc('loan_date')->get();
        $today = $clock->date($request->user());
        $loans->each(function (Loan $loan) use ($balances, $today) {
            $remaining = $balances->remaining($loan);
            $loan->setAttribute('remaining_amount', $remaining);
            $loan->setAttribute('due_label', match (true) {
                $remaining === 0 => 'Lunas',
                ! $loan->due_on => 'Berjalan',
                $loan->due_on->toDateString() < $today => 'Terlambat '.Carbon::parse($loan->due_on->toDateString())->diffInDays(Carbon::parse($today)).' hari',
                $loan->due_on->toDateString() === $today => 'Jatuh tempo hari ini',
                $loan->due_on->lte(Carbon::parse($today)->addDays(3)) => 'Jatuh tempo '.$loan->due_on->diffInDays(Carbon::parse($today)).' hari lagi',
                default => 'Berjalan',
            });
            if ($loan->direction === LoanDirection::Lent && $loan->due_on && $remaining > 0) {
                $loan->setAttribute('reminder_message', __('messages.loan_reminder', [
                    'name' => $loan->counterparty,
                    'amount' => rupiah($loan->amount),
                    'date' => $loan->due_on->translatedFormat('j F Y'),
                ]));
            }
        });
        $wallets = $request->user()->wallets()->whereNull('archived_at')->orderBy('name')->get();

        $lent = $loans->where('direction', LoanDirection::Lent)->sum('remaining_amount');
        $borrowed = $loans->where('direction', LoanDirection::Borrowed)->sum('remaining_amount');

        return view('user.loans.index', compact('loans', 'wallets', 'today', 'lent', 'borrowed'));
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

        return back()->with('success', 'Utang/piutang berhasil dicatat.');
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
        DB::transaction(function () use ($request, $loan, $data, $balances) {
            $lockedLoan = $request->user()->loans()->lockForUpdate()->findOrFail($loan->id);
            $balance = $balances->remaining($lockedLoan);
            abort_if($data['amount'] > $balance, 422, 'Pembayaran melebihi sisa utang/piutang.');
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

        return back()->with('success', 'Pembayaran berhasil dicatat.');
    }

    public function cancelRepayment(Request $request, Loan $loan, \App\Models\LoanRepayment $repayment)
    {
        $this->authorize('update', $loan);
        abort_if($loan->user_id !== $request->user()->id
            || $repayment->loan_id !== $loan->id
            || $repayment->user_id !== $request->user()->id, 404);
        DB::transaction(function () use ($repayment) {
            if ($repayment->transaction_id) {
                \App\Models\Transaction::whereKey($repayment->transaction_id)->delete();
            }
            $repayment->delete();
        });

        return back()->with('success', 'Pembayaran dibatalkan dan sisa utang/piutang diperbarui.');
    }
}
