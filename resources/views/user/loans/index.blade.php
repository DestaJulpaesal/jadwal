@extends('layouts.user')

@section('title', 'Utang dan piutang')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <x-page-header eyebrow="Catatan kesepakatan" title="Utang dan piutang" description="Pantau pinjaman, piutang, dan pembayaran yang sudah dicatat.">
            <x-button type="button" variant="primary" icon="tambah" x-data x-on:click="$dispatch('open-modal', 'loan-create')">Tambah catatan</x-button>
        </x-page-header>
        @include('user.partials.finance-subnav')
        <form method="GET" action="{{ route('user.loans.index') }}" class="surface-card p-4" data-auto-submit>
            <x-segmented name="status" :label="__('ui.entity_status.active').' / '.__('ui.entity_status.archived')" :options="$statusOptions" :value="$status" />
            <x-button type="submit" class="sr-only" data-submit-default>{{ __('ui.habit.list_submit') }}</x-button>
        </form>
        <x-bottom-sheet name="loan-create" title="Tambah utang/piutang" :show="request('create') === 'loan' || (old('form_context') === 'loan-create' && $errors->any())">
            @if (old('form_context') === 'loan-create')
                <x-error-summary :errors="$errors->messages()" title="Utang/piutang belum tersimpan. Periksa isian berikut:" />
            @endif
            <form method="POST" action="{{ route('user.loans.store') }}" class="space-y-4" data-dirty-guard>
                @csrf
                <input type="hidden" name="form_context" value="loan-create">
                @include('user.loans.form', ['lockedByRepayments' => false])
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <x-button type="button" variant="secondary" icon="kembali" x-on:click="$dispatch('close')">Batal</x-button>
                    <x-button type="submit" variant="primary" icon="tambah" loading-text="Menyimpan utang/piutang…">Simpan catatan</x-button>
                </div>
            </form>
        </x-bottom-sheet>
        <section class="grid gap-3 sm:grid-cols-2">
            <article class="surface-card p-4"><p class="text-sm text-muted">Total piutang</p><strong class="mt-1 block text-xl" data-private>{{ rupiah($lent) }}</strong></article>
            <article class="surface-card p-4"><p class="text-sm text-muted">Total utang</p><strong class="mt-1 block text-xl" data-private>{{ rupiah($borrowed) }}</strong></article>
        </section>
        <section class="space-y-4">
            @forelse ($loans as $loan)
                <article class="surface-card p-5">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-success-soft text-success" aria-hidden="true">
                                <x-icon name="utang" class="h-5 w-5" />
                            </span>
                            <div>
                                <h2 class="font-semibold">{{ $loan->direction->label() }} · {{ $loan->counterparty }}</h2>
                                <p class="text-sm text-muted">{{ rupiah($loan->amount) }} · tercatat {{ $loan->loan_date->translatedFormat('j M Y') }}@if($loan->due_on) · jatuh tempo {{ $loan->due_on->translatedFormat('j M Y') }}@endif</p>
                                <p class="mt-1 text-sm">Sisa: <strong data-private>{{ rupiah($loan->remaining_amount) }}</strong> · {{ $loan->due_label }}</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-end gap-2">
                            <x-button type="button" variant="secondary" icon="menu-aksi"
                                aria-label="Aksi untuk {{ $loan->counterparty }}"
                                x-data
                                x-on:click="$dispatch('open-modal', 'loan-actions-{{ $loan->id }}')">Aksi</x-button>
                        @if ($status === 'active' && $loan->remaining_amount > 0)
                            <form method="POST" action="{{ route('user.loans.repay', $loan) }}" class="flex flex-wrap items-end gap-2">
                                @csrf
                                <x-money-input id="repay-amount-{{ $loan->id }}" name="amount" label="Nominal" />
                                <div><label class="field-label" for="repay-date-{{ $loan->id }}">Tanggal</label><input id="repay-date-{{ $loan->id }}" name="paid_on" type="date" value="{{ $today }}" required class="form-control"></div>
                                <select name="wallet_id" aria-label="Dompet" class="form-control"><option value="">Tanpa dompet</option>@foreach ($wallets as $wallet)<option value="{{ $wallet->id }}">{{ $wallet->name }}</option>@endforeach</select>
                                <x-button variant="primary">Catat pembayaran</x-button>
                            </form>
                        @elseif ($loan->remaining_amount === 0)
                            <span class="rounded-full bg-primary-50 px-3 py-1 text-sm font-medium text-primary-800">Lunas</span>
                        @endif
                        </div>
                    </div>
                    <x-bottom-sheet name="loan-actions-{{ $loan->id }}" :title="'Aksi utang/piutang: '.$loan->counterparty">
                        <div class="space-y-3">
                            @if ($status === 'active')
                                <x-button href="{{ route('user.loans.edit', $loan) }}" variant="secondary" icon="ubah" class="w-full">Ubah catatan</x-button>
                                <x-confirm-action :action="route('user.loans.archive', $loan)" method="PATCH"
                                    title="Arsipkan catatan?"
                                    :message="'Catatan '.$loan->counterparty.' tidak lagi muncul di agenda. Riwayat dan pembayaran tetap tersimpan.'"
                                    label="Arsipkan" tone="secondary" icon="nonaktifkan" />
                            @else
                                <form method="POST" action="{{ route('user.loans.restore', $loan) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-button variant="secondary" icon="kembali" class="w-full">Pulihkan</x-button>
                                </form>
                                @if ($loan->repayments->isEmpty())
                                    <x-confirm-action :action="route('user.loans.destroy', $loan)"
                                        title="Hapus catatan permanen?"
                                        :message="'Catatan '.$loan->counterparty.' beserta transaksi awal terkait akan dihapus permanen.'"
                                        label="Hapus permanen" />
                                @else
                                    <p class="text-xs text-muted">Catatan memiliki pembayaran dan tidak dapat dihapus.</p>
                                @endif
                            @endif
                        </div>
                    </x-bottom-sheet>
                    @if($loan->reminder_message)<div class="mt-4"><label class="field-label" for="reminder-{{ $loan->id }}">Pesan pengingat siap salin</label><textarea id="reminder-{{ $loan->id }}" readonly rows="2" class="form-control">{{ $loan->reminder_message }}</textarea></div>@endif
                    @foreach ($loan->repayments as $repayment)
                        <div class="mt-2 flex items-center justify-between border-t border-line pt-2 text-sm"><span>Pembayaran {{ $repayment->paid_on->translatedFormat('j M Y') }} · <span data-private>{{ rupiah($repayment->amount) }}</span></span>
                            @if ($status === 'active')
                                <x-confirm-action :action="route('user.loans.repayments.cancel', [$loan, $repayment])"
                                    title="Batalkan pembayaran?"
                                    :message="'Pembayaran '.rupiah($repayment->amount).' pada '.$repayment->paid_on->translatedFormat('j F Y').' akan dihapus dan sisa pinjaman dihitung ulang.'"
                                    label="Batalkan pembayaran" icon="kembali" />
                            @endif
                        </div>
                    @endforeach
                </article>
            @empty
                <x-empty-state
                    class="surface-card"
                    title="Belum ada catatan utang-piutang."
                    description="Catat kesepakatan agar jumlah dan tanggal pembayaran mudah diikuti."
                    :action-url="route('user.loans.index', ['create' => 'loan'])"
                    action-label="Tambah catatan pertama"
                >
                    <x-slot:icon><x-icon name="utang" /></x-slot:icon>
                </x-empty-state>
            @endforelse
        </section>
    </div>
@endsection
