<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daily Schedule - Kelola Jadwal Harian Anda</title>
    
    @vite(['resources/css/app.css'])
    
    <!-- Custom Styles -->
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .feature-card {
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .animate-pulse-slow {
            animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>

<body class="min-h-screen gradient-bg">
    <!-- Navigation -->
    <nav class="relative z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <!-- Logo -->
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-xl flex items-center justify-center glass-effect">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-white">Daily Schedule</h1>
                </div>
                
               <!-- Auth Links -->
@if (Route::has('login'))
<div class="flex items-center space-x-6">
    @auth
        <a href="{{ url('/dashboard') }}" 
           class="px-4 py-2 text-white hover:text-gray-200 transition-colors duration-200">
            Dashboard
        </a>
    @else
        <a href="{{ route('login') }}" 
           class="px-4 py-2 text-white hover:text-gray-200 transition-colors duration-200">
            Masuk
        </a>
        
        @if (Route::has('register'))
            <a href="{{ route('register') }}" 
               class="px-6 py-2 bg-white bg-opacity-20 text-white rounded-lg hover:bg-opacity-30 transition-all duration-200 glass-effect">
                Daftar
            </a>
        @endif
    @endauth
</div>
@endif

     
    </nav>

    <!-- Hero Section -->
    <section class="relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="text-center">
                <!-- Hero Icon -->
                <div class="flex justify-center mb-8">
                    <div class="w-24 h-24 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center glass-effect animate-float">
                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                    </div>
                </div>
                
                <!-- Hero Text -->
                <h2 class="text-5xl md:text-6xl font-bold text-white mb-6 leading-tight">
                    Kelola Jadwal Harian
                    <span class="block text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 to-orange-300">
                        Dengan Mudah
                    </span>
                </h2>
                
                <p class="text-xl text-white text-opacity-90 mb-8 max-w-2xl mx-auto">
                    Atur, pantau, dan kelola semua aktivitas harian Anda dalam satu platform yang intuitif dan mudah digunakan.
                </p>
                
                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <a href="/register" class="px-8 py-4 bg-white text-purple-600 font-semibold rounded-xl hover:shadow-lg transform hover:scale-105 transition-all duration-200">
                        Mulai Sekarang
                    </a>
                    <a href="#features" class="px-8 py-4 text-white border-2 border-white border-opacity-30 rounded-xl hover:bg-white hover:bg-opacity-10 transition-all duration-200 glass-effect">
                        Lihat Fitur
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Decorative Elements -->
        <div class="absolute top-20 left-10 w-20 h-20 bg-white bg-opacity-10 rounded-full animate-pulse-slow"></div>
        <div class="absolute bottom-32 right-10 w-16 h-16 bg-yellow-300 bg-opacity-20 rounded-full animate-float"></div>
        <div class="absolute top-1/2 left-1/4 w-3 h-3 bg-white bg-opacity-40 rounded-full animate-pulse"></div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white bg-opacity-5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h3 class="text-4xl font-bold text-white mb-4">Fitur Unggulan</h3>
                <p class="text-xl text-white text-opacity-80">Semua yang Anda butuhkan untuk mengelola jadwal harian</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="feature-card p-8 bg-white bg-opacity-10 rounded-2xl glass-effect">
                    <div class="w-16 h-16 bg-blue-500 bg-opacity-20 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h4 class="text-2xl font-semibold text-white mb-4">Manajemen Waktu</h4>
                    <p class="text-white text-opacity-80">Atur jadwal harian Anda dengan sistem waktu yang fleksibel dan mudah digunakan.</p>
                </div>
                
                <!-- Feature 2 -->
                <div class="feature-card p-8 bg-white bg-opacity-10 rounded-2xl glass-effect">
                    <div class="w-16 h-16 bg-green-500 bg-opacity-20 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h4 class="text-2xl font-semibold text-white mb-4">Tracking Progress</h4>
                    <p class="text-white text-opacity-80">Pantau kemajuan dan pencapaian Anda dengan sistem tracking yang komprehensif.</p>
                </div>
                
                <!-- Feature 3 -->
                <div class="feature-card p-8 bg-white bg-opacity-10 rounded-2xl glass-effect">
                    <div class="w-16 h-16 bg-purple-500 bg-opacity-20 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5-5-5h5v-12"></path>
                        </svg>
                    </div>
                    <h4 class="text-2xl font-semibold text-white mb-4">Sinkronisasi</h4>
                    <p class="text-white text-opacity-80">Akses jadwal Anda dari berbagai perangkat dengan sinkronisasi real-time.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                <div class="p-8">
                    <div class="text-5xl font-bold text-white mb-2">100+</div>
                    <div class="text-white text-opacity-80">Pengguna Aktif</div>
                </div>
                <div class="p-8">
                    <div class="text-5xl font-bold text-white mb-2">1000+</div>
                    <div class="text-white text-opacity-80">Jadwal Dibuat</div>
                </div>
                <div class="p-8">
                    <div class="text-5xl font-bold text-white mb-2">99%</div>
                    <div class="text-white text-opacity-80">Tingkat Kepuasan</div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-white bg-opacity-5">
        <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
            <h3 class="text-4xl font-bold text-white mb-6">Siap Untuk Memulai?</h3>
            <p class="text-xl text-white text-opacity-80 mb-8">
                Bergabunglah dengan ribuan pengguna yang telah merasakan kemudahan mengelola jadwal harian dengan Daily Schedule.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/register" class="px-8 py-4 bg-white text-purple-600 font-semibold rounded-xl hover:shadow-lg transform hover:scale-105 transition-all duration-200">
                    Daftar Gratis
                </a>
                <a href="/login" class="px-8 py-4 text-white border-2 border-white border-opacity-30 rounded-xl hover:bg-white hover:bg-opacity-10 transition-all duration-200 glass-effect">
                    Sudah Punya Akun?
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-12 border-t border-white border-opacity-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center space-x-3 mb-4 md:mb-0">
                    <div class="w-8 h-8 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <span class="text-white font-semibold">Daily Schedule</span>
                </div>
                <div class="text-white text-opacity-60 text-sm">
                    © 2024 Daily Schedule. Semua hak dilindungi.
                </div>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button id="scrollTop" class="fixed bottom-8 right-8 w-12 h-12 bg-white bg-opacity-20 text-white rounded-full glass-effect hover:bg-opacity-30 transition-all duration-200 opacity-0 pointer-events-none">
        <svg class="w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
        </svg>
    </button>

    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Scroll to top button
        const scrollTopBtn = document.getElementById('scrollTop');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollTopBtn.classList.remove('opacity-0', 'pointer-events-none');
                scrollTopBtn.classList.add('opacity-100');
            } else {
                scrollTopBtn.classList.add('opacity-0', 'pointer-events-none');
                scrollTopBtn.classList.remove('opacity-100');
            }
        });

        scrollTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Add some interactive animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe feature cards for animation
        document.querySelectorAll('.feature-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });
    </script>
</body>
</html>