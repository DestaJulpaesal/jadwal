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
        <label class="k login-label" for="email">Email</label>
        <div class="fld">
            <span class="ic" style="background: var(--y)" aria-hidden="true"><svg viewBox="0 0 48 48"><use href="#ml" /></svg></span>
            <input id="email" name="email" type="email" autocomplete="username" value="{{ old('email') }}" placeholder="nama@email.com" aria-label="Alamat email" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" maxlength="255" required>
        </div>

        <div class="password-heading">
            <label class="k" for="password">Kata sandi</label>
            <label for="remember_me" class="remember">
                <input id="remember_me" type="checkbox" name="remember" @checked(old('remember'))>
                Ingat saya
            </label>
        </div>
        <div class="fld">
            <span class="ic" style="background: var(--g)" aria-hidden="true"><svg viewBox="0 0 48 48"><use href="#lk" /></svg></span>
            <input id="password" name="password" type="password" autocomplete="current-password" data-password-field placeholder="Masukkan kata sandi" aria-label="Kata sandi" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" required>
            <x-button type="button" class="ic password-toggle" data-password-toggle data-password-show-icon="#password-visible" data-password-hide-icon="#password-hidden" aria-label="Tampilkan password">
                <svg viewBox="0 0 24 24" data-password-icon aria-hidden="true"><use href="#password-visible" /></svg>
                <span class="sr-only" data-password-label>Tampilkan</span>
            </x-button>
        </div>

        @if ($errors->any())
            <p class="msg" role="alert" aria-live="assertive">{{ $errors->first() }}</p>
        @elseif (session('status'))
            <p class="msg ok" role="status" aria-live="polite">{{ session('status') }}</p>
        @else
            <p class="msg" aria-hidden="true"></p>
        @endif

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
