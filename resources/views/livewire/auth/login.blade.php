<div class="min-h-screen flex">
    <!-- Left Section - Branding (Hidden on mobile) -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-primary-600 via-primary-700 to-primary-900 relative overflow-hidden">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0icmdiYSgyNTUsMjU1LDI1NSwwLjAzKSIgc3Ryb2tlLXdpZHRoPSIxIi8+PC9wYXR0ZXJuPjwvZGVmcz48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJ1cmwoI2dyaWQpIi8+PC9zdmc+')] opacity-20"></div>
        
        <div class="relative z-10 flex flex-col justify-between p-12 text-white w-full">
            <!-- Logo -->
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-white/10 backdrop-blur-sm rounded-xl flex items-center justify-center border border-white/20">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </div>
                <span class="text-2xl font-display font-semibold">Avinash-EYE</span>
            </div>

            <!-- Central Content -->
            <div class="max-w-md">
                <h1 class="text-5xl font-display font-bold mb-6 leading-tight">
                    Intelligent Media Analysis Platform
                </h1>
                <p class="text-xl text-white/80 leading-relaxed mb-8">
                    Powered by AI to organize, search, and understand your photo library like never before.
                </p>
                <div class="flex flex-wrap gap-4">
                    <div class="flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full border border-white/20">
                        <span class="material-symbols-outlined text-sm">psychology</span>
                        <span class="text-sm font-medium">AI-Powered</span>
                    </div>
                    <div class="flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full border border-white/20">
                        <span class="material-symbols-outlined text-sm">bolt</span>
                        <span class="text-sm font-medium">Lightning Fast</span>
                    </div>
                    <div class="flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full border border-white/20">
                        <span class="material-symbols-outlined text-sm">lock</span>
                        <span class="text-sm font-medium">Secure</span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-white/60 text-sm">
                © 2025 Avinash-EYE. Enterprise Media Management Solution.
            </div>
        </div>
    </div>

    <!-- Right Section - Login Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-12 bg-surface-variant">
        <div class="w-full max-w-md">
            <!-- Mobile Logo -->
            <div class="lg:hidden flex items-center justify-center gap-3 mb-8">
                <div class="w-10 h-10 bg-primary-600 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </div>
                <span class="text-2xl font-display font-semibold text-gray-900">Avinash-EYE</span>
            </div>

            <!-- Login Card -->
            <div class="bg-white rounded-3xl shadow-md3-3 p-8 sm:p-10 animate-slide-up">
                <!-- Header -->
                <div class="mb-8">
                    <h2 class="text-3xl font-display font-bold text-gray-900 mb-2">
                        Welcome back
                    </h2>
                    <p class="text-base text-gray-600">
                        Sign in to access your media library
                    </p>
                </div>

                @if (session('status'))
                    <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-r-lg animate-fade-in">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-green-600">check_circle</span>
                            <span class="text-sm text-green-800">{{ session('status') }}</span>
                        </div>
                    </div>
                @endif

                <form wire:submit="login" class="space-y-6">
                    <!-- Email Field -->
                    <div class="space-y-2">
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            Email address
                        </label>
                        <div class="relative">
                            <input 
                                type="email" 
                                id="email"
                                wire:model="email" 
                                class="w-full px-4 py-3 bg-surface-variant border-2 border-outline rounded-xl text-gray-900 placeholder-gray-400 
                                       focus:outline-none focus:border-primary-600 focus:ring-4 focus:ring-primary-50 
                                       transition-all duration-200 @error('email') border-red-500 bg-red-50 @enderror"
                                placeholder="you@example.com"
                                autocomplete="email"
                                autofocus
                            >
                            <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
                                <span class="material-symbols-outlined text-gray-400">mail</span>
                            </div>
                        </div>
                        @error('email')
                            <div class="flex items-center gap-2 text-red-600 text-sm animate-fade-in">
                                <span class="material-symbols-outlined text-base">error</span>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror
                    </div>

                    <!-- Password Field -->
                    <div class="space-y-2">
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Password
                        </label>
                        <div class="relative">
                            <input 
                                type="{{ $showPassword ? 'text' : 'password' }}" 
                                id="password"
                                wire:model="password" 
                                class="w-full px-4 py-3 bg-surface-variant border-2 border-outline rounded-xl text-gray-900 placeholder-gray-400 
                                       focus:outline-none focus:border-primary-600 focus:ring-4 focus:ring-primary-50 
                                       transition-all duration-200 @error('password') border-red-500 bg-red-50 @enderror"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                            >
                            <button 
                                type="button"
                                wire:click="togglePasswordVisibility"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors duration-200"
                            >
                                <span class="material-symbols-outlined">{{ $showPassword ? 'visibility_off' : 'visibility' }}</span>
                            </button>
                        </div>
                        @error('password')
                            <div class="flex items-center gap-2 text-red-600 text-sm animate-fade-in">
                                <span class="material-symbols-outlined text-base">error</span>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror
                    </div>

                    <!-- Remember & Forgot -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input 
                                type="checkbox" 
                                id="remember"
                                wire:model="remember"
                                class="w-5 h-5 text-primary-600 border-2 border-outline rounded focus:ring-2 focus:ring-primary-500 transition-all duration-200 cursor-pointer"
                            >
                            <span class="text-sm text-gray-700 group-hover:text-gray-900 transition-colors duration-200">
                                Remember me
                            </span>
                        </label>
                        <a wire:navigate href="{{ route('forgot-password') }}" 
                           class="text-sm font-medium text-primary-600 hover:text-primary-700 transition-colors duration-200">
                            Forgot password?
                        </a>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        class="w-full bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white font-medium py-3.5 px-6 rounded-xl 
                               shadow-md3-2 hover:shadow-md3-3 transition-all duration-200 
                               focus:outline-none focus:ring-4 focus:ring-primary-200
                               disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-primary-600 disabled:hover:shadow-md3-2
                               flex items-center justify-center gap-2 group"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="login" class="flex items-center gap-2">
                            <span>Sign in</span>
                            <span class="material-symbols-outlined group-hover:translate-x-1 transition-transform duration-200">arrow_forward</span>
                        </span>
                        <span wire:loading wire:target="login" class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Signing in...</span>
                        </span>
                    </button>
                </form>

                <!-- Divider -->
                <div class="relative my-8">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-outline"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-white text-gray-500">New to Avinash-EYE?</span>
                    </div>
                </div>

                <!-- Sign Up Link -->
                <a wire:navigate href="{{ route('register') }}" 
                   class="block w-full text-center py-3.5 px-6 border-2 border-outline hover:border-primary-600 
                          text-primary-600 font-medium rounded-xl transition-all duration-200 
                          hover:bg-primary-50 focus:outline-none focus:ring-4 focus:ring-primary-200">
                    Create an account
                </a>
            </div>

            <!-- Security Notice -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-500 flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-base">verified_user</span>
                    <span>Protected by enterprise-grade security</span>
                </p>
            </div>
        </div>
    </div>
</div>
