@extends('layouts.user')

@section('title', 'Tambah tugas')

@section('content')
    <div class="mx-auto max-w-3xl">
        <x-button href="{{ route('user.tasks.index') }}" variant="ghost" icon="kembali" class="mb-3">Kembali</x-button>
        <div class="surface-card mt-3 p-5 sm:p-8">
            <header class="mb-6">
                <p class="text-sm font-semibold text-primary-800">Rencana baru</p>
                <h1 class="mt-1 text-2xl font-semibold">Tambah tugas</h1>
                <p class="mt-2 text-sm text-muted">Tambahkan detail yang membantumu mengingat rencana ini.</p>
            </header>
            <x-error-summary :errors="$errors->messages()" title="Tugas belum tersimpan. Periksa isian berikut:" />
            <form method="POST" action="{{ route('user.tasks.store') }}" class="space-y-5" data-dirty-guard>
                @csrf
                @include('user.tasks.form')
                <x-form-actions :cancel-url="route('user.tasks.index')" submit-label="Simpan tugas" submit-icon="tugas" loading-label="Menyimpan tugas…" />
            </form>
        </div>
    </div>
@endsection
