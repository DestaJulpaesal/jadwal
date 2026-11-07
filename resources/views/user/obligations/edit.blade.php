@extends('layouts.user')

@section('title', 'Ubah pengingat')

@section('content')
    <div class="mx-auto max-w-3xl">
        <x-button href="{{ route('user.obligations.index') }}" variant="ghost" icon="kembali" class="mb-3">Kembali</x-button>
        <section class="surface-card p-5 sm:p-8">
            <header class="mb-6">
                <p class="text-sm font-semibold text-primary-800">Pengingat berkala</p>
                <h1 class="mt-1 text-2xl font-semibold">Ubah {{ $obligation->kind->label() }}</h1>
                <p class="mt-2 text-sm text-muted">Data periode dikunci setelah pembayaran tercatat agar riwayat tetap konsisten.</p>
            </header>
            <x-error-summary :errors="$errors->messages()" title="Pengingat belum diperbarui. Periksa isian berikut:" />
            <form method="POST" action="{{ route('user.obligations.update', $obligation) }}" class="space-y-5" data-dirty-guard>
                @csrf
                @method('PATCH')
                @include('user.obligations.form')
                <x-form-actions :cancel-url="route('user.obligations.index')" submit-label="Simpan pengingat" submit-icon="selesai" loading-label="Menyimpan pengingat…" />
            </form>
        </section>
    </div>
@endsection
