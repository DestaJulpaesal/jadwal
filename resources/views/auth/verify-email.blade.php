<x-guest-layout>
    <div class="mb-6">
        <p class="text-sm font-semibold text-primary-800">Satu langkah lagi</p>
        <h1 class="mt-1 text-2xl font-semibold">Periksa emailmu</h1>
    </div>
    @if (config('auth.email_verification'))
        <p class="text-sm leading-6 text-muted">Klik tautan verifikasi yang kami kirim ke emailmu. Jika belum menerimanya, kamu bisa meminta tautan baru.</p>
    @else
        <p class="text-sm leading-6 text-muted">Verifikasi email tidak diaktifkan. Kamu bisa melanjutkan menggunakan akun.</p>
    @endif
    <div class="mt-6 flex flex-col gap-3 sm:flex-row">
        @if (config('auth.email_verification'))
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-button type="submit" data-loading-text="Mengirim..." variant="primary">Kirim ulang tautan</x-button>
            </form>
        @endif
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-button type="submit" variant="secondary">Keluar</x-button>
        </form>
    </div>
</x-guest-layout>
