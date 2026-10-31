<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Jadwal</title>
    
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100 text-gray-800">

    <!-- NAVBAR -->
    <nav class="relative bg-white shadow">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <!-- Logo -->
            <div class="flex items-center space-x-4">
                <a href="{{ route('user.dashboard') }}">
                    <svg class="h-7 w-7 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14H3V6a2 2 0 0 1 2-2Z" />
                    </svg>
                </a>
                <span class="text-xl font-bold">Sistem Jadwal</span>
            </div>

            <!-- Navigasi -->
            <div class="hidden md:flex space-x-6">
                <a href="{{ route('user.dashboard') }}" class="hover:text-blue-500 font-medium">Beranda</a>
                <a href="{{ route('user.tasks.index') }}" class="hover:text-blue-500 font-medium">Tugas</a>
                <a href="{{ route('user.calendar') }}" class="hover:text-blue-500 font-medium">Kalender</a>
           
            </div>

            <button id="mobileNavigationToggle" type="button" class="inline-flex min-h-11 items-center rounded border px-3 text-gray-700 md:hidden" aria-controls="mobileNavigationMenu" aria-expanded="false">
                <span class="sr-only">Buka navigasi</span>
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <!-- Auth Links -->
            @if (Route::has('login'))
            <div class="flex items-center space-x-6">
                @auth
                    <!-- Dropdown Pengguna -->
                    <div class="relative">
                        <button id="dropdownToggle" class="flex items-center text-lg font-medium hover:text-blue-500 focus:outline-none">
                            {{ Auth::user()->name }}
                            <svg class="ml-2 h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06 0L10 10.92l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.23 8.27a.75.75 0 010-1.06z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div id="dropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-2 z-50">
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Profil</a>
                            <a href="{{ route('user.dashboard') }}" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Dasbor</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-gray-800 hover:bg-gray-100">Keluar</button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" 
                       class="text-gray-800 hover:text-gray-600 transition-colors duration-200 font-medium">
                       MASUK
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" 
                           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200 font-medium">
                           DAFTAR
                        </a>
                    @endif
                @endauth
            </div>
            @endif
        </div>

        <div id="mobileNavigationMenu" class="absolute left-0 right-0 top-full z-40 hidden border-t bg-white px-4 py-2 shadow-md md:hidden">
            <a href="{{ route('user.dashboard') }}" class="block rounded px-3 py-3 hover:bg-gray-100">Beranda</a>
            <a href="{{ route('user.tasks.index') }}" class="block rounded px-3 py-3 hover:bg-gray-100">Tugas</a>
            <a href="{{ route('user.calendar') }}" class="block rounded px-3 py-3 hover:bg-gray-100">Kalender</a>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="container mx-auto px-4 py-10 min-h-screen">
        @yield('content')
    </main>

    <!-- FOOTER -->
    <footer class="bg-gray-800 text-white py-8 mt-16">
        <div class="container mx-auto grid grid-cols-1 md:grid-cols-3 gap-8 px-4">
            <!-- Tentang Kami -->
            <div>
                <h3 class="text-xl font-semibold mb-2">Tentang Sistem Jadwal</h3>
                <p class="text-gray-400 text-sm leading-relaxed">
                    Sistem Jadwal membantu Anda mengorganisir dan mengelola semua kegiatan dengan mudah dan efisien. Rencanakan hari Anda dengan lebih baik!
                </p>
            </div>

            <!-- Tautan Cepat -->
            <div>
                <h3 class="text-xl font-semibold mb-2">Tautan Cepat</h3>
                <ul class="text-gray-400 space-y-2">
                    <li><a href="{{ route('user.dashboard') }}" class="hover:text-white">Beranda</a></li>
                
                </ul>
            </div>

            <!-- Hubungi Kami -->
            <div>
                <h3 class="text-xl font-semibold mb-2">Hubungi Kami</h3>
                <p class="text-gray-400"><strong>Email:</strong> info@sistemjadwal.com</p>
                <p class="text-gray-400"><strong>Telepon:</strong> +62 123 456 7890</p>
                <p class="text-gray-400"><strong>Alamat:</strong> Jl. Teknologi No. 123, Bandung, Indonesia</p>
            </div>
        </div>
    </footer>

    <!-- DROPDOWN SCRIPT -->
    <script>
        const toggle = document.getElementById('dropdownToggle');
        const menu = document.getElementById('dropdownMenu');

        if (toggle && menu) {
            toggle.addEventListener('click', () => {
                menu.classList.toggle('hidden');
            });

            document.addEventListener('click', (e) => {
                if (!toggle.contains(e.target) && !menu.contains(e.target)) {
                    menu.classList.add('hidden');
                }
            });
        }

        const mobileNavigationToggle = document.getElementById('mobileNavigationToggle');
        const mobileNavigationMenu = document.getElementById('mobileNavigationMenu');

        if (mobileNavigationToggle && mobileNavigationMenu) {
            mobileNavigationToggle.addEventListener('click', () => {
                const isExpanded = mobileNavigationToggle.getAttribute('aria-expanded') === 'true';
                mobileNavigationToggle.setAttribute('aria-expanded', String(!isExpanded));
                mobileNavigationMenu.classList.toggle('hidden', isExpanded);
            });
        }
    </script>

</body>
</html>