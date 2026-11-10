<x-guest-layout>
    <section class="c g register-hero">
        <svg class="register-mascot stk bobf" viewBox="0 0 110 100" aria-hidden="true"><use href="#mascot" /></svg>
        <svg class="register-spark stk" viewBox="0 0 24 24" aria-hidden="true"><use href="#spark" /></svg>
        <p class="k">Jadwal Harian</p>
        <h1>Mulai dari<br>satu langkah!</h1>
        <span class="chip register-chip">Hari lebih tertata</span>
    </section>

    <form method="POST" action="{{ route('register') }}" class="c register-form" id="register-form">
        @csrf
        <x-error-summary :errors="$errors->messages()" title="Akun belum dibuat. Periksa isian berikut:" />
        <p class="required-note">Kolom bertanda <span class="required-mark" aria-hidden="true">*</span> perlu diisi.</p>
        <div>
            <label class="k register-label" for="name">Nama lengkap <span class="required-mark" aria-hidden="true">*</span></label>
            <div class="fld">
                <span class="ic" aria-hidden="true"><svg viewBox="0 0 48 48"><use href="#us" /></svg></span>
                <input id="name" name="name" type="text" autocomplete="name" autocapitalize="words" enterkeyhint="next" value="{{ old('name') }}" placeholder="Nama yang ingin kami panggil" aria-label="Nama lengkap" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="name-help name-error" maxlength="255" required>
            </div>
            <p id="name-help" class="field-help">Nama ini akan ditampilkan di akunmu.</p>
            <x-input-error id="name-error" :messages="$errors->get('name')" />
        </div>
        <div>
            <label class="k register-label" for="email">Alamat email <span class="required-mark" aria-hidden="true">*</span></label>
            <div class="fld">
                <span class="ic" aria-hidden="true"><svg viewBox="0 0 48 48"><use href="#ml" /></svg></span>
                <input id="email" name="email" type="email" inputmode="email" autocapitalize="none" enterkeyhint="next" autocomplete="email" value="{{ old('email') }}" placeholder="nama@email.com" aria-label="Alamat email" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="email-help email-error" maxlength="255" required>
            </div>
            <p id="email-help" class="field-help">Gunakan alamat email aktif untuk masuk dan pemulihan akun.</p>
            <x-input-error id="email-error" :messages="$errors->get('email')" />
        </div>
        <div>
            <label class="k register-label" for="password">Kata sandi <span class="required-mark" aria-hidden="true">*</span></label>
            <div class="fld">
                <span class="ic" aria-hidden="true"><svg viewBox="0 0 48 48"><use href="#lk" /></svg></span>
                <input id="password" name="password" type="password" autocomplete="new-password" enterkeyhint="next" data-password-field data-password-checklist placeholder="Buat kata sandi yang kuat" aria-label="Kata sandi" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" aria-describedby="password-help password-error" required>
                <x-button type="button" class="ic password-toggle" data-password-toggle data-password-show-icon="#password-visible" data-password-hide-icon="#password-hidden" aria-label="Tampilkan kata sandi">
                    <svg viewBox="0 0 24 24" data-password-icon aria-hidden="true"><use href="#password-visible" /></svg>
                    <span class="sr-only" data-password-label>Tampilkan</span>
                </x-button>
            </div>
            <p id="password-help" class="register-help">Gunakan setidaknya 8 karakter dan jangan gunakan kata sandi dari akun lain.</p>
            <div class="mt-2" data-password-strength-for="password" role="progressbar" aria-valuemin="0" aria-valuemax="4" aria-valuenow="0" aria-label="Kekuatan kata sandi" aria-live="polite">
                <div class="h-2 overflow-hidden rounded-full bg-canvas" aria-hidden="true">
                    <div class="h-full w-0 rounded-full bg-danger transition-all" data-password-strength-bar></div>
                </div>
                <p class="mt-1 text-xs text-muted" data-password-strength-label>Kata sandi belum diisi.</p>
            </div>
            <ul class="mt-2 space-y-1 text-xs text-muted" data-password-checklist aria-label="Syarat kata sandi">
                <li data-password-rule="length">○ Minimal 8 karakter</li>
                <li data-password-rule="mixed">○ Ada huruf besar dan kecil</li>
                <li data-password-rule="number">○ Ada angka</li>
            </ul>
            <x-input-error id="password-error" :messages="$errors->get('password')" />
        </div>
        <div>
            <label class="k register-label" for="password_confirmation">Ulangi kata sandi <span class="required-mark" aria-hidden="true">*</span></label>
            <div class="fld">
                <span class="ic" aria-hidden="true"><svg viewBox="0 0 48 48"><use href="#lk" /></svg></span>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" enterkeyhint="done" placeholder="Ketik ulang kata sandi" aria-label="Ulangi kata sandi" aria-invalid="{{ $errors->has('password_confirmation') ? 'true' : 'false' }}" aria-describedby="password-confirmation-help password-confirmation-error" required>
            </div>
            <p id="password-confirmation-help" class="field-help">Masukkan kata sandi yang sama untuk memastikan tidak ada salah ketik.</p>
            <x-input-error id="password-confirmation-error" :messages="$errors->get('password_confirmation')" />
        </div>
        <x-button type="submit" variant="primary" icon="tambah" class="register-go" data-loading-text="Menyiapkan ruangmu...">Buat akun</x-button>
        <p class="lnk register-links">Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a></p>
    </form>
</x-guest-layout>
