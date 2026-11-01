<x-guest-layout>
    <div class="mb-6">
        <p class="text-sm font-semibold text-primary-800">Satu langkah lagi</p>
        <h1 class="mt-1 text-2xl font-semibold">Periksa email Anda</h1>
    </div>
    @if (config('auth.email_verification'))
        <p class="text-sm leading-6 text-muted">Klik tautan verifikasi yang kami kirim ke email Anda. Jika belum menerima email, Anda dapat meminta tautan baru.</p>
        @if (session('status') === 'verification-link-sent')
            <x-alert type="success" class="mt-5">Tautan verifikasi baru sudah dikirim ke alamat email Anda.</x-alert>
        @endif
    @else
        <p class="text-sm leading-6 text-muted">Verifikasi email tidak diaktifkan. Anda dapat melanjutkan menggunakan akun.</p>
    @endif
    <div class="mt-6 flex flex-col gap-3 sm:flex-row">
        @if (config('auth.email_verification'))
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn-primary" data-loading-text="Mengirim...">Kirim ulang tautan</button>
            </form>
        @endif
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-secondary">Keluar</button>
        </form>
    </div>
</x-guest-layout>
