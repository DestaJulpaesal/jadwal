@extends('layouts.user')

@section('title', 'Aplikasi')

@section('content')
    <div class="mx-auto max-w-3xl space-y-5">
        <x-page-header
            eyebrow="Akses perangkat"
            title="Aplikasi"
            description="Pasang Jadwal Harian di layar utama, atau terapkan pembaruan yang tersedia."
        />
        @include('user.partials.profile-subnav')

        <section class="surface-card p-5 sm:p-7" aria-labelledby="install-heading">
            <h2 id="install-heading" class="text-lg font-semibold">Pasang di perangkat</h2>
            <p class="mt-2 text-sm text-muted">Buka Jadwal Harian langsung dari layar utama perangkatmu. Data akun tetap tersimpan dengan aman di server.</p>
            <button type="button" class="btn-primary mt-4" data-pwa-install hidden>Pasang aplikasi</button>
            <p id="pwa-install-status" class="mt-3 text-sm text-muted" role="status" aria-live="polite">
                Jika tombol pasang belum muncul, buka menu browser lalu pilih “Tambahkan ke layar utama” atau “Install app”.
            </p>
            <p class="mt-3 text-sm text-muted" data-pwa-ios-help hidden>Di Safari, pilih Bagikan → Tambah ke Layar Utama.</p>
        </section>

        <section id="pwa-update-card" class="surface-card p-5 sm:p-7" aria-labelledby="update-heading" hidden>
            <h2 id="update-heading" class="text-lg font-semibold">Pembaruan tersedia</h2>
            <p id="pwa-update-status" class="mt-2 text-sm text-muted" role="status" aria-live="polite">Versi baru Jadwal Harian siap digunakan.</p>
            <button id="pwa-update-button" type="button" class="btn-primary mt-4">Terapkan pembaruan</button>
        </section>
    </div>
@endsection
