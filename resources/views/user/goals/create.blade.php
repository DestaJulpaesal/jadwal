@extends('layouts.user')

@section('title', 'Buat target')

@section('content')
    <div class="mx-auto max-w-3xl">
        <section class="surface-card p-5 sm:p-8">
            <header class="mb-6">
                <p class="text-sm font-semibold text-primary-800">Langkah demi langkah</p>
                <h1 class="mt-1 text-2xl font-semibold">Buat target</h1>
                <p class="mt-2 text-sm text-muted">Tentukan hasil akhir dan cara kamu ingin mengukur progres.</p>
            </header>
            <x-error-summary :errors="$errors->messages()" title="Target belum tersimpan. Periksa isian berikut:" />
            <form method="POST" action="{{ route('user.goals.store') }}" class="space-y-5" data-dirty-guard>
                @csrf
                @include('user.goals.form')
                <x-form-actions :cancel-url="route('user.goals.index')" submit-label="Simpan target" submit-icon="target" loading-label="Menyimpan target…" />
            </form>
        </section>
    </div>
@endsection
