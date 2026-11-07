<?php

namespace App\Http\Controllers\User;

use App\Enums\MoneyCategoryType;
use App\Enums\ObligationKind;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Obligation;
use App\Models\ObligationPayment;
use App\Models\Transaction;
use App\Services\ObligationScheduler;
use App\Support\Rupiah;
use App\Support\UserClock;
use App\Support\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ObligationController extends Controller
{
    public function index(Request $request, UserClock $clock, ObligationScheduler $scheduler)
    {
        $this->authorize('viewAny', Obligation::class);
        $today = $clock->date($request->user());
        $obligations = $request->user()->obligations()->whereNull('archived_at')->with('payments')->orderBy('due_day')->get();
        $obligations->each(function (Obligation $obligation) use ($today, $scheduler) {
            $lastDate = \Illuminate\Support\Carbon::parse($today)->addMonths(12)->toDateString();
            $monthCount = \Illuminate\Support\Carbon::parse($obligation->starts_on->toDateString())
                ->startOfMonth()->diffInMonths(\Illuminate\Support\Carbon::parse($lastDate)->startOfMonth()) + 1;
            $periods = $scheduler->periodsBetween(
                $obligation->starts_on->toDateString(),
                $obligation->due_day,
                $obligation->starts_on->toDateString(),
                $lastDate,
                $monthCount
            );
            if ($obligation->kind === ObligationKind::Installment
                && $obligation->payments->count() >= $obligation->installments_total) {
                $periods = [];
            }
            $periods = collect($periods)
                ->filter(fn ($date) => $date->gte($obligation->starts_on))
                ->map(function ($date) use ($obligation, $today) {
                    $payment = $obligation->payments->first(
                        fn ($item) => $item->due_on->toDateString() === $date->toDateString()
                    );
                    $status = match (true) {
                        $payment !== null => 'Lunas',
                        $date->toDateString() < $today => 'Terlambat '.$date->diffInDays(\Illuminate\Support\Carbon::parse($today)).' hari',
                        $date->toDateString() === $today => 'Jatuh tempo hari ini',
                        default => 'Belum jatuh tempo',
                    };

                    return ['date' => $date, 'payment' => $payment, 'status' => $status];
                })->values();
            $due = $periods->filter(
                fn ($period) => $period['payment'] === null && $period['date']->toDateString() <= $today
            );
            $upcoming = $periods->filter(
                fn ($period) => $period['payment'] === null && $period['date']->toDateString() > $today
            );
            $paid = $periods->filter(fn ($period) => $period['payment'] !== null)->sortByDesc(
                fn ($period) => $period['date']->timestamp
            );
            $visiblePeriods = $due->take(12);
            $remaining = 12 - $visiblePeriods->count();
            if ($remaining > 0) {
                $visiblePeriods = $visiblePeriods->concat($upcoming->take($remaining));
            }
            $remaining = 12 - $visiblePeriods->count();
            if ($remaining > 0) {
                $visiblePeriods = $visiblePeriods->concat($paid->take($remaining));
            }
            $visiblePeriods = $visiblePeriods->sortBy(fn ($period) => $period['date']->timestamp)->values();
            $obligation->setAttribute('periods', $visiblePeriods);
            $obligation->setAttribute('installments_complete', $obligation->kind === ObligationKind::Installment
                && $obligation->payments->count() >= $obligation->installments_total);
            $obligation->setAttribute('next_due', $visiblePeriods->first(
                fn ($period) => $period['payment'] === null && $period['date']->toDateString() <= $today
            )['date'] ?? null);
            $obligation->setAttribute('paid_installments', $obligation->payments->count());
        });
        $wallets = $request->user()->wallets()->whereNull('archived_at')->orderBy('name')->get();
        $categories = $request->user()->moneyCategories()->where('type', MoneyCategoryType::Expense->value)
            ->whereNull('archived_at')->orderBy('name')->get();

        $obligation = new Obligation;

        return view('user.obligations.index', compact('obligations', 'wallets', 'categories', 'today', 'scheduler', 'obligation'));
    }

    public function edit(Request $request, Obligation $obligation)
    {
        $this->authorize('update', $obligation);
        abort_if($obligation->user_id !== $request->user()->id || $obligation->archived_at, 404);
        $wallets = $request->user()->wallets()->whereNull('archived_at')->orderBy('name')->get();
        $categories = $request->user()->moneyCategories()->where('type', MoneyCategoryType::Expense->value)
            ->whereNull('archived_at')->orderBy('name')->get();
        $lockedByPayments = $obligation->payments()->exists();

        return view('user.obligations.edit', compact('obligation', 'wallets', 'categories', 'lockedByPayments'));
    }

    public function store(Request $request, UserClock $clock)
    {
        $this->authorize('create', Obligation::class);
        $request->merge(['amount' => Rupiah::normalize($request->input('amount'))]);
        $data = $request->validate([
            'kind' => ['required', Rule::in(array_column(ObligationKind::cases(), 'value'))],
            'title' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'integer', 'between:1,'.Rupiah::MAX_AMOUNT],
            'due_day' => ['required', 'integer', 'between:1,31'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'installments_total' => ['nullable', 'integer', 'between:1,120'],
            'remind_days_before' => ['required', 'integer', 'between:0,30'],
            'note' => ['nullable', 'string', 'max:255'],
            'wallet_id' => ['nullable', 'integer', Rule::exists('wallets', 'id')->where('user_id', $request->user()->id)->whereNull('archived_at')],
            'money_category_id' => ['nullable', 'integer', Rule::exists('money_categories', 'id')->where('user_id', $request->user()->id)->where('type', MoneyCategoryType::Expense->value)],
        ]);
        if ($data['kind'] === ObligationKind::Installment->value) {
            $request->validate(['installments_total' => ['required', 'integer', 'between:1,120']]);
        }
        $request->user()->obligations()->create($data);

        return Feedback::success(back(), 'Tagihan berhasil ditambahkan.');
    }

    public function update(Request $request, Obligation $obligation)
    {
        $this->authorize('update', $obligation);
        abort_if($obligation->user_id !== $request->user()->id || $obligation->archived_at, 404);
        $request->merge(['amount' => Rupiah::normalize($request->input('amount'))]);
        $hasPayments = $obligation->payments()->exists();
        $rules = [
            'title' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'integer', 'between:1,'.Rupiah::MAX_AMOUNT],
            'remind_days_before' => ['required', 'integer', 'between:0,30'],
            'note' => ['nullable', 'string', 'max:255'],
            'wallet_id' => ['nullable', 'integer', Rule::exists('wallets', 'id')->where('user_id', $request->user()->id)->whereNull('archived_at')],
            'money_category_id' => ['nullable', 'integer', Rule::exists('money_categories', 'id')->where('user_id', $request->user()->id)->where('type', MoneyCategoryType::Expense->value)->whereNull('archived_at')],
        ];

        if (! $hasPayments) {
            $rules += [
                'kind' => ['required', Rule::in(array_column(ObligationKind::cases(), 'value'))],
                'due_day' => ['required', 'integer', 'between:1,31'],
                'starts_on' => ['required', 'date_format:Y-m-d'],
                'installments_total' => ['nullable', 'integer', 'between:1,120'],
            ];
        }

        $data = $request->validate($rules);
        if (! $hasPayments && ($data['kind'] ?? null) === ObligationKind::Installment->value) {
            $request->validate(['installments_total' => ['required', 'integer', 'between:1,120']]);
        }
        if (! $hasPayments && ($data['kind'] ?? null) !== ObligationKind::Installment->value) {
            $data['installments_total'] = null;
        }
        $obligation->update($data);

        return Feedback::success(redirect()->route('user.obligations.index'), 'Pengingat berhasil diperbarui.');
    }

    public function pay(Request $request, Obligation $obligation, UserClock $clock, ObligationScheduler $scheduler)
    {
        $this->authorize('update', $obligation);
        abort_if($obligation->user_id !== $request->user()->id || $obligation->archived_at, 404);
        $request->merge(['amount_paid' => Rupiah::normalize($request->input('amount_paid'))]);
        $data = $request->validate([
            'due_on' => ['required', 'date_format:Y-m-d'],
            'paid_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$clock->date($request->user())],
            'amount_paid' => ['required', 'integer', 'between:1,'.Rupiah::MAX_AMOUNT],
            'wallet_id' => ['nullable', 'integer', Rule::exists('wallets', 'id')->where('user_id', $request->user()->id)->whereNull('archived_at')],
        ]);
        $expectedDate = $scheduler->dueDate(
            $obligation->due_day,
            (int) substr($data['due_on'], 0, 4),
            (int) substr($data['due_on'], 5, 2)
        )->toDateString();
        abort_unless($expectedDate === $data['due_on'], 422, 'Periode tagihan tidak cocok dengan tanggal jatuh tempo.');
        abort_if($data['due_on'] < $obligation->starts_on->toDateString(), 422, 'Periode ini sebelum tagihan dimulai.');
        abort_if($data['due_on'] > $clock->date($request->user()), 422, 'Pembayaran hanya dapat dicatat pada periode yang sudah jatuh tempo.');

        DB::transaction(function () use ($data, $obligation, $request, $scheduler, $clock) {
            $lockedObligation = $request->user()->obligations()->lockForUpdate()->findOrFail($obligation->id);
            $duplicate = $lockedObligation->payments()->where('due_on', $data['due_on'])->lockForUpdate()->exists();
            abort_if($duplicate, 422, 'Periode ini sudah ditandai lunas.');
            $today = $clock->date($request->user());
            $periodLimit = \Illuminate\Support\Carbon::parse($lockedObligation->starts_on->toDateString())
                ->startOfMonth()->diffInMonths(\Illuminate\Support\Carbon::parse($today)->startOfMonth()) + 1;
            $paidDueDates = $lockedObligation->payments()->pluck('due_on')->all();
            $oldestUnpaid = collect($scheduler->periodsBetween(
                $lockedObligation->starts_on->toDateString(),
                $lockedObligation->due_day,
                $lockedObligation->starts_on->toDateString(),
                $today,
                $periodLimit
            ))->first(fn ($date) => $date->gte($lockedObligation->starts_on)
                && $date->toDateString() <= $today
                && ! in_array($date->toDateString(), $paidDueDates, true));
            abort_unless(
                $oldestUnpaid && $oldestUnpaid->toDateString() === $data['due_on'],
                422,
                'Bayar periode tertua yang belum lunas terlebih dahulu.'
            );
            if ($lockedObligation->kind === ObligationKind::Installment) {
                abort_if(
                    $lockedObligation->payments()->count() >= $lockedObligation->installments_total,
                    422,
                    'Semua cicilan sudah dicatat.'
                );
            }
            $payment = $request->user()->obligationPayments()->create([
                'obligation_id' => $lockedObligation->id,
                'due_on' => $data['due_on'],
                'paid_on' => $data['paid_on'],
                'amount_paid' => $data['amount_paid'],
            ]);
            $transaction = null;
            if (! empty($data['wallet_id'])) {
                $transaction = $request->user()->transactions()->create([
                    'wallet_id' => $data['wallet_id'],
                    'type' => TransactionType::Expense,
                    'amount' => $data['amount_paid'],
                    'money_category_id' => $lockedObligation->money_category_id,
                    'occurred_on' => $data['paid_on'],
                    'note' => $lockedObligation->title,
                    'source_type' => 'obligation_payment',
                    'source_id' => $payment->id,
                ]);
                $payment->update(['transaction_id' => $transaction->id]);
            }
        });

        return Feedback::success(back(), 'Pembayaran berhasil dicatat.');
    }

    public function cancelPayment(Request $request, Obligation $obligation, ObligationPayment $payment)
    {
        $this->authorize('update', $obligation);
        abort_if($obligation->user_id !== $request->user()->id
            || $payment->obligation_id !== $obligation->id
            || $payment->user_id !== $request->user()->id, 404);
        DB::transaction(function () use ($payment) {
            if ($payment->transaction_id) {
                Transaction::whereKey($payment->transaction_id)->delete();
            }
            $payment->delete();
        });

        return Feedback::success(back(), 'Pembayaran dibatalkan dan catatan uangnya dipulihkan.');
    }
}
