@extends('layouts.user')

@section('title', 'Tagihan dan cicilan')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <header><p class="text-sm font-semibold text-primary-800">Pengingat berkala</p><h1 class="mt-1 text-2xl font-semibold">Tagihan dan cicilan</h1><p class="mt-2 text-sm text-muted">Pembayaran hanya dicatat pada periode yang jatuh tempo.</p></header>
        <section class="surface-card p-5">
            <h2 class="text-lg font-semibold">Tambah pengingat</h2>
            @if ($errors->any())<x-alert type="danger" class="mt-3"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></x-alert>@endif
            <form method="POST" action="{{ route('user.obligations.store') }}" class="mt-4 space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div><label for="kind" class="field-label">Jenis</label><select id="kind" name="kind" class="form-control"><option value="bill">Tagihan</option><option value="installment">Cicilan</option></select></div>
                    <div><label for="title" class="field-label">Nama</label><input id="title" name="title" required maxlength="120" class="form-control"></div>
                    <x-money-input id="amount" name="amount" label="Nominal (Rupiah)" :error="$errors->first('amount')" />
                    <div><label for="due_day" class="field-label">Tanggal jatuh tempo (1–31)</label><input id="due_day" name="due_day" type="number" min="1" max="31" required class="form-control"></div>
                </div>
                <details>
                    <summary class="min-h-11 cursor-pointer py-3 font-medium text-primary-800">Pengaturan lanjutan</summary>
                    <div class="grid gap-4 border-t border-line pt-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div><label for="starts_on" class="field-label">Mulai</label><input id="starts_on" name="starts_on" type="date" required value="{{ $today }}" class="form-control"></div>
                        <div><label for="installments_total" class="field-label">Jumlah cicilan</label><input id="installments_total" name="installments_total" type="number" min="1" max="120" class="form-control"></div>
                        <div><label for="remind_days_before" class="field-label">Ingatkan (hari sebelumnya)</label><input id="remind_days_before" name="remind_days_before" type="number" min="0" max="30" value="3" required class="form-control"></div>
                        <div><label for="wallet_id" class="field-label">Dompet pembayaran</label><select id="wallet_id" name="wallet_id" class="form-control"><option value="">Pilih nanti</option>@foreach ($wallets as $wallet)<option value="{{ $wallet->id }}">{{ $wallet->name }}</option>@endforeach</select></div>
                        <div><label for="money_category_id" class="field-label">Kategori pengeluaran</label><select id="money_category_id" name="money_category_id" class="form-control"><option value="">Tanpa kategori</option>@foreach ($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></div>
                        <div class="sm:col-span-2"><label for="note" class="field-label">Catatan</label><input id="note" name="note" maxlength="255" class="form-control"></div>
                    </div>
                </details>
                <button class="btn-primary">Simpan pengingat</button>
            </form>
        </section>
        <section class="space-y-4">
            @forelse ($obligations as $obligation)
                <article class="surface-card p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div><h2 class="font-semibold">{{ $obligation->title }}</h2><p class="text-sm text-muted">{{ $obligation->kind->label() }} · {{ rupiah($obligation->amount) }} · tanggal {{ $obligation->due_day }} setiap bulan</p>
                            @if($obligation->kind->value === 'installment')<p class="mt-1 text-sm text-muted">{{ $obligation->paid_installments }} dari {{ $obligation->installments_total }} · sisa {{ max(0, $obligation->installments_total - $obligation->paid_installments) }}</p>@endif
                        </div>
                        @if ($obligation->next_due)
                            <form method="POST" action="{{ route('user.obligations.pay', $obligation) }}" class="flex flex-wrap items-end gap-2">
                                @csrf
                                <input type="hidden" name="due_on" value="{{ $obligation->next_due->toDateString() }}">
                                <div><label class="field-label" for="paid-{{ $obligation->id }}">Dibayar pada</label><input id="paid-{{ $obligation->id }}" name="paid_on" type="date" value="{{ $today }}" required class="form-control"></div>
                                <x-money-input id="paid-amount-{{ $obligation->id }}" name="amount_paid" label="Nominal" :value="$obligation->amount" />
                                <select name="wallet_id" aria-label="Dompet pembayaran" class="form-control"><option value="">Tanpa dompet</option>@foreach ($wallets as $wallet)<option value="{{ $wallet->id }}" @selected($obligation->wallet_id == $wallet->id)>{{ $wallet->name }}</option>@endforeach</select>
                                <button class="btn-primary">Tandai dibayar</button>
                            </form>
                        @else
                            <span class="text-sm text-muted">{{ $obligation->installments_complete ? 'Semua cicilan sudah dicatat.' : 'Belum ada periode yang jatuh tempo.' }}</span>
                        @endif
                    </div>
                    @if ($obligation->next_due)<p class="mt-2 text-sm text-muted">Bayar periode tertua terlebih dahulu: {{ $obligation->next_due->translatedFormat('j F Y') }}.</p>@endif
                    <ul class="mt-4 divide-y divide-line border-t border-line">
                        @foreach ($obligation->periods as $period)
                            <li class="flex min-h-11 items-center justify-between gap-3 py-2 text-sm">
                                <span>Jatuh tempo {{ $period['date']->translatedFormat('j M Y') }}</span>
                                <span class="text-right {{ str_starts_with($period['status'], 'Terlambat') ? 'font-medium text-danger' : 'text-muted' }}">{{ $period['status'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @if ($obligation->payments->isNotEmpty())<p class="mt-2 text-xs text-muted">{{ $obligation->payments->count() }} pembayaran tercatat.</p>@endif
                    @foreach ($obligation->payments as $payment)
                        <div class="mt-2 flex items-center justify-between border-t border-line pt-2 text-sm"><span>Jatuh tempo {{ $payment->due_on->translatedFormat('j M Y') }} · dibayar {{ rupiah($payment->amount_paid) }}</span>
                            <form method="POST" action="{{ route('user.obligations.payments.cancel', [$obligation, $payment]) }}">
                                @csrf
                                @method('DELETE')
                                <button class="min-h-11 font-medium text-danger underline">Batalkan pembayaran</button>
                            </form>
                        </div>
                    @endforeach
                </article>
            @empty
                <div class="surface-card p-8 text-center text-muted">Belum ada tagihan atau cicilan.</div>
            @endforelse
        </section>
    </div>
@endsection
