@extends('layouts.user')

@section('title', 'Tambah kebiasaan')

@section('content')
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('user.habits.index') }}" class="habit-form-back">← Kembali ke kebiasaan</a>
        <div class="surface-card habit-form-shell mt-4 p-5 sm:p-8">
            <div class="habit-form-heading">
                <span class="habit-form-icon"><x-concept-icon name="fl" class="h-8 w-8" /></span>
                <div><p class="k">Mulai dari hal kecil</p><h1>Tambah kebiasaan</h1><p class="habit-form-subtitle">Atur ritmemu sekali, lalu cukup catat saat selesai.</p></div>
            </div>
            <x-error-summary :errors="$errors->messages()" title="Kebiasaan belum tersimpan. Periksa isian berikut:" />
            <form method="POST" action="{{ route('user.habits.store') }}" class="habit-form" data-dirty-guard>
                @csrf
                @include('user.habits.form')
                <x-form-actions :cancel-url="route('user.habits.index')" submit-label="Simpan kebiasaan" submit-icon="kebiasaan" loading-label="Menyimpan kebiasaan…" />
            </form>
        </div>
    </div>
@endsection
