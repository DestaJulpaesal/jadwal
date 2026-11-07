@extends('layouts.user')

@section('title', 'Ubah tugas')

@section('content')
    <div class="mx-auto max-w-3xl">
        <x-button href="{{ route('user.tasks.index') }}" variant="ghost" icon="kembali" class="mb-3">Kembali</x-button>
        <div class="surface-card mt-3 p-5 sm:p-8">
            <header class="mb-6">
                <p class="text-sm font-semibold text-primary-800">Perbarui rencana</p>
                <h1 class="mt-1 text-2xl font-semibold">Ubah tugas</h1>
            </header>
            <x-error-summary :errors="$errors->messages()" title="Perubahan belum tersimpan. Periksa isian berikut:" />
            <form method="POST" action="{{ route('user.tasks.update', $task) }}" class="space-y-5" data-dirty-guard>
                @csrf
                @method('PUT')
                @include('user.tasks.form')
                <x-form-actions :cancel-url="route('user.tasks.index')" submit-label="Simpan perubahan" submit-icon="tugas" loading-label="Menyimpan perubahan…" />
            </form>
        </div>
    </div>
@endsection
