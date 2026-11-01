<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jadwal Harian — Atur hari dengan lebih tenang</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-canvas text-ink antialiased">
    <header class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6">
        <a href="{{ route('home') }}" class="flex min-h-11 items-center gap-3 font-semibold" aria-label="Jadwal Harian">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary-100 text-primary-800" aria-hidden="true">
                <x-icon name="kalender" class="h-5 w-5" />
            </span>
            Jadwal Harian
        </a>
        <nav class="flex items-center gap-2" aria-label="Akun">
            @auth
                <a href="{{ route('user.dashboard') }}" class="btn-primary"><x-icon name="beranda" class="h-5 w-5" />Buka beranda</a>
            @else
                <a href="{{ route('login') }}" class="btn-quiet"><x-icon name="profil" class="h-5 w-5" />Masuk</a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn-primary"><x-icon name="tambah" class="h-5 w-5" />Daftar gratis</a>
                @endif
            @endauth
        </nav>
    </header>

    <main>
        <section class="mx-auto grid max-w-7xl gap-12 px-4 py-12 sm:px-6 sm:py-20 lg:grid-cols-[1fr_0.9fr] lg:items-center lg:py-24">
            <div class="max-w-2xl">
                <p class="mb-4 inline-flex items-center gap-2 rounded-full border border-primary-100 bg-white px-3 py-1 text-sm font-medium text-primary-800">
                    <x-icon name="kalender" class="h-5 w-5" />
                    Rencana harian yang lebih tertata
                </p>
                <h1 class="text-4xl font-semibold leading-tight tracking-tight sm:text-5xl">Lebih mudah tahu apa yang perlu dikerjakan hari ini.</h1>
                <p class="mt-5 max-w-xl text-lg leading-8 text-muted">Catat tugas, tentukan waktunya, dan lihat agenda dalam satu tempat. Mulai dari hal kecil tanpa membuat rencana terasa rumit.</p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    @guest
                        <a href="{{ route('register') }}" class="btn-primary"><x-icon name="tambah" class="h-5 w-5" />Daftar gratis</a>
                        <a href="{{ route('login') }}" class="btn-secondary"><x-icon name="profil" class="h-5 w-5" />Masuk ke akun</a>
                    @else
                        <a href="{{ route('user.dashboard') }}" class="btn-primary"><x-icon name="beranda" class="h-5 w-5" />Lihat jadwal saya</a>
                    @endguest
                </div>
                <p class="mt-4 text-sm text-muted">Jadwal Anda hanya terlihat oleh akun Anda.</p>
            </div>

            <aside class="surface-card p-5 sm:p-7" aria-label="Contoh tampilan agenda">
                <div class="flex items-center justify-between border-b border-line pb-4">
                    <div>
                        <p class="text-sm text-muted">Contoh tampilan agenda</p>
                        <h2 class="mt-1 text-xl font-semibold">Hari yang terencana</h2>
                    </div>
                    <span class="rounded-lg bg-primary-50 px-3 py-2 text-sm font-semibold text-primary-900">Hari ini</span>
                </div>
                <ol class="mt-2 divide-y divide-line">
                    <li class="flex gap-4 py-4">
                        <span class="w-12 pt-0.5 text-sm font-semibold text-primary-800">09.00</span>
                        <x-icon name="tugas" class="h-5 w-5 text-primary-700" />
                        <div><p class="font-medium">Tinjau rencana hari ini</p><p class="mt-1 text-sm text-muted">Mulai dengan melihat prioritas</p></div>
                    </li>
                    <li class="flex gap-4 py-4">
                        <span class="w-12 pt-0.5 text-sm font-semibold text-primary-800">13.00</span>
                        <x-icon name="kalender" class="h-5 w-5 text-primary-700" />
                        <div><p class="font-medium">Kerjakan satu hal penting</p><p class="mt-1 text-sm text-muted">Atur waktu agar agenda tetap realistis</p></div>
                    </li>
                    <li class="flex gap-4 py-4">
                        <span class="w-12 pt-0.5 text-sm font-semibold text-primary-800">16.30</span>
                        <x-icon name="selesai" class="h-5 w-5 text-primary-700" />
                        <div><p class="font-medium">Periksa tugas berikutnya</p><p class="mt-1 text-sm text-muted">Tugas dapat dilihat lagi di kalender</p></div>
                    </li>
                </ol>
            </aside>
        </section>

        <section class="border-y border-line bg-white">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 md:grid-cols-3">
                <div><h2 class="inline-flex items-center gap-2 font-semibold"><x-icon name="tugas" class="h-5 w-5" />Catat tugas dengan jelas</h2><p class="mt-2 text-sm leading-6 text-muted">Simpan judul, kategori, status, dan tenggat agar detail penting tidak tercecer.</p></div>
                <div><h2 class="inline-flex items-center gap-2 font-semibold"><x-icon name="bentrok" class="h-5 w-5" />Atur waktu tanpa bentrok</h2><p class="mt-2 text-sm leading-6 text-muted">Tentukan jam mulai dan selesai. Jika ada jadwal bertabrakan, Anda akan mendapat peringatan.</p></div>
                <div><h2 class="inline-flex items-center gap-2 font-semibold"><x-icon name="kalender" class="h-5 w-5" />Lihat rencana beberapa hari</h2><p class="mt-2 text-sm leading-6 text-muted">Periksa agenda harian, tugas mendatang, dan kalender bulanan sesuai zona waktu Anda.</p></div>
            </div>
        </section>
    </main>

    <footer class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-8 text-sm text-muted sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <span class="font-semibold text-ink">Jadwal Harian</span>
        <span>Rencana yang jelas, satu hari pada satu waktu.</span>
    </footer>
</body>
</html>
