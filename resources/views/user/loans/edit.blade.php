@extends('layouts.user')

@section('title', 'Ubah utang/piutang')

@section('content')
    <div class="mx-auto max-w-3xl">
        <x-button href="{{ route('user.loans.index') }}" variant="ghost" icon="kembali" class="mb-3">Kembali</x-button>
        <section class="surface-card p-5 sm:p-8">
            <header class="mb-6">
                <p class="text-sm font-semibold text-primary-800">Catatan kesepakatan</p>
                <h1 class="mt-1 text-2xl font-semibold">Ubah utang/piutang</h1>
                <p class="mt-2 text-sm text-muted">Data transaksi awal dikunci setelah pembayaran tercatat agar saldo dan riwayat tetap konsisten.</p>
            </header>
            <x-error-summary :errors="$errors->messages()" title="Utang/piutang belum diperbarui. Periksa isian berikut:" />
            <form method="POST" action="{{ route('user.loans.update', $loan) }}" class="space-y-5" data-dirty-guard>
                @csrf
                @method('PATCH')
                @include('user.loans.form')
                <x-form-actions :cancel-url="route('user.loans.index')" submit-label="Simpan utang/piutang" submit-icon="selesai" loading-label="Menyimpan utang/piutang…" />
            </form>
        </section>
    </div>
@endsection
