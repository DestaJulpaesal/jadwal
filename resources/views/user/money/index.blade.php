@extends('layouts.user')

@section('title', 'Keuangan')

@section('content')
    <div class="mx-auto max-w-6xl space-y-5">
        <header class="concept-card concept-card-orange page-heading mb-0">
            <div>
                <p class="k">Catatan pribadi</p>
                <h1 class="mt-1 text-3xl">Keuangan</h1>
                <p class="mt-2 text-sm">Pantau saldo dompet dan arus uang bulanan.</p>
            </div>
            <p class="concept-chip">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y') }}</p>
        </header>
        <section class="money-summary-grid" aria-label="Ringkasan keuangan">
            <article class="money-summary-card"><p class="text-sm">Pemasukan</p><p class="display-number mt-1" data-private>{{ rupiah($totals['income']) }}</p></article>
            <article class="money-summary-card"><p class="text-sm">Pengeluaran</p><p class="display-number mt-1" data-private>{{ rupiah($totals['expense']) }}</p></article>
            <article class="money-summary-card"><p class="text-sm">Selisih</p><p class="display-number mt-1" data-private>{{ rupiah($totals['net']) }}</p></article>
        </section>
        <section class="concept-card concept-card-white">
            <div class="concept-card-header">
                <div class="concept-heading mb-0"><x-concept-icon name="wl" /><div><h2 class="text-lg font-semibold">Dompet</h2><p class="text-sm">Saldo saat ini di setiap tempat penyimpanan uang.</p></div></div>
            </div>
            <ul class="concept-list">
                @forelse ($wallets as $wallet)
                    <li class="concept-item"><span class="min-w-0 flex-1">{{ $wallet->name }} <span class="block text-xs">{{ $wallet->type->label() }}</span></span><strong class="shrink-0" data-private>{{ rupiah($wallet->calculated_balance) }}</strong></li>
                @empty
                    <li class="py-4 text-sm text-muted">Belum ada dompet.</li>
                @endforelse
            </ul>
            <form method="POST" action="{{ route('user.money.wallets.store') }}" class="money-form mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
                @csrf
                <div><label class="field-label" for="wallet_name">Nama dompet</label><input id="wallet_name" name="name" required maxlength="60" class="form-control"></div>
                <div><label class="field-label" for="wallet_type">Jenis</label><select id="wallet_type" name="type" class="form-control">@foreach ($walletTypes as $type)<option value="{{ $type->value }}">{{ $type->label() }}</option>@endforeach</select></div>
                <x-money-input id="opening_balance" name="opening_balance" label="Saldo awal (Rupiah)" value="0" />
                <button class="btn-primary">Tambah dompet</button>
            </form>
        </section>
        <div class="money-panel-grid grid gap-5">
            <section class="concept-card concept-card-white">
                <div class="concept-card-header">
                    <div><h2 class="text-lg font-semibold">Transaksi</h2><p class="text-sm">Catatan uang untuk bulan yang dipilih.</p></div>
                    <form method="GET" class="w-full sm:w-auto"><label class="sr-only" for="money_month">Pilih bulan</label><input id="money_month" type="month" name="month" value="{{ $month }}" class="form-control sm:w-48" onchange="this.form.submit()"></form>
                </div>
                <ul class="concept-list">
                    @forelse ($transactions as $transaction)
                        <li class="concept-item items-start">
                            <div class="min-w-0 flex-1"><p class="font-medium">{{ $transaction->moneyCategory?->name ?? $transaction->type->label() }}</p><p class="text-sm">{{ $transaction->occurred_on->translatedFormat('j M Y') }} · {{ $transaction->wallet->name }}@if($transaction->destinationWallet) → {{ $transaction->destinationWallet->name }}@endif</p>@if($transaction->note)<p class="text-sm">{{ $transaction->note }}</p>@endif</div>
                            <strong class="money-transaction-amount shrink-0" data-private>{{ $transaction->type === \App\Enums\TransactionType::Expense ? '−' : '' }}{{ rupiah($transaction->amount) }}</strong>
                        </li>
                    @empty
                        <li class="py-5 text-sm text-muted">Belum ada transaksi pada bulan ini.</li>
                    @endforelse
                </ul>
                <div class="mt-4">{{ $transactions->links() }}</div>
            </section>
            <section class="concept-card concept-card-white">
                <div class="concept-card-header"><div class="concept-heading mb-0"><x-concept-icon name="ml" /><div><h2 class="text-lg font-semibold">Catat transaksi</h2><p class="text-sm">Tambahkan pemasukan, pengeluaran, atau pindah dana.</p></div></div></div>
                @if ($errors->any())<x-alert type="danger" class="mt-3"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></x-alert>@endif
                <form method="POST" action="{{ route('user.money.transactions.store') }}" class="mt-4 space-y-4">
                    @csrf
                    <div><label for="type" class="field-label">Jenis</label><select id="type" name="type" class="form-control"><option value="expense">Pengeluaran</option><option value="income">Pemasukan</option><option value="transfer">Pindah dana</option></select></div>
                    <x-money-input id="amount" name="amount" label="Nominal (Rupiah)" :error="$errors->first('amount')" />
                    <div><label for="wallet_id" class="field-label">Dompet asal</label><select id="wallet_id" name="wallet_id" required class="form-control"><option value="">Pilih dompet</option>@foreach ($wallets as $wallet)<option value="{{ $wallet->id }}">{{ $wallet->name }}</option>@endforeach</select></div>
                    <div><label for="occurred_on" class="field-label">Tanggal</label><input id="occurred_on" name="occurred_on" type="date" required value="{{ $today }}" class="form-control"></div>
                    <details class="border-t border-line pt-3">
                        <summary class="min-h-11 cursor-pointer font-medium text-primary-800">Rincian lainnya</summary>
                        <div class="mt-3 space-y-4">
                            <div><label for="to_wallet_id" class="field-label">Dompet tujuan (untuk pindah dana)</label><select id="to_wallet_id" name="to_wallet_id" class="form-control"><option value="">Tidak ada</option>@foreach ($wallets as $wallet)<option value="{{ $wallet->id }}">{{ $wallet->name }}</option>@endforeach</select></div>
                            <div><label for="money_category_id" class="field-label">Kategori</label><select id="money_category_id" name="money_category_id" class="form-control"><option value="">Tanpa kategori</option>@foreach ($categories as $category)<option value="{{ $category->id }}">{{ $category->type->label() }} · {{ $category->name }}</option>@endforeach</select></div>
                            <div><label for="note" class="field-label">Catatan</label><input id="note" name="note" maxlength="255" class="form-control"></div>
                        </div>
                    </details>
                    <button class="btn-primary w-full">Simpan transaksi</button>
                </form>
                <details class="mt-5 border-t border-line pt-4"><summary class="cursor-pointer font-medium">Tambah kategori</summary>
                    <form method="POST" action="{{ route('user.money.categories.store') }}" class="mt-3 space-y-3">
                        @csrf
                        <div><label for="category_name" class="field-label">Nama kategori</label><input id="category_name" name="name" maxlength="60" required class="form-control"></div>
                        <div><label for="category_type" class="field-label">Jenis</label><select id="category_type" name="type" class="form-control"><option value="expense">Pengeluaran</option><option value="income">Pemasukan</option></select></div>
                        <button class="btn-secondary">Simpan kategori</button>
                    </form>
                </details>
            </section>
        </div>
    </div>
@endsection
