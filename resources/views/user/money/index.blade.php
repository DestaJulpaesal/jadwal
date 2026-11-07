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
        <x-tabs :tabs="[
            ['key' => 'overview', 'label' => 'Ringkasan', 'url' => route('user.money.index', ['section' => 'overview', 'month' => $month])],
            ['key' => 'transactions', 'label' => 'Transaksi', 'url' => route('user.money.index', ['section' => 'transactions', 'month' => $month])],
            ['key' => 'wallets', 'label' => 'Dompet & kategori', 'url' => route('user.money.index', ['section' => 'wallets', 'month' => $month])],
            ['key' => 'obligations', 'label' => 'Tagihan', 'url' => route('user.obligations.index', ['month' => $month])],
            ['key' => 'loans', 'label' => 'Utang-piutang', 'url' => route('user.loans.index', ['month' => $month])],
            ['key' => 'reports', 'label' => 'Laporan', 'url' => route('user.reports.index', ['month' => $month])],
        ]" :active="request()->routeIs('user.obligations.*') ? 'obligations' : (request()->routeIs('user.loans.*') ? 'loans' : (request()->routeIs('user.reports.*') ? 'reports' : $section))" />
        @if (in_array($section, ['overview', 'reports'], true))
        <section class="money-summary-grid" aria-label="Ringkasan keuangan">
            <article class="money-summary-card"><p class="text-sm">Pemasukan</p><p class="display-number mt-1" data-private>{{ rupiah($totals['income']) }}</p></article>
            <article class="money-summary-card"><p class="text-sm">Pengeluaran</p><p class="display-number mt-1" data-private>{{ rupiah($totals['expense']) }}</p></article>
            <article class="money-summary-card"><p class="text-sm">Selisih</p><p class="display-number mt-1" data-private>{{ rupiah($totals['net']) }}</p></article>
        </section>
        @endif
        @if ($section === 'overview')
        <section class="concept-card concept-card-white">
            <div class="concept-card-header">
                <div><h2 class="text-lg font-semibold">Rencana pengeluaran</h2><p class="text-sm">Tagihan dan cicilan yang belum dibayar pada bulan ini.</p></div>
                <strong class="text-lg" data-private>{{ rupiah($plannedTotal) }}</strong>
            </div>
            <ul class="concept-list">
                @forelse ($plannedObligations as $obligation)
                    <li class="concept-item">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium">{{ $obligation->title }}</p>
                            <p class="text-sm">{{ $obligation->kind->label() }} · jatuh tempo {{ $obligation->planned_due_on->translatedFormat('j M Y') }}</p>
                        </div>
                        <strong class="shrink-0" data-private>{{ rupiah($obligation->amount) }}</strong>
                    </li>
                @empty
                    <li class="py-4 text-sm text-muted">Tidak ada tagihan atau cicilan yang belum dibayar pada bulan ini.</li>
                @endforelse
            </ul>
        </section>
        <section class="concept-card concept-card-white">
            <div class="concept-card-header">
                <div><h2 class="text-lg font-semibold">Rencana utang dan piutang</h2><p class="text-sm">Sisa pembayaran atau penerimaan yang jatuh tempo pada bulan ini.</p></div>
                <div class="text-right text-sm">
                    <p class="text-success">Piutang masuk: <strong data-private>{{ rupiah($plannedLoanIncome) }}</strong></p>
                    <p class="text-danger">Utang keluar: <strong data-private>{{ rupiah($plannedLoanExpense) }}</strong></p>
                </div>
            </div>
            <ul class="concept-list">
                @forelse ($plannedLoans as $loan)
                    <li class="concept-item">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium">{{ $loan->direction->label() }} · {{ $loan->counterparty }}</p>
                            <p class="text-sm">Jatuh tempo {{ $loan->due_on->translatedFormat('j M Y') }}</p>
                        </div>
                        <strong class="shrink-0 {{ $loan->direction === \App\Enums\LoanDirection::Lent ? 'text-success' : 'text-danger' }}" data-private>
                            {{ $loan->direction === \App\Enums\LoanDirection::Lent ? '+' : '−' }}{{ rupiah($loan->planned_amount) }}
                        </strong>
                    </li>
                @empty
                    <li class="py-4 text-sm text-muted">Tidak ada utang atau piutang jatuh tempo pada bulan ini.</li>
                @endforelse
            </ul>
        </section>
        @endif
        @if ($section === 'reports')
        <section class="concept-card concept-card-white">
            <div class="concept-card-header">
                <div class="concept-heading mb-0"><x-concept-icon name="rc" /><div><h2 class="text-lg font-semibold">Laporan bulanan</h2><p class="text-sm">Bandingkan pengeluaran dan lihat kategori terbesar.</p></div></div>
            </div>
            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                <div>
                    <h3 class="font-semibold">Dibanding bulan lalu</h3>
                    <p class="mt-2 text-sm text-muted">Pengeluaran bulan {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $previousMonth)->translatedFormat('F') }}: <strong data-private>{{ rupiah($previous['expense']) }}</strong></p>
                    @if ($comparison === null)
                        <p class="mt-1 text-sm text-muted">Belum ada data pengeluaran bulan lalu untuk dibandingkan.</p>
                    @elseif ($comparison === 0)
                        <p class="mt-1 text-sm">Pengeluaran sama seperti bulan lalu.</p>
                    @else
                        <p class="mt-1 text-sm">Pengeluaran {{ abs($comparison) }}% {{ $comparison > 0 ? 'lebih tinggi' : 'lebih rendah' }} dari bulan lalu.</p>
                    @endif
                </div>
                <div>
                    <h3 class="font-semibold">Kategori pengeluaran terbesar</h3>
                    @forelse ($reportCategories as $category)
                        <div class="mt-3">
                            <div class="mb-1 flex justify-between gap-3 text-sm"><span>{{ $category->category_name }}</span><strong data-private>{{ rupiah((int) $category->total) }}</strong></div>
                            <div class="h-2 overflow-hidden rounded-full bg-gray-100" role="progressbar" aria-label="Pengeluaran {{ $category->category_name }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $largest ? (int) round($category->total * 100 / $largest) : 0 }}">
                                <div class="h-full rounded-full bg-primary-700" style="width: {{ $largest ? (int) round($category->total * 100 / $largest) : 0 }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="mt-3 text-sm text-muted">Belum ada pengeluaran berkategori pada bulan ini.</p>
                    @endforelse
                </div>
            </div>
        </section>
        @endif
        @if ($section === 'wallets')
        <section class="concept-card concept-card-white">
            <div class="concept-card-header">
                <div class="concept-heading mb-0"><x-concept-icon name="wl" /><div><h2 class="text-lg font-semibold">Dompet</h2><p class="text-sm">Saldo saat ini di setiap tempat penyimpanan uang.</p></div></div>
            </div>
            <ul class="concept-list">
                @forelse ($wallets as $wallet)
                    <li class="concept-item flex-wrap">
                        <span class="min-w-0 flex-1">{{ $wallet->name }} <span class="block text-xs">{{ $wallet->type->label() }}</span></span>
                        <strong class="shrink-0" data-private>{{ rupiah($wallet->calculated_balance) }}</strong>
                        <div class="flex w-full flex-wrap gap-2 sm:w-auto">
                            <x-button type="button" size="sm" variant="secondary" icon="ubah" x-data x-on:click="$dispatch('open-modal', 'wallet-edit-{{ $wallet->id }}')">Ubah nama</x-button>
                            <x-button type="button" size="sm" variant="danger" icon="nonaktifkan" x-data x-on:click="$dispatch('open-modal', 'wallet-archive-{{ $wallet->id }}')">Arsipkan</x-button>
                        </div>
                        <x-bottom-sheet name="wallet-edit-{{ $wallet->id }}" title="Ubah nama dompet" :show="old('form_context') === 'wallet-edit-'.$wallet->id && $errors->any()">
                            @if (old('form_context') === 'wallet-edit-'.$wallet->id)
                                <x-error-summary :errors="$errors->messages()" title="Nama dompet belum diperbarui. Periksa isian berikut:" />
                            @endif
                            <form method="POST" action="{{ route('user.money.wallets.update', $wallet) }}" class="space-y-4" data-dirty-guard>
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="form_context" value="wallet-edit-{{ $wallet->id }}">
                                <x-field id="wallet-name-{{ $wallet->id }}" label="Nama dompet" help="Ubah nama agar dompet mudah dikenali." required :error="$errors->first('name')">
                                    <input id="wallet-name-{{ $wallet->id }}" name="name" value="{{ old('name', $wallet->name) }}" maxlength="60" required autocapitalize="words" class="form-control">
                                </x-field>
                                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                    <x-button type="button" variant="secondary" icon="kembali" x-on:click="$dispatch('close')">Batal</x-button>
                                    <x-button type="submit" variant="primary" icon="ubah" loading-text="Menyimpan nama dompet…">Simpan nama</x-button>
                                </div>
                            </form>
                        </x-bottom-sheet>
                        <x-modal name="wallet-archive-{{ $wallet->id }}" focusable>
                            <div class="p-5 sm:p-6">
                                <h2 class="text-lg font-semibold">Arsipkan dompet {{ $wallet->name }}?</h2>
                                <p class="mt-2 text-sm text-muted">Dompet tidak lagi muncul pada pilihan transaksi baru. Riwayat dan saldonya tetap tersimpan.</p>
                                <form method="POST" action="{{ route('user.money.wallets.archive', $wallet) }}" class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                    @csrf
                                    @method('PATCH')
                                    <x-button type="button" variant="secondary" icon="kembali" x-on:click="$dispatch('close')">Batal</x-button>
                                    <x-button type="submit" variant="danger" icon="nonaktifkan" loading-text="Mengarsipkan dompet…">Arsipkan dompet</x-button>
                                </form>
                            </div>
                        </x-modal>
                    </li>
                @empty
                    <li class="py-4 text-sm text-muted">Belum ada dompet.</li>
                @endforelse
            </ul>
            <x-button type="button" class="mt-4" variant="primary" icon="tambah" x-data x-on:click="$dispatch('open-modal', 'wallet-create')">Tambah dompet</x-button>
        </section>
        <section class="concept-card concept-card-white">
            <div class="concept-card-header">
                <div><h2 class="text-lg font-semibold">Kategori uang</h2><p class="text-sm">Kelompokkan pemasukan dan pengeluaran.</p></div>
                <x-button type="button" variant="secondary" icon="tambah" x-data x-on:click="$dispatch('open-modal', 'money-category-create')">Tambah kategori</x-button>
            </div>
            <ul class="concept-list">
                @forelse ($categories as $category)
                    <li class="concept-item flex-wrap">
                        <span class="min-w-0 flex-1">{{ $category->name }}</span>
                        <x-badge>{{ $category->type->label() }}</x-badge>
                        <x-button type="button" size="sm" variant="secondary" icon="ubah" x-data x-on:click="$dispatch('open-modal', 'money-category-edit-{{ $category->id }}')">Ubah kategori</x-button>
                        <x-bottom-sheet name="money-category-edit-{{ $category->id }}" title="Ubah kategori" :show="old('form_context') === 'money-category-edit-'.$category->id && $errors->any()">
                            @if (old('form_context') === 'money-category-edit-'.$category->id)
                                <x-error-summary :errors="$errors->messages()" title="Kategori belum diperbarui. Periksa isian berikut:" />
                            @endif
                            <form method="POST" action="{{ route('user.money.categories.update', $category) }}" class="space-y-4" data-dirty-guard>
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="form_context" value="money-category-edit-{{ $category->id }}">
                                <x-field id="money-category-name-{{ $category->id }}" label="Nama kategori" help="Jenis kategori tetap {{ $category->type->label() }}." required :error="$errors->first('name')">
                                    <input id="money-category-name-{{ $category->id }}" name="name" value="{{ old('name', $category->name) }}" maxlength="60" required autocapitalize="words" class="form-control">
                                </x-field>
                                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                    <x-button type="button" variant="secondary" icon="kembali" x-on:click="$dispatch('close')">Batal</x-button>
                                    <x-button type="submit" variant="primary" icon="ubah" loading-text="Menyimpan kategori…">Simpan kategori</x-button>
                                </div>
                            </form>
                        </x-bottom-sheet>
                    </li>
                @empty
                    <li class="py-4 text-sm text-muted">Belum ada kategori khusus.</li>
                @endforelse
            </ul>
        </section>
        @endif
        @if ($section === 'transactions')
        <div class="money-panel-grid grid gap-5">
            <section class="concept-card concept-card-white">
                <div class="concept-card-header">
                    <div><h2 class="text-lg font-semibold">Transaksi</h2><p class="text-sm">Catatan uang untuk bulan yang dipilih.</p></div>
                    <form method="GET" action="{{ route('user.money.index') }}" class="flex w-full items-end gap-2 sm:w-auto" data-auto-submit>
                        <input type="hidden" name="section" value="transactions">
                        <div><label class="sr-only" for="money_month">Pilih bulan</label><input id="money_month" type="month" name="month" value="{{ $month }}" class="form-control sm:w-48"></div>
                        <x-button type="submit" data-submit-default variant="secondary" icon="filter">Terapkan</x-button>
                    </form>
                </div>
                <x-button type="button" class="mt-4" variant="primary" icon="tambah" x-data x-on:click="$dispatch('open-modal', 'transaction-create')">Catat transaksi</x-button>
                <ul class="concept-list">
                    @forelse ($transactions as $transaction)
                        <li class="concept-item items-start">
                            <div class="min-w-0 flex-1"><p class="font-medium">{{ $transaction->moneyCategory?->name ?? $transaction->type->label() }}</p><p class="text-sm">{{ $transaction->occurred_on->translatedFormat('j M Y') }} · {{ $transaction->wallet->name }}@if($transaction->destinationWallet) → {{ $transaction->destinationWallet->name }}@endif</p>@if($transaction->note)<p class="text-sm">{{ $transaction->note }}</p>@endif</div>
                            <strong class="money-transaction-amount shrink-0" data-private>{{ $transaction->type === \App\Enums\TransactionType::Expense ? '−' : '' }}{{ rupiah($transaction->amount) }}</strong>
                            @if ($transaction->source_type === null && ! $transaction->wallet?->archived_at && ! $transaction->destinationWallet?->archived_at && ! $transaction->moneyCategory?->archived_at)
                                <x-button href="{{ route('user.money.transactions.edit', $transaction) }}" size="sm" variant="secondary" icon="ubah">Ubah transaksi</x-button>
                            @endif
                        </li>
                    @empty
                        <li class="list-none">
                            <x-empty-state
                                title="Belum ada transaksi pada bulan ini."
                                description="Catat pemasukan, pengeluaran, atau transfer untuk mulai melihat arus uang."
                                :action-url="route('user.money.index', ['section' => 'transactions', 'create' => 'transaction'])"
                                action-label="Catat transaksi pertama"
                            >
                                <x-slot:icon><x-icon name="keuangan" /></x-slot:icon>
                            </x-empty-state>
                        </li>
                    @endforelse
                </ul>
                <div class="mt-4">{{ $transactions->links() }}</div>
            </section>
        </div>
        @endif
        @if ($section === 'wallets')
            <x-bottom-sheet name="wallet-create" title="Tambah dompet" :show="old('form_context') === 'wallet-create' && $errors->any()">
                @if (old('form_context') === 'wallet-create')
                    <x-error-summary :errors="$errors->messages()" title="Dompet belum tersimpan. Periksa isian berikut:" />
                @endif
                <form method="POST" action="{{ route('user.money.wallets.store') }}" class="space-y-4" data-dirty-guard>
                    @csrf
                    <input type="hidden" name="form_context" value="wallet-create">
                    <x-field id="wallet_name" label="Nama dompet" help="Gunakan nama yang mudah dikenali, misalnya Tunai atau Bank." required :error="$errors->first('name')">
                        <input id="wallet_name" name="name" value="{{ old('name') }}" required maxlength="60" autocapitalize="words" data-validate-on-blur class="form-control">
                    </x-field>
                    <x-segmented name="type" label="Jenis dompet"
                        :options="collect($walletTypes)->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all()"
                        :value="old('type', $walletTypes[0]->value)" help="Pilih tempat penyimpanan uang ini." required
                        :error="$errors->first('type')" />
                    <x-money-input id="opening_balance" name="opening_balance" label="Saldo awal" :value="old('opening_balance', '0')" :error="$errors->first('opening_balance')" />
                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <x-button type="button" variant="secondary" icon="kembali" x-on:click="$dispatch('close')">Batal</x-button>
                        <x-button type="submit" variant="primary" icon="tambah" loading-text="Menyimpan dompet…">Simpan dompet</x-button>
                    </div>
                </form>
            </x-bottom-sheet>
            <x-bottom-sheet name="money-category-create" title="Tambah kategori" :show="old('form_context') === 'money-category-create' && $errors->any()">
                @if (old('form_context') === 'money-category-create')
                    <x-error-summary :errors="$errors->messages()" title="Kategori belum tersimpan. Periksa isian berikut:" />
                @endif
                <form method="POST" action="{{ route('user.money.categories.store') }}" class="space-y-4" data-dirty-guard>
                    @csrf
                    <input type="hidden" name="form_context" value="money-category-create">
                    <x-field id="category_name" label="Nama kategori" help="Pilih nama singkat yang mudah dikenali." required :error="$errors->first('name')">
                        <input id="category_name" name="name" value="{{ old('name') }}" maxlength="60" required autocapitalize="words" data-validate-on-blur class="form-control">
                    </x-field>
                    <x-segmented name="type" label="Jenis kategori" :options="['expense' => 'Pengeluaran', 'income' => 'Pemasukan']" :value="old('type', 'expense')" help="Pilih apakah kategori digunakan untuk uang keluar atau masuk." />
                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <x-button type="button" variant="secondary" icon="kembali" x-on:click="$dispatch('close')">Batal</x-button>
                        <x-button type="submit" variant="primary" icon="tambah" loading-text="Menyimpan kategori…">Simpan kategori</x-button>
                    </div>
                </form>
            </x-bottom-sheet>
        @elseif ($section === 'transactions')
            <x-bottom-sheet name="transaction-create" title="Catat transaksi" :show="request('create') === 'transaction' || (old('form_context') === 'transaction-create' && $errors->any())">
                @if (old('form_context') === 'transaction-create')
                    <x-error-summary :errors="$errors->messages()" title="Transaksi belum tersimpan. Periksa isian berikut:" />
                @endif
                <form method="POST" action="{{ route('user.money.transactions.store') }}" class="space-y-4" data-dirty-guard>
                    @csrf
                    <input type="hidden" name="form_context" value="transaction-create">
                    <x-segmented name="type" label="Jenis transaksi" :options="['expense' => 'Pengeluaran', 'income' => 'Pemasukan', 'transfer' => 'Pindah dana']" :value="old('type', 'expense')" required help="Pilih arah uang sebelum mengisi dompet dan kategori." />
                    <x-money-input id="transaction_amount" name="amount" label="Nominal" :error="$errors->first('amount')" />
                    <x-select id="transaction_wallet_id" name="wallet_id" label="Dompet asal" help="Pilih dompet yang menjadi sumber transaksi." required :error="$errors->first('wallet_id')">
                        <option value="">Pilih dompet</option>
                        @foreach ($wallets as $wallet)
                            <option value="{{ $wallet->id }}" @selected(old('wallet_id') == $wallet->id)>{{ $wallet->name }}</option>
                        @endforeach
                    </x-select>
                    <div data-conditional-field data-condition-name="type" data-condition-value="transfer" data-condition-required hidden>
                        <x-select id="transaction_to_wallet_id" name="to_wallet_id" label="Dompet tujuan" help="Pilih dompet penerima untuk memindahkan dana." required :error="$errors->first('to_wallet_id')">
                            <option value="">Pilih dompet tujuan</option>
                            @foreach ($wallets as $wallet)
                                <option value="{{ $wallet->id }}" @selected(old('to_wallet_id') == $wallet->id)>{{ $wallet->name }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    <x-datetime-quick id="transaction_date" name="occurred_on" label="Tanggal transaksi" type="date" :value="old('occurred_on', $today)" :error="$errors->first('occurred_on')" required help="Tanggal ini dipakai untuk laporan bulanan." />
                    <div data-conditional-field data-condition-name="type" data-condition-except="transfer">
                        <x-select id="transaction_category_id" name="money_category_id" label="Kategori" help="Kategori membantu merangkum pemasukan dan pengeluaran." optional data-match-field="type">
                            <option value="">Tanpa kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" data-match-value="{{ $category->type->value }}" @selected(old('money_category_id') == $category->id)>{{ $category->type->label() }} · {{ $category->name }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    <x-textarea id="transaction_note" name="note" label="Catatan" help="Tambahkan keterangan transaksi (opsional)." optional :error="$errors->first('note')" :value="old('note')" rows="2" maxlength="255" autocomplete="off" />
                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <x-button type="button" variant="secondary" icon="kembali" x-on:click="$dispatch('close')">Batal</x-button>
                        <x-button type="submit" variant="primary" icon="selesai" loading-text="Menyimpan transaksi…">Simpan transaksi</x-button>
                    </div>
                </form>
            </x-bottom-sheet>
        @endif
    </div>
@endsection
