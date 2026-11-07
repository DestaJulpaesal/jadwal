@extends('layouts.user')

@section('title', 'Ubah target')

@section('content')
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('user.goals.index') }}" class="text-sm font-medium text-primary-800 underline">Kembali ke target</a>
        <section class="surface-card mt-4 p-5 sm:p-8">
            <h1 class="mb-6 text-2xl font-semibold">Ubah target</h1>
            <x-error-summary :errors="$errors->messages()" title="Perubahan target belum tersimpan. Periksa isian berikut:" />
            <form method="POST" action="{{ route('user.goals.update', $goal) }}" class="space-y-5" data-dirty-guard>
                @csrf
                @method('PUT')
                @include('user.goals.form')
                <x-form-actions :cancel-url="route('user.goals.index')" submit-label="Simpan perubahan" submit-icon="target" loading-label="Menyimpan perubahan…" />
            </form>
        </section>
    </div>
@endsection
