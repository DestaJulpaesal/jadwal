@extends('layouts.user')

@section('title', 'Utang dan piutang')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <header><p class="text-sm font-semibold text-primary-800">Catatan kesepakatan</p><h1 class="mt-1 text-2xl font-semibold">Utang dan piutang</h1></header>
        <section class="grid gap-3 sm:grid-cols-2">
            <article class="surface-card p-4"><p class="text-sm text-muted">Total piutang</p><strong class="mt-1 block text-xl" data-private>{{ rupiah($lent) }}</strong></article>
            <article class="surface-card p-4"><p class="text-sm text-muted">Total utang</p><strong class="mt-1 block text-xl" data-private>{{ rupiah($borrowed) }}</strong></article>
        </section>
        <section class="surface-card p-5">
            <h2 class="text-lg font-semibold">Tambah catatan</h2>
            @if ($errors->any())<x-alert type="danger" class="mt-3"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></x-alert>@endif
            <form method="POST" action="{{ route('user.loans.store') }}" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @csrf
                <div><label for="direction" class="field-label">Jenis</label><select id="direction" name="direction" class="form-control"><option value="borrowed">Utang</option><option value="lent">Piutang</option></select></div>
                <div><label for="counterparty" class="field-label">Nama pihak</label><input id="counterparty" name="counterparty" maxlength="80" required class="form-control"></div>
                <x-money-input id="amount" name="amount" label="Nominal (Rupiah)" :error="$errors->first('amount')" />
                <div><label for="loan_date" class="field-label">Tanggal transaksi</label><input id="loan_date" name="loan_date" type="date" required value="{{ $today }}" class="form-control"></div>
                <div><label for="due_on" class="field-label">Jatuh tempo (opsional)</label><input id="due_on" name="due_on" type="date" class="form-control"></div>
                <div><label for="wallet_id" class="field-label">Dompet (opsional)</label><select id="wallet_id" name="wallet_id" class="form-control"><option value="">Tidak mengubah saldo</option>@foreach ($wallets as $wallet)<option value="{{ $wallet->id }}">{{ $wallet->name }}</option>@endforeach</select></div>
                <div class="sm:col-span-2 lg:col-span-3"><x-button variant="primary">Simpan</x-button></div>
            </form>
        </section>
        <section class="space-y-4">
            @forelse ($loans as $loan)
                <article class="surface-card p-5">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div><h2 class="font-semibold">{{ $loan->direction->label() }} · {{ $loan->counterparty }}</h2><p class="text-sm text-muted">{{ rupiah($loan->amount) }} · tercatat {{ $loan->loan_date->translatedFormat('j M Y') }}@if($loan->due_on) · jatuh tempo {{ $loan->due_on->translatedFormat('j M Y') }}@endif</p><p class="mt-1 text-sm">Sisa: <strong data-private>{{ rupiah($loan->remaining_amount) }}</strong> · {{ $loan->due_label }}</p></div>
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
