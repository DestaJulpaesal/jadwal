<x-guest-layout>
    <div class="mb-6">
        <p class="text-sm font-semibold text-primary-800">Keamanan akun</p>
        <h1 class="mt-1 text-2xl font-semibold">Buat kata sandi baru</h1>
        <p class="mt-2 text-sm text-muted">Gunakan kata sandi yang kuat dan tidak dipakai di akun lain.</p>
    </div>
    <x-error-summary :errors="$errors->messages()" title="Kata sandi belum diperbarui. Periksa isian berikut:" />
    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div>
            <x-input-label for="email" value="Alamat email (wajib)" />
            <x-text-input id="email" name="email" type="email" inputmode="email" autocapitalize="none" enterkeyhint="next" class="mt-1" :value="old('email', $request->email)" required autofocus autocomplete="username" maxlength="255" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="email-help email-error" />
            <p id="email-help" class="field-help">Gunakan email yang menerima tautan pengaturan ulang ini.</p>
            <x-input-error id="email-error" :messages="$errors->get('email')" />
        </div>
        <div>
            <x-input-label for="password" value="Kata sandi baru (wajib)" />
            <div class="mt-1 flex gap-2">
                <x-text-input id="password" name="password" type="password" class="min-w-0 flex-1" required autocomplete="new-password" enterkeyhint="next" data-password-field aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" aria-describedby="password-help password-error" />
                <x-button type="button" data-password-toggle data-password-show-icon="{{ asset('icons/sprite.svg#icon-lihat-password') }}" data-password-hide-icon="{{ asset('icons/sprite.svg#icon-sembunyikan-password') }}" aria-label="Tampilkan kata sandi" variant="secondary" class="px-3">
                    <x-icon name="lihat-password" class="h-5 w-5" data-password-icon />
                    <span data-password-label>Tampilkan</span>
                </x-button>
            </div>
            <p id="password-help" class="field-help">Gunakan setidaknya 8 karakter dan hindari kata sandi dari akun lain.</p>
            <div class="mt-2" data-password-strength-for="password" role="progressbar" aria-valuemin="0" aria-valuemax="4" aria-valuenow="0" aria-label="Kekuatan kata sandi baru" aria-live="polite">
                <div class="h-2 overflow-hidden rounded-full bg-canvas" aria-hidden="true">
                    <div class="h-full w-0 rounded-full bg-danger transition-all" data-password-strength-bar></div>
                </div>
                <p class="mt-1 text-xs text-muted" data-password-strength-label>Kata sandi belum diisi.</p>
            </div>
            <x-input-error id="password-error" :messages="$errors->get('password')" />
        </div>
        <div>
            <x-input-label for="password_confirmation" value="Ulangi kata sandi baru (wajib)" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1" required autocomplete="new-password" enterkeyhint="done" aria-invalid="{{ $errors->has('password_confirmation') ? 'true' : 'false' }}" aria-describedby="password-confirmation-help password-confirmation-error" />
            <p id="password-confirmation-help" class="field-help">Masukkan kata sandi yang sama untuk memastikan tidak ada salah ketik.</p>
            <x-input-error id="password-confirmation-error" :messages="$errors->get('password_confirmation')" />
        </div>
        <x-button type="submit" data-loading-text="Menyimpan..." variant="primary" class="w-full">Simpan kata sandi baru</x-button>
    </form>
</x-guest-layout>
