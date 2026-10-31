<x-guest-layout>
    <!-- Header registrasi dengan icon dan deskripsi -->

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <!-- Field Nama dengan icon -->
        <div class="space-y-2">
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Nama Lengkap
            </label>
            <div class="relative">
                <input id="name" 
                       type="text" 
                       name="name" 
                       value="{{ old('name') }}" 
                       required 
                       autofocus 
                       autocomplete="name"
                       class="w-full px-4 py-3 pl-12 bg-white/80 dark:bg-gray-800/80 border border-gray-300 dark:border-gray-500 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 placeholder-gray-500 text-gray-900 dark:text-white backdrop-blur-sm shadow-sm"
                       placeholder="Masukkan nama lengkap">
                
                <!-- Icon user -->
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
            </div>
            <x-input-error :messages="$errors->get('name')" class="mt-1 text-sm text-red-500" />
        </div>

        <!-- Field Email dengan icon -->
        <div class="space-y-2">
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Email Address
            </label>
            <div class="relative">
                <input id="email" 
                       type="email" 
                       name="email" 
                       value="{{ old('email') }}" 
                       required 
                       autocomplete="username"
                       class="w-full px-4 py-3 pl-12 bg-white/80 dark:bg-gray-800/80 border border-gray-300 dark:border-gray-500 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 placeholder-gray-500 text-gray-900 dark:text-white backdrop-blur-sm shadow-sm"
                       placeholder="nama@email.com">
                
                <!-- Icon email -->
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-1 text-sm text-red-500" />
        </div>

        <!-- Password fields dalam grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Password -->
            <div class="space-y-2">
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Password
                </label>
                <div class="relative">
                    <input id="password" 
                           type="password" 
                           name="password" 
                           required 
                           autocomplete="new-password"
                           class="w-full px-4 py-3 pl-12 bg-white/80 dark:bg-gray-800/80 border border-gray-300 dark:border-gray-500 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 placeholder-gray-500 text-gray-900 dark:text-white backdrop-blur-sm shadow-sm"
                           placeholder="Password">
                    
                    <!-- Icon lock -->
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-1 text-sm text-red-500" />
            </div>

            <!-- Confirm Password -->
            <div class="space-y-2">
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Konfirmasi Password
                </label>
                <div class="relative">
                    <input id="password_confirmation" 
                           type="password" 
                           name="password_confirmation" 
                           required 
                           autocomplete="new-password"
                           class="w-full px-4 py-3 pl-12 bg-white/80 dark:bg-gray-800/80 border border-gray-300 dark:border-gray-500 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 placeholder-gray-500 text-gray-900 dark:text-white backdrop-blur-sm shadow-sm"
                           placeholder="Ulangi password">
                    
                    <!-- Icon check -->
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1 text-sm text-red-500" />
            </div>
        </div>

        <!-- Tombol Register -->
        <button type="submit" 
                class="w-full bg-gradient-to-r from-green-600 to-blue-600 hover:from-green-700 hover:to-blue-700 text-white font-semibold py-3 px-4 rounded-xl transition-all duration-300 transform hover:scale-105 hover:shadow-lg focus:ring-4 focus:ring-green-500/25 focus:outline-none flex items-center justify-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
            </svg>
            <span>Buat Akun Sekarang</span>
        </button>

        <!-- Link ke halaman login -->
        <div class="text-center pt-4 border-t border-gray-300 dark:border-gray-500">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Sudah punya akun?
                <a href="{{ route('login') }}" 
                   class="font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors duration-200 hover:underline ml-1">
                    Masuk di sini
                </a>
            </p>
        </div>
    </form>

    <!-- Benefits singkat -->
    <div class="mt-6 pt-6 border-t border-gray-300 dark:border-gray-500">
        <div class="grid grid-cols-3 gap-3 text-center">
            <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <svg class="w-6 h-6 mx-auto text-green-600 dark:text-green-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-xs font-medium text-green-800 dark:text-green-200">Hemat Waktu</p>
            </div>
            
            <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <svg class="w-6 h-6 mx-auto text-blue-600 dark:text-blue-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v6a2 2 0 002 2h6a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                </svg>
                <p class="text-xs font-medium text-blue-800 dark:text-blue-200">Terorganisir</p>
            </div>
            
            <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                <svg class="w-6 h-6 mx-auto text-purple-600 dark:text-purple-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <p class="text-xs font-medium text-purple-800 dark:text-purple-200">Produktif</p>
            </div>
        </div>
    </div>

    <script>
        // Efek focus sederhana untuk input
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input');
            
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
            });

            // Loading state saat submit
            const form = document.querySelector('form');
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalContent = submitBtn.innerHTML;
                
                submitBtn.innerHTML = `
                    <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Membuat akun...</span>
                `;
                submitBtn.disabled = true;
            });
        });
    </script>

    <style>
        /* Glass effect untuk input */
        input {
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        
        /* Focus effect */
        input:focus {
            background: rgba(255, 255, 255, 0.95) !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
        }
        
        .dark input:focus {
            background: rgba(17, 24, 39, 0.95) !important;
        }
        
        /* Smooth transitions */
        * {
            transition: all 0.2s ease;
        }
    </style>
</x-guest-layout>