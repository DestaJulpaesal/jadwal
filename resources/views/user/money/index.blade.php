@extends('layouts.user')

@section('title', 'Keuangan')

@section('content')
    <div class="mx-auto max-w-6xl space-y-5">
        <x-page-header eyebrow="Catatan pribadi" title="Keuangan" description="Pantau saldo dompet dan arus uang bulanan.">
            <p class="concept-chip">{{ $monthLabel }}</p>
        </x-page-header>
        @include('user.partials.finance-subnav')
        @if ($negativeWallets->isNotEmpty())
            <x-alert type="warning">
                <p class="font-semibold">{{ __('ui.money.negative_wallets_title') }}</p>
                <ul class="mt-2 list-inside list-disc">
                    @foreach ($negativeWallets as $wallet)
                        <li>{{ $wallet->name }}: <strong data-private>{{ rupiah($wallet->calculated_balance) }}</strong></li>
                    @endforeach
                </ul>
                <p class="mt-2 text-sm">{{ __('ui.money.negative_wallets_help') }}</p>
            </x-alert>
        @endif
        @if (in_array($section, ['overview', 'reports'], true))
        <section class="money-summary-grid" aria-label="Ringkasan keuangan">
            <article class="money-summary-card"><p class="text-sm">Pemasukan</p><p class="display-number mt-1" data-private>{{ rupiah($totals['income']) }}</p></article>
            <article class="money-summary-card"><p class="text-sm">Pengeluaran</p><p class="display-number mt-1" data-private>{{ rupiah($totals['expense']) }}</p></article>
            <article class="money-summary-card"><p class="text-sm">Selisih</p><p class="display-number mt-1" data-private>{{ rupiah($totals['net']) }}</p></article>
        </section>
        <p class="text-sm text-muted">{{ __('ui.money.monthly_net_help') }}</p>
        @endif
        @if ($section === 'overview')
        @include('user.money.budgets')
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
                    <li class="py-4 text-sm text-muted">Tidak ada tagihan atau cicilan yang belum dibayar pada bulan ini. <a class="font-semibold underline" href="{{ route('user.obligations.index', ['create' => 'obligation']) }}">Tambah pengingat</a></li>
                @endforelse
            </ul>
        </section>
        <section class="concept-card concept-card-white">
            <div class="concept-card-header">
                <div><h2 class="text-lg font-semibold">Rencana utang dan piutang</h2><p class="text-sm">Sisa pembayaran atau penerimaan yang jatuh tempo bulan ini atau masih terlambat.</p></div>
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
                            <p class="text-sm">
                                @if ($loan->planned_status === 'overdue')
                                    <span class="font-semibold text-danger">Terlambat</span> ·
                                @endif
                                {{ __('ui.agenda.due') }} {{ $loan->due_on->translatedFormat('j M Y') }}
                            </p>
                        </div>
                        <strong class="shrink-0 {{ $loan->direction === \App\Enums\LoanDirection::Lent ? 'text-success' : 'text-danger' }}" data-private>
                            {{ $loan->direction === \App\Enums\LoanDirection::Lent ? '+' : '−' }}{{ rupiah($loan->planned_amount) }}
                        </strong>
                    </li>
                @empty
                    <li class="py-4 text-sm text-muted">Tidak ada utang atau piutang yang jatuh tempo atau terlambat. <a class="font-semibold underline" href="{{ route('user.loans.index', ['create' => 'loan']) }}">Tambah catatan</a></li>
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
                    <p class="mt-2 text-sm text-muted">Pengeluaran bulan {{ $previousMonthLabel }}: <strong data-private>{{ rupiah($previous['expense']) }}</strong></p>
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
                        <x-button href="{{ route('user.money.index', ['section' => 'transactions', 'month' => $month, 'create' => 'transaction']) }}" variant="secondary" icon="tambah" class="mt-3">Catat pengeluaran pertama</x-button>
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
            <form method="GET" action="{{ route('user.money.index') }}" class="mt-4 surface-card p-4" data-auto-submit>
                <input type="hidden" name="section" value="wallets">
                <x-segmented name="status" :label="__('ui.entity_status.active').' / '.__('ui.entity_status.archived')" :options="$statusOptions" :value="$status" />
                <x-button type="submit" class="sr-only" data-submit-default>{{ __('ui.habit.list_submit') }}</x-button>
            </form>
            <ul class="concept-list">
                @forelse ($wallets as $wallet)
                    <li class="grid gap-3 rounded-xl border border-line bg-surface p-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-info-soft text-info" aria-hidden="true">
                                <x-icon name="keuangan" class="h-5 w-5" />
                            </span>
                            <span class="min-w-0 flex-1 font-semibold">{{ $wallet->name }} <span class="block text-xs font-normal text-muted">{{ $wallet->type->label() }}</span></span>
                        </div>
                        <strong class="shrink-0 text-right tabular-nums" data-private>{{ rupiah($wallet->calculated_balance) }}</strong>
                        <div class="flex w-full flex-wrap gap-2 sm:col-span-2">
                            @if ($status === 'archived')
                                <form method="POST" action="{{ route('user.money.wallets.restore', $wallet) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-button size="sm" variant="secondary" icon="kembali">Pulihkan</x-button>
                                </form>
                                @if ($wallet->transactions_count + $wallet->destination_transactions_count === 0)
                                    <x-confirm-action :action="route('user.money.wallets.destroy', $wallet)"
                                        title="Hapus dompet permanen?"
                                        :message="'Dompet '.$wallet->name.' dan saldo awalnya akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.'"
                                        label="Hapus permanen" />
                                @else
                                    <p class="w-full text-xs text-muted">Dompet memiliki transaksi dan tidak dapat dihapus. Riwayat dan saldo tetap tersimpan.</p>
                                @endif
                                @if ($wallet->calculated_balance !== 0)
                                    <p class="w-full text-xs font-medium text-warning-800">Saldo arsip {{ rupiah($wallet->calculated_balance) }} tidak masuk total saldo aktif.</p>
                                @endif
                            @else
                                <x-button type="button" size="sm" variant="secondary" icon="ubah" x-data x-on:click="$dispatch('open-modal', 'wallet-adjust-{{ $wallet->id }}')">{{ __('ui.wallet.adjustment.action') }}</x-button>
                                <x-button type="button" size="sm" variant="secondary" icon="ubah" x-data x-on:click="$dispatch('open-modal', 'wallet-edit-{{ $wallet->id }}')">Ubah nama</x-button>
                                <x-confirm-action :action="route('user.money.wallets.archive', $wallet)" method="PATCH"
                                    title="Arsipkan dompet?"
                                    :message="'Dompet '.$wallet->name.' tidak lagi tersedia untuk transaksi baru. Riwayat dan saldo tetap tersimpan.'"
                                    label="Arsipkan dompet" tone="secondary" icon="nonaktifkan" />
                            @endif
                        </div>
                        @if ($status !== 'archived')
                            <x-bottom-sheet name="wallet-adjust-{{ $wallet->id }}"
                                :title="__('ui.wallet.adjustment.title', ['wallet' => $wallet->name])"
                                :show="old('form_context') === 'wallet-adjust-'.$wallet->id && $errors->any()">
                                @if (old('form_context') === 'wallet-adjust-'.$wallet->id)
                                    <x-error-summary :errors="$errors->messages()" :title="__('ui.wallet.adjustment.error_summary')" />
                                @endif
                                <form method="POST" action="{{ route('user.money.wallets.adjustments.store', $wallet) }}" class="space-y-4" data-dirty-guard>
                                    @csrf
                                    <input type="hidden" name="form_context" value="wallet-adjust-{{ $wallet->id }}">
                                    <p class="text-sm text-muted">{{ __('ui.wallet.adjustment.current_balance') }} <strong data-private>{{ rupiah($wallet->calculated_balance) }}</strong></p>
                                    <x-field id="wallet-actual-balance-{{ $wallet->id }}"
                                        :label="__('ui.wallet.adjustment.actual_balance')"
                                        :help="__('ui.wallet.adjustment.help')"
                                        :error="$errors->first('actual_balance')"
                                        required>
                                        <input id="wallet-actual-balance-{{ $wallet->id }}"
                                            name="actual_balance"
                                            type="number"
                                            inputmode="numeric"
                                            step="1"
                                            min="-{{ \App\Support\Rupiah::MAX_AMOUNT }}"
                                            max="{{ \App\Support\Rupiah::MAX_AMOUNT }}"
                                            value="{{ old('actual_balance', $wallet->calculated_balance) }}"
                                            required
                                            class="form-control">
                                    </x-field>
                                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                        <x-button type="button" variant="secondary" icon="kembali" x-on:click="$dispatch('close')">{{ __('ui.wallet.adjustment.cancel') }}</x-button>
                                        <x-button type="submit" variant="primary" icon="ubah" loading-text="{{ __('ui.wallet.adjustment.saving') }}">{{ __('ui.wallet.adjustment.submit') }}</x-button>
                                    </div>
                                </form>
                            </x-bottom-sheet>
                        @endif
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
                    </li>
                @empty
                    <li class="list-none">
                        <x-empty-state title="Belum ada dompet." description="Tambahkan dompet supaya saldo dan catatan uang bisa dikelompokkan.">
                            <x-slot:icon><x-icon name="keuangan" class="h-6 w-6" /></x-slot:icon>
                            <x-button type="button" variant="primary" icon="tambah" x-data x-on:click="$dispatch('open-modal', 'wallet-create')">Tambah dompet pertama</x-button>
                        </x-empty-state>
                    </li>
                @endforelse
            </ul>
            @if ($wallets->isNotEmpty())
                <x-button type="button" class="mt-4" variant="primary" icon="tambah" x-data x-on:click="$dispatch('open-modal', 'wallet-create')">Tambah dompet</x-button>
            @endif
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
                        @if ($status === 'archived')
                            <form method="POST" action="{{ route('user.money.categories.restore', $category) }}">
                                @csrf
                                @method('PATCH')
                                <x-button size="sm" variant="secondary" icon="kembali">Pulihkan</x-button>
                            </form>
                            @if ($category->transactions_count + $category->obligations_count + $category->budgets_count === 0)
                                <x-confirm-action :action="route('user.money.categories.destroy', $category)"
                                    title="Hapus kategori permanen?"
                                    :message="'Kategori '.$category->name.' akan dihapus permanen.'"
                                    label="Hapus permanen" />
                            @else
                                <p class="w-full text-xs text-muted">Kategori masih digunakan oleh transaksi, tagihan, atau anggaran dan tidak dapat dihapus. Arsipnya tidak mengubah riwayat.</p>
                            @endif
                        @else
                            <x-confirm-action :action="route('user.money.categories.archive', $category)" method="PATCH"
                                title="Arsipkan kategori?"
                                :message="'Kategori '.$category->name.' tidak lagi muncul untuk catatan baru. Riwayat transaksi tetap tersimpan.'"
                                label="Arsipkan" tone="secondary" icon="nonaktifkan" />
                        @endif
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
                    <li class="list-none">
                        <x-empty-state title="Belum ada kategori khusus." description="Kategori membantu membaca pola pemasukan dan pengeluaranmu.">
                            <x-slot:icon><x-icon name="filter" class="h-6 w-6" /></x-slot:icon>
                            <x-button type="button" variant="primary" icon="tambah" x-data x-on:click="$dispatch('open-modal', 'money-category-create')">Tambah kategori pertama</x-button>
                        </x-empty-state>
                    </li>
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
                <ul class="grid gap-4">
                    @if ($transactions->isEmpty())
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
                    @else
                        @foreach ($transactions->getCollection()->groupBy(fn ($transaction) => $transaction->occurred_on->toDateString()) as $dayTransactions)
                            @php
                                $day = $dayTransactions->first()->occurred_on;
                            @endphp
                            <li class="list-none">
                                <h3 class="mb-2 font-semibold">{{ $day->translatedFormat('l, j F Y') }}</h3>
                                <ul class="grid gap-2">
                                    @foreach ($dayTransactions as $transaction)
                                        @php
                                            $transactionTitle = $transaction->source_type === 'adjustment'
                                                ? __('ui.money.adjustment_label')
                                                : ($transaction->moneyCategory?->name ?? $transaction->type->label());
                                            $iconColor = match ($transaction->type) {
                                                \App\Enums\TransactionType::Expense => 'bg-danger-soft text-danger',
                                                \App\Enums\TransactionType::Transfer => 'bg-info-soft text-info',
                                                default => 'bg-success-soft text-success',
                                            };
                                        @endphp
                                        <li class="grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3 rounded-xl border border-line bg-white p-3 sm:p-4">
                                            <span class="flex h-11 w-11 items-center justify-center rounded-full {{ $iconColor }}" aria-hidden="true">
                                                <x-icon name="keuangan" class="h-5 w-5" />
                                            </span>
                                            <div class="min-w-0">
                                                <p class="break-words font-semibold">{{ $transactionTitle }}</p>
                                                <p class="mt-1 text-sm text-muted">
                                                    {{ $transaction->wallet->name }}@if($transaction->destinationWallet) → {{ $transaction->destinationWallet->name }}@endif
                                                </p>
                                                @if ($transaction->note && $transaction->source_type !== 'adjustment')
                                                    <p class="mt-1 break-words text-sm text-muted">{{ $transaction->note }}</p>
                                                @endif
                                            </div>
                                            <div class="flex flex-col items-end gap-2">
                                                <strong class="money-transaction-amount tabular-nums" data-private>
                                                    {{ $transaction->type === \App\Enums\TransactionType::Expense ? '−' : '' }}{{ rupiah($transaction->amount) }}
                                                </strong>
                                                <x-button type="button" size="sm" variant="secondary" icon="menu-aksi"
                                                    aria-label="Aksi untuk {{ $transactionTitle }}"
                                                    x-data
                                                    x-on:click="$dispatch('open-modal', 'transaction-actions-{{ $transaction->id }}')">Aksi</x-button>
                                            </div>
                                            <x-bottom-sheet name="transaction-actions-{{ $transaction->id }}" :title="'Aksi transaksi: '.$transactionTitle">
                                                <div class="space-y-3">
                                                    @if ($transaction->source_type === null && ! $transaction->wallet?->archived_at && ! $transaction->destinationWallet?->archived_at && ! $transaction->moneyCategory?->archived_at)
                                                        <x-button href="{{ route('user.money.transactions.edit', $transaction) }}" variant="secondary" icon="ubah" class="w-full">Ubah transaksi</x-button>
                                                    @endif
                                                    @if ($transaction->source_type === null || $transaction->source_type === 'adjustment')
                                                        <x-confirm-action :action="route('user.money.transactions.destroy', $transaction)"
                                                            title="Hapus transaksi?"
                                                            :message="'Transaksi '.$transaction->type->label().' sebesar '.rupiah($transaction->amount).' akan dihapus dan saldo dompet dihitung ulang.'"
                                                            label="Hapus" />
                                                    @elseif ($transaction->source_type === 'obligation_payment')
                                                        <x-button href="{{ route('user.obligations.index') }}" variant="secondary" icon="kembali" class="w-full">Batalkan pembayaran</x-button>
                                                    @elseif ($transaction->source_type === 'loan_repayment')
                                                        <x-button href="{{ route('user.loans.index') }}" variant="secondary" icon="kembali" class="w-full">Batalkan pembayaran</x-button>
                                                    @elseif ($transaction->source_type === 'loan')
                                                        <x-button href="{{ route('user.loans.index') }}" variant="secondary" icon="ubah" class="w-full">Kelola utang/piutang</x-button>
                                                    @endif
                                                </div>
                                            </x-bottom-sheet>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @endforeach
                    @endif
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
                    <div data-receipt-scan class="space-y-2 rounded-lg border border-line p-3">
                        <input type="file" accept="image/*" capture="environment" data-receipt-input="camera" hidden>
                        <input type="file" accept="image/*" data-receipt-input="gallery" hidden>
                        <div class="flex flex-wrap gap-2">
                            <x-button type="button" variant="secondary" icon="tambah" data-receipt-trigger="camera">Foto struk</x-button>
                            <x-button type="button" variant="secondary" data-receipt-trigger="gallery">Pilih dari galeri</x-button>
                        </div>
                        <p class="field-help">Struk dibaca langsung di perangkat. Foto tidak dikirim ke mana pun. Dekatkan kamera sampai struk memenuhi layar dan pastikan terang.</p>
                        <p data-receipt-status class="text-sm" role="status" aria-live="polite"></p>
                        <div data-receipt-suggestions class="flex flex-wrap gap-2"></div>
                    </div>
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
                            <option value="">{{ __('ui.money.uncategorized') }}</option>
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
