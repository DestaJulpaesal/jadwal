<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            .gradient-bg {
                background: linear-gradient(135deg, #1e3a8a 0%, #312e81 50%, #1f2937 100%);
            }
            
            .glass-effect {
                backdrop-filter: blur(16px) saturate(180%);
                -webkit-backdrop-filter: blur(16px) saturate(180%);
                background-color: rgba(255, 255, 255, 0.95);
                border: 1px solid rgba(209, 213, 219, 0.3);
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            }
            
            .dark .glass-effect {
                background-color: rgba(17, 24, 39, 0.95);
                border: 1px solid rgba(75, 85, 99, 0.3);
            }

            .floating-animation {
                animation: float 6s ease-in-out infinite;
            }

            @keyframes float {
                0% { transform: translateY(0px); }
                50% { transform: translateY(-20px); }
                100% { transform: translateY(0px); }
            }

            .schedule-pattern {
                background-image: 
                    radial-gradient(circle at 25px 25px, rgba(255,255,255,0.05) 2px, transparent 2px),
                    radial-gradient(circle at 75px 75px, rgba(255,255,255,0.05) 2px, transparent 2px);
                background-size: 100px 100px;
            }
        </style>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <!-- Background with gradient and pattern -->
        <div class="min-h-screen gradient-bg schedule-pattern relative overflow-hidden">
            <!-- Decorative elements -->
            <div class="absolute top-10 left-10 w-72 h-72 bg-white/5 rounded-full blur-xl floating-animation"></div>
            <div class="absolute bottom-10 right-10 w-96 h-96 bg-purple-300/10 rounded-full blur-2xl floating-animation" style="animation-delay: -3s;"></div>
            <div class="absolute top-1/2 left-1/4 w-64 h-64 bg-blue-300/8 rounded-full blur-xl floating-animation" style="animation-delay: -1.5s;"></div>

            <!-- Main content container -->
            <div class="relative z-10 min-h-screen flex flex-col justify-center items-center px-6 py-12">
                <!-- Header section with logo and title -->
                <div class="text-center mb-8">
                    
                    <h1 class="text-4xl font-bold text-black mb-2 drop-shadow-lg">Schedule Manager</h1>
                    <p class="text-black/90 text-lg drop-shadow-md">Kelola jadwal Anda dengan mudah dan efisien</p>
                </div>

                <!-- Main card with glass effect -->
                <div class="w-full max-w-md">
                    <div class="glass-effect rounded-3xl shadow-2xl p-8 border border-white/20">
                        <!-- Welcome message -->
                        <div class="text-center mb-6">
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl mb-4">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white mb-2">Selamat Datang</h2>
                            <p class="text-gray-600 dark:text-gray-300">Masuk untuk mengakses jadwal Anda</p>
                        </div>

                        <!-- Form content slot -->
                        <div class="space-y-6">
                            {{ $slot }}
                        </div>
                    </div>

                    <!-- Features highlight -->
                    <div class="mt-8 grid grid-cols-3 gap-4">
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 bg-white/20 rounded-xl mb-2 backdrop-blur-sm">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <p class="text-white/90 text-sm font-medium">Real-time</p>
                        </div>
                        
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 bg-white/20 rounded-xl mb-2 backdrop-blur-sm">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v6a2 2 0 002 2h6a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                </svg>
                            </div>
                            <p class="text-white/90 text-sm font-medium">Organized</p>
                        </div>
                        
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 bg-white/20 rounded-xl mb-2 backdrop-blur-sm">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <p class="text-white/90 text-sm font-medium">Fast</p>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-12 text-center">
                    <p class="text-black/80 text-sm drop-shadow-md">
                        © 2025 Schedule Manager. Dibuat dengan ❤️ untuk produktivitas yang lebih baik.
                    </p>
                </div>
            </div>
        </div>

        <script>
            // Add some interactive effects
            document.addEventListener('DOMContentLoaded', function() {
                // Add subtle hover effects to form elements
                const formElements = document.querySelectorAll('input, button');
                formElements.forEach(element => {
                    element.addEventListener('mouseenter', function() {
                        this.style.transform = 'translateY(-1px)';
                        this.style.transition = 'transform 0.2s ease';
                    });
                    
                    element.addEventListener('mouseleave', function() {
                        this.style.transform = 'translateY(0)';
                    });
                });
            });
        </script>
    </body>
</html>