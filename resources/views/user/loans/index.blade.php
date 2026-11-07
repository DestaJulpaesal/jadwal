@extends('layouts.user')

@section('title', 'Utang dan piutang')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-sm font-semibold text-primary-800">Catatan kesepakatan</p><h1 class="mt-1 text-2xl font-semibold">Utang dan piutang</h1></div>
            <x-button type="button" variant="primary" icon="tambah" x-data x-on:click="$dispatch('open-modal', 'loan-create')">Tambah catatan</x-button>
        </header>
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
                        <div><h2 class="font-semibold">{{ $loan->direction->label() }} · {{ $loan->counterparty }}</h2><p class="text-sm text-muted">{{ rupiah($loan->amount) }} · tercatat {{ $loan->loan_date->translatedFormat('j M Y') }}@if($loan->due_on) · jatuh tempo {{ $loan->due_on->translatedFormat('j M Y') }}@endif</p><p class="mt-1 text-sm">Sisa: <strong data-private>{{ rupiah($loan->remaining_amount) }}</strong> · {{ $loan->due_label }}</p></div>
                        <x-button href="{{ route('user.loans.edit', $loan) }}" variant="secondary" icon="ubah">Ubah catatan</x-button>
                        @if ($loan->remaining_amount > 0)
                            <form method="POST" action="{{ route('user.loans.repay', $loan) }}" class="flex flex-wrap items-end gap-2">
                                @csrf
                                <x-money-input id="repay-amount-{{ $loan->id }}" name="amount" label="Nominal" />
                                <div><label class="field-label" for="repay-date-{{ $loan->id }}">Tanggal</label><input id="repay-date-{{ $loan->id }}" name="paid_on" type="date" value="{{ $today }}" required class="form-control"></div>
                                <select name="wallet_id" aria-label="Dompet" class="form-control"><option value="">Tanpa dompet</option>@foreach ($wallets as $wallet)<option value="{{ $wallet->id }}">{{ $wallet->name }}</option>@endforeach</select>
                                <x-button variant="primary">Catat pembayaran</x-button>
                            </form>
                        @else <span class="rounded-full bg-primary-50 px-3 py-1 text-sm font-medium text-primary-800">Lunas</span> @endif
                    </div>
                    @if($loan->reminder_message)<div class="mt-4"><label class="field-label" for="reminder-{{ $loan->id }}">Pesan pengingat siap salin</label><textarea id="reminder-{{ $loan->id }}" readonly rows="2" class="form-control">{{ $loan->reminder_message }}</textarea></div>@endif
                    @foreach ($loan->repayments as $repayment)
                        <div class="mt-2 flex items-center justify-between border-t border-line pt-2 text-sm"><span>Pembayaran {{ $repayment->paid_on->translatedFormat('j M Y') }} · <span data-private>{{ rupiah($repayment->amount) }}</span></span>
                            <form method="POST" action="{{ route('user.loans.repayments.cancel', [$loan, $repayment]) }}">
                                @csrf
                                @method('DELETE')
                                <x-button class="min-h-11 font-medium text-danger underline">Batalkan</x-button>
                            </form>
                        </div>
                    @endforeach
                </article>
            @empty
                <div class="surface-card p-8 text-center text-muted">Belum ada catatan utang/piutang.</div>
            @endforelse
        </section>
    </div>
@endsection
