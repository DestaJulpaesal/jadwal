<?php

namespace App\Http\Controllers\User;

use App\Enums\MoneyCategoryType;
use App\Enums\ObligationKind;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Obligation;
use App\Models\ObligationPayment;
use App\Models\Transaction;
use App\Services\ObligationSchedule;
use App\Services\ObligationScheduler;
use App\Support\Db as DatabaseTransaction;
use App\Support\Feedback;
use App\Support\Rupiah;
use App\Support\UserClock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ObligationController extends Controller
{
    public function index(Request $request, UserClock $clock, ObligationSchedule $obligationSchedule)
    {
        $this->authorize('viewAny', Obligation::class);
        $statusOptions = [
            'active' => __('ui.entity_status.active'),
            'archived' => __('ui.entity_status.archived'),
        ];
        $status = $request->validate([
            'status' => ['nullable', Rule::in(array_keys($statusOptions))],
        ])['status'] ?? 'active';
        $today = $clock->date($request->user());
        $obligations = $request->user()->obligations()
            ->when($status === 'archived', fn ($query) => $query->whereNotNull('archived_at'), fn ($query) => $query->whereNull('archived_at'))
            ->with('payments')->orderBy('due_day')->get();
        $obligations->each(function (Obligation $obligation) use ($today, $obligationSchedule) {
            $lastDate = \Illuminate\Support\Carbon::parse($today)->addMonths(12)->toDateString();
            $startsOn = $obligation->starts_on->toDateString();
            $periods = $obligationSchedule->scheduledPeriods(
                $obligation,
                $startsOn,
                $lastDate
            );
            $periods = collect($periods)
                ->map(function ($date) use ($obligation, $today) {
                    $dateKey = $date->toDateString();
                    $payment = $obligation->payments->first(fn ($item) => $item->due_on->toDateString() === $dateKey);
                    $status = match (true) {
                        $payment !== null => __('ui.agenda.paid'),
                        $dateKey < $today => __('ui.agenda.late_days', [
                            'days' => abs((int) $date->diffInDays(\Illuminate\Support\Carbon::parse($today))),
                        ]),
                        $dateKey === $today => __('ui.agenda.due_today'),
                        default => __('ui.agenda.not_due'),
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
            $obligation->setAttribute('next_due', $due->first()['date'] ?? null);
            $obligation->setAttribute('paid_installments', $obligation->payments->count());
        });
        $wallets = $request->user()->wallets()->whereNull('archived_at')->orderBy('name')->get();
        $categories = $request->user()->moneyCategories()->where('type', MoneyCategoryType::Expense->value)
            ->whereNull('archived_at')->orderBy('name')->get();

        $obligation = new Obligation;

        return view('user.obligations.index', compact('obligations', 'wallets', 'categories', 'today', 'obligation', 'status', 'statusOptions'));
    }

    public function archive(Request $request, Obligation $obligation)
    {
        $this->authorize('update', $obligation);
        abort_if($obligation->user_id !== $request->user()->id || $obligation->archived_at, 404);
        $obligation->update(['archived_at' => now()]);

        return Feedback::success(back(), __('ui.feedback.obligation_archived'));
    }

    public function restore(Request $request, Obligation $obligation)
    {
        $this->authorize('update', $obligation);
        abort_if($obligation->user_id !== $request->user()->id || ! $obligation->archived_at, 404);
        $obligation->update(['archived_at' => null]);

        return Feedback::success(back(), __('ui.feedback.record_restored'));
    }

    public function destroy(Request $request, Obligation $obligation)
    {
        $this->authorize('delete', $obligation);
        abort_if($obligation->user_id !== $request->user()->id || ! $obligation->archived_at, 404);
        DatabaseTransaction::immediate(function () use ($request, $obligation) {
            $locked = $request->user()->obligations()->lockForUpdate()->findOrFail($obligation->id);
            abort_if($locked->payments()->exists(), 422, __('ui.feedback.obligation_has_payments'));
            $locked->delete();
        });

        return Feedback::success(back(), __('ui.feedback.record_deleted'));
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
            'money_category_id' => ['nullable', 'integer', Rule::exists('money_categories', 'id')->where('user_id', $request->user()->id)->where('type', MoneyCategoryType::Expense->value)->whereNull('archived_at')],
        ]);
        if ($data['kind'] === ObligationKind::Installment->value) {
            $request->validate(['installments_total' => ['required', 'integer', 'between:1,120']]);
        }
        $request->user()->obligations()->create($data);

        return Feedback::success(back(), __('ui.feedback.obligation_created'));
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

        return Feedback::success(redirect()->route('user.obligations.index'), __('ui.feedback.obligation_updated'));
    }

    public function pay(
        Request $request,
        Obligation $obligation,
        UserClock $clock,
        ObligationScheduler $scheduler,
        ObligationSchedule $obligationSchedule
    ) {
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
        abort_unless($expectedDate === $data['due_on'], 422, __('ui.feedback.obligation_period_mismatch'));
        abort_if($data['due_on'] < $obligation->starts_on->toDateString(), 422, __('ui.feedback.obligation_period_before_start'));
        abort_if($data['due_on'] > $clock->date($request->user()), 422, __('ui.feedback.obligation_not_due'));

        DatabaseTransaction::immediate(function () use ($data, $obligation, $request, $obligationSchedule, $clock) {
            $lockedObligation = $request->user()->obligations()->lockForUpdate()->findOrFail($obligation->id);
            $duplicate = $lockedObligation->payments()->where('due_on', $data['due_on'])->lockForUpdate()->exists();
            abort_if($duplicate, 422, __('ui.feedback.obligation_already_paid'));
            $today = $clock->date($request->user());
            $oldestUnpaid = $obligationSchedule->duePeriods(
                $lockedObligation,
                $lockedObligation->starts_on->toDateString(),
                $today
            )[0] ?? null;
            abort_unless(
                $oldestUnpaid && $oldestUnpaid->toDateString() === $data['due_on'],
                422,
                __('ui.feedback.obligation_oldest_first')
            );
            if ($lockedObligation->kind === ObligationKind::Installment) {
                abort_if(
                    $lockedObligation->payments()->count() >= $lockedObligation->installments_total,
                    422,
                    __('ui.feedback.installments_complete')
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

        return Feedback::success(back(), __('ui.feedback.payment_recorded'));
    }

    public function cancelPayment(Request $request, Obligation $obligation, ObligationPayment $payment)
    {
        $this->authorize('update', $obligation);
        abort_if($obligation->user_id !== $request->user()->id
            || $obligation->archived_at
            || $payment->obligation_id !== $obligation->id
            || $payment->user_id !== $request->user()->id, 404);
        DB::transaction(function () use ($payment) {
            if ($payment->transaction_id) {
                Transaction::whereKey($payment->transaction_id)->delete();
            }
            $payment->delete();
        });

        return Feedback::success(back(), __('ui.feedback.obligation_payment_cancelled'));
    }
}
