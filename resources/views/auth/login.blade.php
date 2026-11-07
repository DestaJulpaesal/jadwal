<x-guest-layout>
    <section class="c b hero-card">
        <svg class="hero-mascot stk bobf" viewBox="0 0 110 100" aria-hidden="true"><use href="#mascot" /></svg>
        <svg class="hero-spark stk" viewBox="0 0 24 24" aria-hidden="true"><use href="#spark" /></svg>
        <svg class="hero-calendar stk bobf" viewBox="0 0 48 48" aria-hidden="true"><use href="#cl" /></svg>
        <svg class="hero-wallet stk bobf" viewBox="0 0 48 48" aria-hidden="true"><use href="#wl" /></svg>
        <p class="k">Jadwal Harian</p>
        <h1 style="margin-top: 4px">Masuk dulu,<br>Pahlawan!</h1>
        <span class="chip hero-chip">Rencanamu tertata</span>
    </section>

    <form method="POST" action="{{ route('login') }}" class="c login-form" id="login-form">
        @csrf
        @php
            $loginErrors = $errors->messages();
            unset($loginErrors['credentials'], $loginErrors['throttle']);
        @endphp
        <x-error-summary :errors="$loginErrors" title="Belum bisa masuk. Periksa isian berikut:" />
        @if ($errors->has('credentials'))
            <p class="msg" role="alert" aria-live="assertive">
                {{ $errors->first('credentials') }}
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}">Atur ulang kata sandi.</a>
                @endif
            </p>
        @endif
        @if ($errors->has('throttle'))
            @php
                preg_match('/(\d+):(\d{2})/', $errors->first('throttle'), $throttleParts);
                $throttleSeconds = isset($throttleParts[1], $throttleParts[2])
                    ? ((int) $throttleParts[1] * 60) + (int) $throttleParts[2]
                    : 0;
            @endphp
            <p class="msg" role="alert" aria-live="assertive" data-login-throttle data-throttle-seconds="{{ $throttleSeconds }}">
                {{ $errors->first('throttle') }}
            </p>
        @endif
        <label class="k login-label" for="email">Email (wajib)</label>
        <div class="fld">
            <span class="ic" style="background: var(--y)" aria-hidden="true"><svg viewBox="0 0 48 48"><use href="#ml" /></svg></span>
            <input id="email" name="email" type="email" inputmode="email" autocapitalize="none" enterkeyhint="next" autocomplete="username" value="{{ old('email') }}" placeholder="nama@email.com" aria-label="Alamat email" aria-describedby="login-email-help login-email-error" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" maxlength="255" required>
        </div>
        <p id="login-email-help" class="field-help">Gunakan alamat email yang terdaftar di akun kamu.</p>
        <x-input-error id="login-email-error" :messages="$errors->get('email')" />

        <div class="password-heading">
            <label class="k" for="password">Kata sandi (wajib)</label>
            <label for="remember_me" class="remember">
                <input id="remember_me" type="checkbox" name="remember" @checked(old('remember'))>
                Ingat saya
            </label>
        </div>
        <div class="fld">
            <span class="ic" style="background: var(--g)" aria-hidden="true"><svg viewBox="0 0 48 48"><use href="#lk" /></svg></span>
            <input id="password" name="password" type="password" enterkeyhint="done" autocomplete="current-password" data-password-field data-caps-lock placeholder="Masukkan kata sandi" aria-label="Kata sandi" aria-describedby="login-password-help login-caps-lock" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" @if ($errors->has('credentials')) autofocus @endif required>
            <x-button type="button" class="ic password-toggle" data-password-toggle data-password-show-icon="#password-visible" data-password-hide-icon="#password-hidden" aria-label="Tampilkan kata sandi">
                <svg viewBox="0 0 24 24" data-password-icon aria-hidden="true"><use href="#password-visible" /></svg>
                <span class="sr-only" data-password-label>Tampilkan</span>
            </x-button>
        </div>
        <p id="login-password-help" class="field-help">Masukkan kata sandi yang kamu gunakan saat mendaftar.</p>
        <p id="login-caps-lock" class="field-help" data-caps-lock-hint hidden>Caps Lock sedang aktif.</p>
        <x-input-error id="login-password-error" :messages="$errors->get('password')" />

        <div class="row"><x-button type="submit" variant="primary" icon="kelola-akun" id="lg" data-loading-text="Memeriksa...">Masuk</x-button></div>
        <div class="lnk">
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}">Lupa kata sandi?</a>
            @endif
            <a href="{{ route('register') }}">Buat akun baru</a>
        </div>
    </form>

    <section class="c" aria-label="Fitur Jadwal Harian">
        <div class="hd"><h2>Lihat dulu isinya</h2></div>
        <div class="quick">
            <div class="q"><span class="ic" aria-hidden="true"><svg viewBox="0 0 48 48"><use href="#tk" /></svg></span>Tugas</div>
            <div class="q"><span class="ic" aria-hidden="true"><svg viewBox="0 0 48 48"><use href="#fl" /></svg></span>Kebiasaan</div>
            <div class="q"><span class="ic" aria-hidden="true"><svg viewBox="0 0 48 48"><use href="#wl" /></svg></span>Uang</div>
            <div class="q"><span class="ic" aria-hidden="true"><svg viewBox="0 0 48 48"><use href="#cl" /></svg></span>Jadwal</div>
        </div>
    </section>
</x-guest-layout>
