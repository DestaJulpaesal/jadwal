<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lupa Password - Daily Schedule</title>
    
    @vite(['resources/css/app.css'])
    
    <style>
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .glass-effect { background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); }
        .card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.3); }
        .input-field:focus { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1); }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); transition: all 0.3s ease; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4); }
        .animate-float { animation: float 6s ease-in-out infinite; }
        @keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-20px); } }
        .success-alert { background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); }
    </style>
</head>

<body class="min-h-screen gradient-bg flex items-center justify-center p-4">
    <!-- Background Elements -->
    <div class="absolute top-20 left-10 w-20 h-20 bg-white bg-opacity-10 rounded-full animate-pulse"></div>
    <div class="absolute bottom-32 right-10 w-16 h-16 bg-yellow-300 bg-opacity-20 rounded-full animate-float"></div>
    
    <!-- Forgot Password Container -->
    <div class="w-full max-w-md">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="flex justify-center mb-6">
                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center glass-effect animate-float">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 12H9v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.586l4.707-4.707A1 1 0 0111 3h4a2 2 0 012 2v2z"></path>
                    </svg>
                </div>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">Lupa Password?</h1>
            <p class="text-white text-opacity-80">Jangan khawatir, kami akan bantu Anda</p>
        </div>

        <!-- Forgot Password Form -->
        <div class="card rounded-2xl shadow-2xl p-8">
            <!-- Info Message -->
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-blue-800">
                    Masukkan alamat email Anda dan kami akan mengirimkan link untuk reset password yang memungkinkan Anda membuat password baru.
                </p>
            </div>

            <!-- Session Status -->
            <div id="sessionStatus" class="hidden mb-4 p-4 success-alert text-green-700 rounded-lg">
                Link reset password telah dikirim ke email Anda!
            </div>

            <form method="POST" action="/password/email">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">

                <!-- Email Address -->
                <div class="mb-6">
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                        Email Address
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                            </svg>
                        </div>
                        <input 
                            id="email" 
                            type="email" 
                            name="email" 
                            class="input-field w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all"
                            placeholder="masukkan@email.com"
                            required 
                            autofocus
                        />
                    </div>
                    <div id="emailError" class="hidden mt-2 text-sm text-red-600"></div>
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="btn-primary w-full py-3 px-4 text-white font-semibold rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 mb-6"
                    id="submitBtn"
                >
                    <span id="btnText">Kirim Link Reset Password</span>
                    <svg id="spinner" class="hidden animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </form>

            <!-- Back to Login -->
            <div class="text-center">
                <p class="text-gray-600">
                    Ingat password Anda? 
                    <a href="/login" class="text-purple-600 hover:text-purple-800 font-semibold">
                        Kembali ke login
                    </a>
                </p>
            </div>
        </div>

        <!-- Additional Options -->
        <div class="text-center mt-6 space-y-3">
            <a href="/register" class="block text-white text-opacity-80 hover:text-opacity-100 transition-colors">
                Belum punya akun? Daftar sekarang
            </a>
            <a href="/" class="inline-flex items-center text-white text-opacity-80 hover:text-opacity-100 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Kembali ke beranda
            </a>
        </div>
    </div>

    <script>
        // Form submission handling
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const spinner = document.getElementById('spinner');
            const emailInput = document.getElementById('email');
            const emailError = document.getElementById('emailError');
            const sessionStatus = document.getElementById('sessionStatus');
            
            // Clear previous errors
            emailError.classList.add('hidden');
            emailInput.classList.remove('border-red-500');
            sessionStatus.classList.add('hidden');
            
            // Basic validation
            if (!emailInput.value || !isValidEmail(emailInput.value)) {
                emailError.textContent = 'Masukkan alamat email yang valid';
                emailError.classList.remove('hidden');
                emailInput.classList.add('border-red-500');
                return;
            }
            
            // Show loading state
            submitBtn.disabled = true;
            btnText.textContent = 'Mengirim...';
            spinner.classList.remove('hidden');
            
            // Simulate API call
            setTimeout(() => {
                // Show success message
                sessionStatus.classList.remove('hidden');
                
                // Reset button
                submitBtn.disabled = false;
                btnText.textContent = 'Kirim Link Reset Password';
                spinner.classList.add('hidden');
                
                // Clear form
                emailInput.value = '';
            }, 2000);
        });
        
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // Clear error on input
        document.getElementById('email').addEventListener('input', function() {
            const emailError = document.getElementById('emailError');
            if (!emailError.classList.contains('hidden')) {
                emailError.classList.add('hidden');
                this.classList.remove('border-red-500');
            }
        });
    </script>
</body>
</html>