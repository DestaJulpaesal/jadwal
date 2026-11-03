<x-guest-layout>
    <section class="c g register-hero">
        <svg class="register-mascot stk bobf" viewBox="0 0 110 100" aria-hidden="true"><use href="#mascot" /></svg>
        <svg class="register-spark stk" viewBox="0 0 24 24" aria-hidden="true"><use href="#spark" /></svg>
        <p class="k">Jadwal Harian</p>
        <h1>Mulai dari<br>satu langkah!</h1>
        <span class="chip register-chip">Hari lebih tertata</span>
    </section>

    @if ($errors->any())
        <x-alert type="danger" class="register-error">
            <p class="font-semibold">Periksa kembali isian berikut:</p>
            <ul class="mt-2 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </x-alert>
    @endif
    <form method="POST" action="{{ route('register') }}" class="c register-form" id="register-form">
        @csrf
        <div>
            <label class="k register-label" for="name">Nama lengkap</label>
            <div class="fld">
                <span class="ic" style="background: var(--p)" aria-hidden="true"><svg viewBox="0 0 48 48"><use href="#us" /></svg></span>
                <input id="name" name="name" type="text" autocomplete="name" value="{{ old('name') }}" placeholder="Nama yang ingin kami panggil" aria-label="Nama lengkap" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="name-error" maxlength="255" required>
            </div>
            <x-input-error id="name-error" :messages="$errors->get('name')" />
        </div>
        <div>
            <label class="k register-label" for="email">Alamat email</label>
            <div class="fld">
                <span class="ic" style="background: var(--y)" aria-hidden="true"><svg viewBox="0 0 48 48"><use href="#ml" /></svg></span>
                <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" placeholder="nama@email.com" aria-label="Alamat email" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="email-error" maxlength="255" required>
            </div>
            <x-input-error id="email-error" :messages="$errors->get('email')" />
        </div>
        <div>
            <label class="k register-label" for="password">Kata sandi</label>
            <div class="fld">
                <span class="ic" style="background: var(--o)" aria-hidden="true"><svg viewBox="0 0 48 48"><use href="#lk" /></svg></span>
                <input id="password" name="password" type="password" autocomplete="new-password" data-password-field placeholder="Buat kata sandi yang kuat" aria-label="Kata sandi" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" aria-describedby="password-help password-error" required>
                <button type="button" class="ic password-toggle" data-password-toggle data-password-show-icon="#password-visible" data-password-hide-icon="#password-hidden" aria-label="Tampilkan password">
                    <svg viewBox="0 0 24 24" data-password-icon aria-hidden="true"><use href="#password-visible" /></svg>
                    <span class="sr-only" data-password-label>Tampilkan</span>
                </button>
            </div>
            <p id="password-help" class="register-help">Pilih kata sandi yang kuat dan mudah kamu ingat.</p>
            <x-input-error id="password-error" :messages="$errors->get('password')" />
        </div>
        <div>
            <label class="k register-label" for="password_confirmation">Ulangi kata sandi</label>
            <div class="fld">
                <span class="ic" style="background: var(--b)" aria-hidden="true"><svg viewBox="0 0 48 48"><use href="#lk" /></svg></span>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Ketik ulang kata sandi" aria-label="Ulangi kata sandi" aria-invalid="{{ $errors->has('password_confirmation') ? 'true' : 'false' }}" aria-describedby="password-confirmation-error" required>
            </div>
            <x-input-error id="password-confirmation-error" :messages="$errors->get('password_confirmation')" />
        </div>
        @if (session('status'))
            <p class="msg ok" role="status" aria-live="polite">{{ session('status') }}</p>
        @endif
        <button type="submit" class="go register-go" data-loading-text="Menyiapkan ruangmu...">Buat akun</button>
        <p class="lnk register-links">Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a></p>
    </form>
</x-guest-layout>
