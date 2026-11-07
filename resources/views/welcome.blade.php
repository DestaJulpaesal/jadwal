<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Jadwal Harian — Atur hari dengan lebih tenang</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=3">
    @vite(['resources/css/app.css', 'resources/css/pages/home.css', 'resources/js/app.js'])
</head>
<body class="concept-home">
    <x-concept-b-sprite />
    <header class="home-header">
        <a href="{{ route('home') }}" class="home-brand" aria-label="Jadwal Harian, beranda">
            <span class="home-brand-icon"><svg viewBox="0 0 48 48" aria-hidden="true"><use href="#cl" /></svg></span>
            <span>Jadwal Harian</span>
        </a>
        <nav class="home-nav" aria-label="Akun">
            @auth
                <a href="{{ route('user.dashboard') }}" class="home-button home-button-primary"><svg viewBox="0 0 48 48" aria-hidden="true"><use href="#home" /></svg>Buka beranda</a>
            @else
                <a href="{{ route('login') }}" class="home-login"><svg viewBox="0 0 48 48" aria-hidden="true"><use href="#us" /></svg><span>Masuk</span></a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="home-button home-button-primary"><span class="home-plus" aria-hidden="true">+</span>Daftar gratis</a>
                @endif
            @endauth
        </nav>
    </header>

    <main>
        <section class="home-hero" aria-labelledby="home-title">
            <div class="hero-copy-card">
                <svg class="home-spark" viewBox="0 0 24 24" aria-hidden="true"><use href="#spark" /></svg>
                <p class="home-kicker"><svg viewBox="0 0 48 48" aria-hidden="true"><use href="#cl" /></svg>Rencana harian yang lebih tertata</p>
                <h1 id="home-title">Rencanakan hari,<br>jalani dengan tenang.</h1>
                <p class="home-description">Tugas, kebiasaan, agenda, dan catatan uangmu—tertata dalam satu tempat. Mulai dari satu hal kecil, sisanya bisa menyusul.</p>
                <div class="home-actions">
                    @guest
                        <a href="{{ route('register') }}" class="home-button home-button-primary"><span class="home-plus" aria-hidden="true">+</span>Mulai sekarang</a>
                        <a href="{{ route('login') }}" class="home-button home-button-secondary"><svg viewBox="0 0 48 48" aria-hidden="true"><use href="#us" /></svg>Masuk ke akun</a>
                    @else
                        <a href="{{ route('user.dashboard') }}" class="home-button home-button-primary"><svg viewBox="0 0 48 48" aria-hidden="true"><use href="#home" /></svg>Lihat jadwalku</a>
                    @endguest
                </div>
                <p class="home-privacy"><svg viewBox="0 0 48 48" aria-hidden="true"><use href="#lk" /></svg>Jadwalmu hanya bisa dilihat oleh akunmu.</p>
                <svg class="home-mascot" viewBox="0 0 110 100" aria-hidden="true"><use href="#mascot" /></svg>
            </div>

            <aside class="agenda-card" aria-label="Contoh tampilan agenda">
                <div class="agenda-heading">
                    <div><p class="home-script">Intip rencanamu</p><h2>Hari yang terencana</h2></div>
                    <span class="today-badge">Hari ini</span>
                </div>
                <ol class="agenda-list">
                    <li><time>09.00</time><span class="agenda-icon agenda-green"><svg viewBox="0 0 48 48" aria-hidden="true"><use href="#tk" /></svg></span><div><h3>Tinjau rencana hari ini</h3><p>Mulai dengan melihat prioritas</p></div></li>
                    <li><time>13.00</time><span class="agenda-icon agenda-yellow"><svg viewBox="0 0 48 48" aria-hidden="true"><use href="#cl" /></svg></span><div><h3>Kerjakan satu hal penting</h3><p>Atur waktu agar agenda tetap realistis</p></div></li>
                    <li><time>16.30</time><span class="agenda-icon agenda-pink"><svg viewBox="0 0 48 48" aria-hidden="true"><use href="#wl" /></svg></span><div><h3>Periksa tugas berikutnya</h3><p>Satu langkah kecil tetap berarti</p></div></li>
                </ol>
                <span class="agenda-sticker" aria-hidden="true"><svg viewBox="0 0 48 48"><use href="#fl" /></svg></span>
            </aside>
        </section>

        <section id="fitur" class="home-features" aria-label="Yang bisa kamu atur">
            <article class="feature-card feature-green"><span class="feature-icon"><svg viewBox="0 0 48 48" aria-hidden="true"><use href="#tk" /></svg></span><h2>Tugas lebih jelas</h2><p>Catat yang penting, tentukan prioritas, lalu selesaikan satu per satu.</p></article>
            <article class="feature-card feature-pink"><span class="feature-icon"><svg viewBox="0 0 48 48" aria-hidden="true"><use href="#fl" /></svg></span><h2>Kebiasaan baik</h2><p>Bangun rutinitas kecil dan rayakan progres yang kamu capai.</p></article>
            <article class="feature-card feature-blue"><span class="feature-icon"><svg viewBox="0 0 48 48" aria-hidden="true"><use href="#cl" /></svg></span><h2>Agenda tertata</h2><p>Atur waktu dengan tenang dan lihat rencanamu dalam satu kalender.</p></article>
        </section>
    </main>

    <footer class="home-footer">
        <div class="footer-invite">
            <span class="footer-ticket footer-ticket-left" aria-hidden="true">SIAP?</span>
            <div class="footer-invite-copy">
                <span class="footer-invite-icon"><svg viewBox="0 0 48 48" aria-hidden="true"><use href="#fl" /></svg></span>
                <div>
                    <p class="footer-invite-message"><span class="home-script">Pelan-pelan juga sampai</span> Satu langkah kecil bisa bikin harimu lebih tertata.</p>
                </div>
            </div>
            @guest
                <a href="{{ route('register') }}" class="home-button home-button-primary footer-cta"><span class="home-plus" aria-hidden="true">+</span>Mulai gratis</a>
            @else
                <a href="{{ route('user.dashboard') }}" class="home-button home-button-primary footer-cta">Lihat jadwalku</a>
            @endguest
            <span class="footer-ticket footer-ticket-right" aria-hidden="true">MULAI!</span>
        </div>
        <div class="footer-divider" aria-hidden="true">
            <span class="footer-divider-line"></span>
            <span class="footer-divider-label">ATUR HARI, BUKAN KEKHAWATIRAN</span>
            <span class="footer-divider-line"></span>
        </div>
        <div class="footer-bottom">
            <div class="footer-bottom-left">
                <div class="footer-brand-row">
                    <a href="{{ route('home') }}" class="footer-brand"><span class="footer-brand-icon"><svg viewBox="0 0 48 48" aria-hidden="true"><use href="#cl" /></svg></span>Jadwal Harian</a>
                    <span class="footer-live-badge"><span aria-hidden="true"></span> Teman harianmu</span>
                </div>
                <p class="footer-note">Rencana yang jelas, satu hari pada satu waktu.</p>
                <p class="footer-quote">“Hari yang tenang dimulai dari rencana kecil.”</p>
                <div class="footer-topics" aria-label="Ruang yang bisa diatur">
                    <span>Tugas</span><span>Kebiasaan</span><span>Agenda</span><span>Keuangan</span>
                </div>
            </div>
            <div class="footer-bottom-right">
                <div class="footer-nav-group">
                    <span class="footer-nav-label">Jelajahi</span>
                    <nav class="footer-links" aria-label="Tautan akun">
                        <a href="#fitur">Fitur</a>
                        @auth
                            <a href="{{ route('user.dashboard') }}">Berandaku</a>
                        @else
                            <a href="{{ route('login') }}">Masuk</a>
                            <a href="{{ route('register') }}">Daftar gratis</a>
                        @endauth
                    </nav>
                </div>
                <div class="footer-meta">
                    <span class="footer-copy">&copy; {{ now()->year }} Jadwal Harian</span>
                    <span class="footer-made">Dibuat untuk hari yang lebih ringan <span aria-hidden="true">✦</span></span>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
