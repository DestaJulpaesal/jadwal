@extends('layouts.admin')

@section('title', 'Detail akun')
@section('heading', 'Detail akun')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.users.index') }}" class="text-sm font-semibold text-primary-800 hover:underline">Kembali ke manajemen akun</a>
        <p class="mt-4 text-sm font-semibold text-primary-800">Informasi akun</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ $user->name }}</h1>
        <p class="mt-2 text-muted">Detail ini hanya menampilkan ringkasan akun, bukan isi jadwal atau data pribadi pengguna.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(18rem,1fr)]">
        <section class="surface-card overflow-hidden">
            <h2 class="border-b border-line px-5 py-4 font-semibold">Profil akun</h2>
            <dl class="divide-y divide-line">
                <div class="grid gap-1 px-5 py-4 sm:grid-cols-2">
                    <dt class="text-sm text-muted">Nama</dt>
                    <dd class="font-medium">{{ $user->name }}</dd>
                </div>
                <div class="grid gap-1 px-5 py-4 sm:grid-cols-2">
                    <dt class="text-sm text-muted">Verifikasi email</dt>
                    <dd class="break-all font-medium">{{ $user->email }}</dd>
                </div>
                <div class="grid gap-1 px-5 py-4 sm:grid-cols-2">
                    <dt class="text-sm text-muted">Peran</dt>
                    <dd>{{ $user->isAdmin() ? 'Admin' : 'Pengguna' }}</dd>
                </div>
                <div class="grid gap-1 px-5 py-4 sm:grid-cols-2">
                    <dt class="text-sm text-muted">Status akun</dt>
                    <dd><x-status-badge :status="$user->is_active ? 'Aktif' : 'Nonaktif'" /></dd>
                </div>
                <div class="grid gap-1 px-5 py-4 sm:grid-cols-2">
                    <dt class="text-sm text-muted">Email</dt>
                    <dd>{{ $user->hasVerifiedEmail() ? 'Terverifikasi' : 'Belum terverifikasi' }}</dd>
                </div>
                <div class="grid gap-1 px-5 py-4 sm:grid-cols-2">
                    <dt class="text-sm text-muted">Akun dibuat</dt>
                    <dd>{{ $user->created_at->translatedFormat('d M Y, H:i') }}</dd>
                </div>
                <div class="grid gap-1 px-5 py-4 sm:grid-cols-2">
                    <dt class="text-sm text-muted">Terakhir diperbarui</dt>
                    <dd>{{ $user->updated_at->translatedFormat('d M Y, H:i') }}</dd>
                </div>
            </dl>
        </section>

        <section class="surface-card overflow-hidden">
            <h2 class="border-b border-line px-5 py-4 font-semibold">Ringkasan penggunaan</h2>
            <dl class="divide-y divide-line">
                <div class="flex justify-between gap-4 px-5 py-4"><dt class="text-sm text-muted">Tugas</dt><dd class="font-semibold">{{ $user->tasks_count }}</dd></div>
                <div class="flex justify-between gap-4 px-5 py-4"><dt class="text-sm text-muted">Kebiasaan</dt><dd class="font-semibold">{{ $user->habits_count }}</dd></div>
                <div class="flex justify-between gap-4 px-5 py-4"><dt class="text-sm text-muted">Target</dt><dd class="font-semibold">{{ $user->goals_count }}</dd></div>
                <div class="flex justify-between gap-4 px-5 py-4"><dt class="text-sm text-muted">Dompet</dt><dd class="font-semibold">{{ $user->wallets_count }}</dd></div>
            </dl>
        </section>
    </div>

    <section class="surface-card mt-6 p-5 sm:p-6">
        <h2 class="font-semibold">Tindakan admin</h2>
        <p class="mt-1 text-sm text-muted">Perubahan role dan status berdampak pada akses pengguna. Reset password dikirim melalui tautan email.</p>

        @if ($user->id === auth()->id())
            <p class="mt-4 rounded-lg bg-gray-50 p-4 text-sm text-muted">Untuk keamanan, admin tidak dapat mengubah role, status, atau menghapus akun yang sedang digunakan.</p>
        @else
            <div class="mt-5 flex flex-wrap gap-3">
                <x-confirm-action
                    :action="route('admin.users.role', $user)"
                    method="PATCH"
                    :title="$user->isAdmin() ? 'Ubah role menjadi pengguna?' : 'Jadikan akun admin?'"
                    :message="$user->isAdmin() ? 'Pengguna ini akan kehilangan akses ke panel admin.' : 'Pengguna ini akan mendapat akses penuh ke panel admin dan pengelolaan akun.'"
                    :label="$user->isAdmin() ? 'Jadikan pengguna' : 'Jadikan admin'"
                    tone="secondary"
                    icon="ubah"
                />
                <x-confirm-action
                    :action="route('admin.users.password-reset', $user)"
                    method="POST"
                    title="Kirim tautan reset password?"
                    message="Tautan reset akan dikirim ke alamat email akun ini. Password pengguna tidak akan ditampilkan kepada admin."
                    label="Kirim reset password"
                    tone="secondary"
                    icon="kelola-akun"
                />
                <x-confirm-action
                    :action="route('admin.users.status', $user)"
                    method="PATCH"
                    :title="$user->is_active ? 'Nonaktifkan akun?' : 'Aktifkan akun?'"
                    :message="$user->is_active ? 'Pengguna tidak akan dapat masuk selama akunnya nonaktif.' : 'Pengguna akan dapat masuk kembali setelah akun diaktifkan.'"
                    :label="$user->is_active ? 'Nonaktifkan akun' : 'Aktifkan akun'"
                    tone="secondary"
                    :icon="$user->is_active ? 'nonaktifkan' : 'selesai'"
                />
                <x-confirm-action
                    :action="route('admin.users.destroy', $user)"
                    method="DELETE"
                    title="Hapus akun secara permanen?"
                    message="Semua data jadwal milik pengguna ini akan ikut dihapus. Tindakan ini tidak dapat dibatalkan."
                    label="Hapus akun"
                />
            </div>
        @endif
    </section>
@endsection
