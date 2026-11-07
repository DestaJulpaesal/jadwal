@extends('layouts.user')

@section('title', 'Profil')

@section('content')
    <div class="mx-auto max-w-3xl space-y-5">
        <header class="mb-6 flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-primary-800">Pengaturan akun</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight">Profil Anda</h1>
                <p class="mt-2 text-muted">Perbarui informasi dan keamanan akun. Zona waktu serta preferensi aplikasi tersedia di Pengaturan.</p>
            </div>
            <x-button href="{{ route('settings.edit') }}" aria-label="Buka pengaturan" title="Pengaturan" variant="ghost" class="shrink-0">
                <x-icon name="zona-waktu" class="h-6 w-6" />
            </x-button>
        </header>

        @if (session('status') === 'profile-updated')
            <x-alert type="success">Informasi profil berhasil diperbarui.</x-alert>
        @elseif (session('status') === 'password-updated')
            <x-alert type="success">Password berhasil diperbarui.</x-alert>
        @elseif (session('status') === 'verification-link-sent')
            <x-alert type="success">Tautan verifikasi baru sudah dikirim ke alamat email Anda.</x-alert>
        @endif

        <section class="surface-card p-5 sm:p-7">
            @include('profile.partials.update-profile-information-form')
        </section>
        <section class="surface-card p-5 sm:p-7">
            @include('profile.partials.update-password-form')
        </section>
        <section class="surface-card p-5 sm:p-7">
            <h2 class="text-lg font-semibold">Sesi akun</h2>
            <p class="mt-1 text-sm text-muted">Keluar dari akun ini di perangkat yang sedang digunakan.</p>
            <form method="POST" action="{{ route('logout') }}" class="mt-4">
                @csrf
                <x-button type="submit" variant="secondary"><x-icon name="keluar" class="h-5 w-5" />Keluar</x-button>
            </form>
        </section>
        <section class="surface-card border-red-200 p-5 sm:p-7">
            @include('profile.partials.delete-user-form')
        </section>
    </div>
@endsection
