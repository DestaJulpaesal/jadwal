@extends('layouts.user')

@section('title', 'Profil')

@section('content')
    <div class="mx-auto max-w-3xl space-y-5">
        <x-page-header eyebrow="Pengaturan akun" title="Profil" description="Perbarui informasi dan keamanan akun. Zona waktu serta preferensi aplikasi tersedia di Pengaturan.">
            <x-button href="{{ route('settings.edit') }}" aria-label="Buka pengaturan" title="Pengaturan" variant="ghost" class="shrink-0">
                <x-icon name="zona-waktu" class="h-6 w-6" />
            </x-button>
        </x-page-header>
        @include('user.partials.profile-subnav')

        <section class="surface-card p-5 sm:p-7">
            @include('profile.partials.update-profile-information-form')
        </section>
        <section class="surface-card p-5 sm:p-7">
            @include('profile.partials.update-password-form')
        </section>
        <section class="surface-card p-5 sm:p-7">
            <h2 class="text-lg font-semibold">Sesi akun</h2>
            <p class="mt-1 text-sm text-muted">Keluar dari akun ini di perangkat yang sedang digunakan.</p>
            <div class="mt-4">
                <x-confirm-action
                    :action="route('logout')"
                    method="POST"
                    tone="secondary"
                    icon="keluar"
                    label="Keluar"
                    title="Keluar dari akun?"
                    message="Kamu akan keluar dari sesi di perangkat ini. Data yang sudah tersimpan tetap aman."
                />
            </div>
        </section>
        <section id="bantuan" class="surface-card p-5 sm:p-7" aria-labelledby="help-heading">
            <h2 id="help-heading" class="text-lg font-semibold">Bantuan singkat</h2>
            <p class="mt-2 text-sm text-muted">Pilih sub-menu di atas untuk membuka bagian yang dibutuhkan. Perubahan data tersimpan setelah kamu menekan tombol Simpan.</p>
        </section>
        <section class="surface-card border-red-200 p-5 sm:p-7">
            @include('profile.partials.delete-user-form')
        </section>
    </div>
@endsection
