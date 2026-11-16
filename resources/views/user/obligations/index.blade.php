@extends('layouts.user')

@section('title', 'Tagihan dan cicilan')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <x-page-header eyebrow="Pengingat berkala" title="Tagihan dan cicilan" description="Pembayaran hanya dicatat pada periode yang jatuh tempo.">
            <x-button type="button" variant="primary" icon="tambah" x-data x-on:click="$dispatch('open-modal', 'obligation-create')">Tambah pengingat</x-button>
        </x-page-header>
        @include('user.partials.finance-subnav')
        <form method="GET" action="{{ route('user.obligations.index') }}" class="surface-card p-4" data-auto-submit>
            <x-segmented name="status" :label="__('ui.entity_status.active').' / '.__('ui.entity_status.archived')" :options="$statusOptions" :value="$status" />
            <x-button type="submit" class="sr-only" data-submit-default>{{ __('ui.habit.list_submit') }}</x-button>
        </form>
        <x-bottom-sheet name="obligation-create" title="Tambah pengingat" :show="request('create') === 'obligation' || (old('form_context') === 'obligation-create' && $errors->any())">
            @if (old('form_context') === 'obligation-create')
                <x-error-summary :errors="$errors->messages()" title="Pengingat belum tersimpan. Periksa isian berikut:" />
            @endif
            <form method="POST" action="{{ route('user.obligations.store') }}" class="space-y-4" data-dirty-guard>
                @csrf
                <input type="hidden" name="form_context" value="obligation-create">
                @include('user.obligations.form', ['lockedByPayments' => false])
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <x-button type="button" variant="secondary" icon="kembali" x-on:click="$dispatch('close')">Batal</x-button>
                    <x-button type="submit" variant="primary" icon="tambah" loading-text="Menyimpan pengingat…">Simpan pengingat</x-button>
                </div>
            </form>
        </x-bottom-sheet>
        <section class="space-y-4">
            @forelse ($obligations as $obligation)
                <article class="surface-card p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div><h2 class="font-semibold">{{ $obligation->title }}</h2><p class="text-sm text-muted">{{ $obligation->kind->label() }} · {{ rupiah($obligation->amount) }} · tanggal {{ $obligation->due_day }} setiap bulan</p>
                            @if($obligation->kind->value === 'installment')<p class="mt-1 text-sm text-muted">{{ $obligation->paid_installments }} dari {{ $obligation->installments_total }} · sisa {{ max(0, $obligation->installments_total - $obligation->paid_installments) }}</p>@endif
                        </div>
                        @if ($status === 'active')
                            <x-button href="{{ route('user.obligations.edit', $obligation) }}" variant="secondary" icon="ubah">Ubah pengingat</x-button>
                            <x-confirm-action :action="route('user.obligations.archive', $obligation)" method="PATCH"
                                title="Arsipkan pengingat?"
                                :message="'Pengingat '.$obligation->title.' tidak lagi muncul di agenda. Riwayat pembayarannya tetap tersimpan.'"
                                label="Arsipkan" tone="secondary" icon="nonaktifkan" />
                        @else
                            <form method="POST" action="{{ route('user.obligations.restore', $obligation) }}">
                                @csrf
                                @method('PATCH')
                                <x-button variant="secondary" icon="kembali">Pulihkan</x-button>
                            </form>
                            @if ($obligation->payments->isEmpty())
                                <x-confirm-action :action="route('user.obligations.destroy', $obligation)"
                                    title="Hapus pengingat permanen?"
                                    :message="'Pengingat '.$obligation->title.' akan dihapus permanen.'"
                                    label="Hapus permanen" />
                            @else
                                <p class="text-xs text-muted">Pengingat memiliki riwayat pembayaran dan tidak dapat dihapus.</p>
                            @endif
                        @endif
                        @if ($status === 'active' && $obligation->next_due)
                            <form method="POST" action="{{ route('user.obligations.pay', $obligation) }}" class="flex flex-wrap items-end gap-2">
                                @csrf
                                <input type="hidden" name="due_on" value="{{ $obligation->next_due->toDateString() }}">
                                <div><label class="field-label" for="paid-{{ $obligation->id }}">Dibayar pada</label><input id="paid-{{ $obligation->id }}" name="paid_on" type="date" value="{{ $today }}" required class="form-control"></div>
                                <x-money-input id="paid-amount-{{ $obligation->id }}" name="amount_paid" label="Nominal" :value="$obligation->amount" />
                                <select name="wallet_id" aria-label="Dompet pembayaran" class="form-control"><option value="">Tanpa dompet</option>@foreach ($wallets as $wallet)<option value="{{ $wallet->id }}" @selected($obligation->wallet_id == $wallet->id)>{{ $wallet->name }}</option>@endforeach</select>
                                <x-button variant="primary" icon="selesai" loading-text="Mencatat pembayaran…">Tandai dibayar</x-button>
                            </form>
                        @else
                            <span class="text-sm text-muted">{{ $obligation->installments_complete ? 'Semua cicilan sudah dicatat.' : 'Belum ada periode yang jatuh tempo.' }}</span>
                        @endif
                    </div>
                    @if ($obligation->next_due)<p class="mt-2 text-sm text-muted">Bayar periode tertua terlebih dahulu: {{ $obligation->next_due->translatedFormat('j F Y') }}.</p>@endif
                    <ul class="mt-4 divide-y divide-line border-t border-line">
                        @foreach ($obligation->periods as $period)
                            <li class="flex min-h-11 items-center justify-between gap-3 py-2 text-sm">
                                <span>{{ __('ui.agenda.due') }} {{ $period['date']->translatedFormat('j M Y') }}</span>
                                <span class="text-right {{ str_starts_with($period['status'], __('ui.agenda.overdue')) ? 'font-medium text-danger' : 'text-muted' }}">{{ $period['status'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @if ($obligation->payments->isNotEmpty())<p class="mt-2 text-xs text-muted">{{ $obligation->payments->count() }} pembayaran tercatat.</p>@endif
                    @foreach ($obligation->payments as $payment)
                        <div class="mt-2 flex items-center justify-between border-t border-line pt-2 text-sm"><span>{{ __('ui.agenda.due') }} {{ $payment->due_on->translatedFormat('j M Y') }} · dibayar {{ rupiah($payment->amount_paid) }}</span>
                            @if ($status === 'active')
                                <x-confirm-action :action="route('user.obligations.payments.cancel', [$obligation, $payment])"
                                    title="Batalkan pembayaran?"
                                    :message="'Pembayaran '.rupiah($payment->amount_paid).' untuk periode '.$payment->due_on->translatedFormat('j F Y').' akan dihapus dan saldo dompet dihitung ulang.'"
                                    label="Batalkan pembayaran" icon="kembali" />
                            @endif
                        </div>
                    @endforeach
                </article>
            @empty
                <x-empty-state
                    class="surface-card"
                    title="Belum ada tagihan atau cicilan."
                    description="Catat pembayaran rutin agar tanggal jatuh tempo mudah diingat."
                    :action-url="route('user.obligations.index', ['create' => 'obligation'])"
                    action-label="Tambah tagihan pertama"
                >
                    <x-slot:icon><x-icon name="tagihan" /></x-slot:icon>
                </x-empty-state>
            @endforelse
        </section>
    </div>
@endsection
