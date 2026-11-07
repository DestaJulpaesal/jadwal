<section>
    <header>
        <h2 class="text-lg font-semibold">Ubah password</h2>
        <p class="mt-1 text-sm text-muted">Gunakan password yang panjang dan sulit ditebak.</p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-5" data-dirty-guard>
        @csrf
        @method('put')
        <x-error-summary :errors="$errors->updatePassword->messages()" title="Password belum diperbarui. Periksa isian berikut:" />
        <div>
            <x-input-label for="update_password_current_password" value="Password saat ini" />
            <div class="mt-1 flex gap-2">
                <x-text-input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" data-password-field class="min-w-0 flex-1" aria-invalid="{{ $errors->updatePassword->has('current_password') ? 'true' : 'false' }}" aria-describedby="current-password-error" />
                <x-button type="button" data-password-toggle data-password-show-icon="{{ asset('icons/sprite.svg#icon-lihat-password') }}" data-password-hide-icon="{{ asset('icons/sprite.svg#icon-sembunyikan-password') }}" aria-label="Tampilkan password" variant="secondary" class="px-3">
                    <x-icon name="lihat-password" class="h-5 w-5" data-password-icon />
                    <span data-password-label>Tampilkan</span>
                </x-button>
            </div>
            <p class="field-help">Masukkan password yang saat ini digunakan untuk akun ini.</p>
            <x-input-error id="current-password-error" :messages="$errors->updatePassword->get('current_password')" />
        </div>
        <div>
            <x-input-label for="update_password_password" value="Password baru" />
            <div class="mt-1 flex gap-2">
                <x-text-input id="update_password_password" name="password" type="password" autocomplete="new-password" data-password-field class="min-w-0 flex-1" aria-invalid="{{ $errors->updatePassword->has('password') ? 'true' : 'false' }}" aria-describedby="new-password-help new-password-error" />
                <x-button type="button" data-password-toggle data-password-show-icon="{{ asset('icons/sprite.svg#icon-lihat-password') }}" data-password-hide-icon="{{ asset('icons/sprite.svg#icon-sembunyikan-password') }}" aria-label="Tampilkan password" variant="secondary" class="px-3">
                    <x-icon name="lihat-password" class="h-5 w-5" data-password-icon />
                    <span data-password-label>Tampilkan</span>
                </x-button>
            </div>
            <p id="new-password-help" class="field-help">Gunakan setidaknya 8 karakter dan jangan gunakan password dari akun lain.</p>
            <div class="mt-2" data-password-strength-for="update_password_password" role="progressbar" aria-valuemin="0" aria-valuemax="4" aria-valuenow="0" aria-label="Kekuatan password baru" aria-live="polite">
                <div class="h-2 overflow-hidden rounded-full bg-canvas" aria-hidden="true">
                    <div class="h-full w-0 rounded-full bg-danger transition-all" data-password-strength-bar></div>
                </div>
                <p class="mt-1 text-xs text-muted" data-password-strength-label>Password belum diisi.</p>
            </div>
            <x-input-error id="new-password-error" :messages="$errors->updatePassword->get('password')" />
        </div>
        <div>
            <x-input-label for="update_password_password_confirmation" value="Ulangi password baru" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1" autocomplete="new-password" required aria-invalid="{{ $errors->updatePassword->has('password_confirmation') ? 'true' : 'false' }}" aria-describedby="confirm-password-error" />
            <x-input-error id="confirm-password-error" :messages="$errors->updatePassword->get('password_confirmation')" />
            <p class="field-help">Ulangi password baru agar kami dapat memastikan tidak ada salah ketik.</p>
        </div>
        <x-form-actions :cancel-url="route('profile.edit')" submit-label="Simpan password" loading-label="Menyimpan password…" />
    </form>
</section>
