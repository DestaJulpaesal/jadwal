@extends('layouts.user')

@section('title', 'Ubah transaksi')

@section('content')
    <div class="mx-auto max-w-3xl">
        <x-button href="{{ route('user.money.index', ['section' => 'transactions']) }}" variant="ghost" icon="kembali" class="mb-3">Kembali</x-button>
        <section class="surface-card p-5 sm:p-8">
            <header class="mb-6">
                <p class="text-sm font-semibold text-primary-800">Catatan keuangan</p>
                <h1 class="mt-1 text-2xl font-semibold">Ubah transaksi</h1>
                <p class="mt-2 text-sm text-muted">Perubahan transaksi akan memperbarui saldo dompet dan laporan.</p>
            </header>
            @include('user.partials.finance-subnav')
            <x-error-summary :errors="$errors->messages()" title="Transaksi belum diperbarui. Periksa isian berikut:" />
            <form method="POST" action="{{ route('user.money.transactions.update', $transaction) }}" class="space-y-5" data-dirty-guard>
                @csrf
                @method('PATCH')
                <x-segmented name="type" label="Jenis transaksi" :options="['expense' => 'Pengeluaran', 'income' => 'Pemasukan', 'transfer' => 'Pindah dana']" :value="$transaction->type->value" required help="Pilih arah uang sebelum mengisi dompet dan kategori." />
                <x-money-input id="transaction_amount" name="amount" label="Nominal" :value="$transaction->amount" :error="$errors->first('amount')" />
                <x-select id="transaction_wallet_id" name="wallet_id" label="Dompet asal" help="Pilih dompet sumber transaksi." required :error="$errors->first('wallet_id')">
                    <option value="">Pilih dompet</option>
                    @foreach ($wallets as $wallet)
                        <option value="{{ $wallet->id }}" @selected(old('wallet_id', $transaction->wallet_id) == $wallet->id)>{{ $wallet->name }}</option>
                    @endforeach
                </x-select>
                <div data-conditional-field data-condition-name="type" data-condition-value="transfer" data-condition-required hidden>
                    <x-select id="transaction_to_wallet_id" name="to_wallet_id" label="Dompet tujuan" help="Pilih dompet penerima untuk memindahkan dana." required :error="$errors->first('to_wallet_id')">
                        <option value="">Pilih dompet tujuan</option>
                        @foreach ($wallets as $wallet)
                            <option value="{{ $wallet->id }}" @selected(old('to_wallet_id', $transaction->to_wallet_id) == $wallet->id)>{{ $wallet->name }}</option>
                        @endforeach
                    </x-select>
                </div>
                <x-datetime-quick id="transaction_date" name="occurred_on" label="Tanggal transaksi" type="date" :value="$transaction->occurred_on->toDateString()" required :error="$errors->first('occurred_on')" help="Tanggal ini menentukan bulan laporan transaksi." />
                <div data-conditional-field data-condition-name="type" data-condition-except="transfer">
                    <x-select id="transaction_category_id" name="money_category_id" label="Kategori" help="Kategori membantu merangkum pemasukan dan pengeluaran." optional data-match-field="type">
                        <option value="">Tanpa kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" data-match-value="{{ $category->type->value }}" @selected(old('money_category_id', $transaction->money_category_id) == $category->id)>{{ $category->type->label() }} · {{ $category->name }}</option>
                        @endforeach
                    </x-select>
                </div>
                <x-textarea id="transaction_note" name="note" label="Catatan" help="Tambahkan keterangan transaksi (opsional)." optional :error="$errors->first('note')" :value="$transaction->note" rows="2" maxlength="255" autocomplete="off" />
                <x-form-actions :cancel-url="route('user.money.index', ['section' => 'transactions', 'month' => $transaction->occurred_on->format('Y-m')])" submit-label="Simpan transaksi" submit-icon="selesai" loading-label="Menyimpan transaksi…" />
            </form>
        </section>
    </div>
@endsection
