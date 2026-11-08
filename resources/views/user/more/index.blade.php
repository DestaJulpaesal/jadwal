@extends('layouts.user')

@section('title', 'Lainnya')

@section('content')
    <div class="mx-auto max-w-4xl">
        <x-page-header title="Lainnya" description="Kalender, target, pengaturan, dan fitur lain ada di sini." />

        <nav class="grid gap-3 sm:grid-cols-2" aria-label="Fitur lainnya">
            <a class="surface-card flex min-h-16 items-center gap-3 p-4 hover:border-primary-500" href="{{ route('user.calendar') }}"><x-concept-icon name="cl" class="h-6 w-6" /><span><strong class="block">Kalender</strong><span class="text-sm text-muted">Lihat agenda berdasarkan tanggal.</span></span></a>
            <a class="surface-card flex min-h-16 items-center gap-3 p-4 hover:border-primary-500" href="{{ route('user.goals.index') }}"><x-icon name="target" class="h-6 w-6" /><span><strong class="block">Target</strong><span class="text-sm text-muted">Pantau hasil yang ingin dicapai.</span></span></a>
            <a class="surface-card flex min-h-16 items-center gap-3 p-4 hover:border-primary-500" href="{{ route('user.obligations.index') }}"><x-icon name="tagihan" class="h-6 w-6" /><span><strong class="block">Tagihan & cicilan</strong><span class="text-sm text-muted">Atur tanggal dan pembayaran.</span></span></a>
            <a class="surface-card flex min-h-16 items-center gap-3 p-4 hover:border-primary-500" href="{{ route('user.loans.index') }}"><x-icon name="utang" class="h-6 w-6" /><span><strong class="block">Utang-piutang</strong><span class="text-sm text-muted">Catat pinjaman dan pembayarannya.</span></span></a>
            <a class="surface-card flex min-h-16 items-center gap-3 p-4 hover:border-primary-500" href="{{ route('user.reports.index') }}"><x-concept-icon name="rc" class="h-6 w-6" /><span><strong class="block">Laporan</strong><span class="text-sm text-muted">Tinjau ringkasan keuangan bulanan.</span></span></a>
            <a class="surface-card flex min-h-16 items-center gap-3 p-4 hover:border-primary-500" href="{{ route('user.search') }}"><x-concept-icon name="sc" class="h-6 w-6" /><span><strong class="block">Pencarian</strong><span class="text-sm text-muted">Temukan catatan di seluruh aplikasi.</span></span></a>
            <a class="surface-card flex min-h-16 items-center gap-3 p-4 hover:border-primary-500" href="{{ route('settings.edit') }}"><x-icon name="zona-waktu" class="h-6 w-6" /><span><strong class="block">Pengaturan</strong><span class="text-sm text-muted">Atur tampilan dan preferensi akun.</span></span></a>
            <a class="surface-card flex min-h-16 items-center gap-3 p-4 hover:border-primary-500" href="{{ route('profile.edit') }}"><x-concept-icon name="pr" class="h-6 w-6" /><span><strong class="block">Profil</strong><span class="text-sm text-muted">Ubah informasi dan keamanan akun.</span></span></a>
        </nav>

        <section id="bantuan" class="surface-card mt-6 p-5" aria-labelledby="help-heading">
            <h2 id="help-heading" class="text-lg font-semibold">Bantuan singkat</h2>
            <p class="mt-2 text-sm text-muted">Pilih menu untuk membuka bagian yang dibutuhkan. Perubahan data tersimpan setelah kamu menekan tombol Simpan.</p>
        </section>

        <section class="surface-card mt-6 p-5" aria-labelledby="install-heading">
            <h2 id="install-heading" class="text-lg font-semibold">Gunakan sebagai aplikasi</h2>
            <p class="mt-2 text-sm text-muted">Pasang Jadwal di layar utama agar lebih mudah dibuka. Halaman dan data akun tidak disimpan saat offline.</p>
            <button type="button" class="mt-4 rounded-lg bg-primary-700 px-4 py-3 font-semibold text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2" data-pwa-install hidden>Pasang aplikasi</button>
            <p class="mt-4 text-sm text-muted" data-pwa-ios-help hidden>Di Safari, pilih Bagikan → Tambah ke Layar Utama.</p>
        </section>

        <form method="POST" action="{{ route('logout') }}" class="mt-5">
            @csrf
            <x-button type="submit" variant="secondary" icon="keluar">Keluar</x-button>
        </form>
    </div>
@endsection
