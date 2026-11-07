<x-guest-layout>
    <div class="mb-6">
        <p class="text-sm font-semibold text-primary-800">Pemeriksaan keamanan</p>
        <h1 class="mt-1 text-2xl font-semibold">Konfirmasi password</h1>
        <p class="mt-2 text-sm leading-6 text-muted">Untuk melanjutkan ke bagian aman ini, masukkan password akun Anda.</p>
    </div>
    <x-error-summary :errors="$errors->messages()" title="Konfirmasi gagal. Periksa isian berikut:" />
    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf
        <div>
            <x-input-label for="password" value="Password (wajib)" />
            <div class="mt-1 flex gap-2">
                <x-text-input id="password" name="password" type="password" class="min-w-0 flex-1" required autofocus autocomplete="current-password" data-password-field aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" aria-describedby="password-error" />
                <x-button type="button" data-password-toggle data-password-show-icon="{{ asset('icons/sprite.svg#icon-lihat-password') }}" data-password-hide-icon="{{ asset('icons/sprite.svg#icon-sembunyikan-password') }}" aria-label="Tampilkan password" variant="secondary" class="px-3">
                    <x-icon name="lihat-password" class="h-5 w-5" data-password-icon />
                    <span data-password-label>Tampilkan</span>
                </x-button>
            </div>
            <p class="field-help">Masukkan password akun Anda untuk melanjutkan.</p>
            <x-input-error id="password-error" :messages="$errors->get('password')" />
        </div>
        <x-button type="submit" data-loading-text="Memeriksa..." variant="primary" class="w-full">Konfirmasi</x-button>
    </form>
</x-guest-layout>
