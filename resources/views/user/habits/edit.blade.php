@extends('layouts.user')

@section('title', 'Ubah kebiasaan')

@section('content')
    <div class="mx-auto max-w-3xl">
        <x-button href="{{ route('user.habits.index') }}" variant="ghost" icon="kembali" class="mb-3">Kembali</x-button>
        <div class="surface-card habit-form-shell mt-4 p-5 sm:p-8">
            <div class="habit-form-heading">
                <span class="habit-form-icon"><x-concept-icon name="fl" class="h-8 w-8" /></span>
                <div><p class="k">Perbarui ritmemu</p><h1>Ubah kebiasaan</h1><p class="habit-form-subtitle">Sesuaikan jadwal dan target agar tetap realistis.</p></div>
            </div>
            <x-error-summary :errors="$errors->messages()" title="Perubahan kebiasaan belum tersimpan. Periksa isian berikut:" />
            <form method="POST" action="{{ route('user.habits.update', $habit) }}" class="habit-form" data-dirty-guard>
                @csrf
                @method('PUT')
                @include('user.habits.form')
                <x-form-actions :cancel-url="route('user.habits.index')" submit-label="Simpan perubahan" submit-icon="kebiasaan" loading-label="Menyimpan perubahan…" />
            </form>
        </div>
    </div>
@endsection
