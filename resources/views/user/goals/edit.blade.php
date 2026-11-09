@extends('layouts.user')

@section('title', 'Ubah target')

@section('content')
    <div class="mx-auto max-w-3xl">
        <x-button href="{{ route('user.goals.index') }}" variant="ghost" icon="kembali" class="mb-3">Kembali</x-button>
        <section class="surface-card mt-4 p-5 sm:p-8">
            <h1 class="mb-6 text-2xl font-semibold">Ubah target</h1>
            @include('user.partials.habits-subnav')
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
