<div>
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <span class="auth-logo-icon">📸</span>
                <span>Avinash-EYE</span>
            </div>
            <h1 class="auth-title">Welcome back</h1>
            <p class="auth-subtitle">Sign in to continue to your account</p>
        </div>

        @if (session('status'))
            <div class="alert alert-success">
                <span class="material-symbols-outlined">check_circle</span>
                {{ session('status') }}
            </div>
        @endif

        <form wire:submit="login">
            <div class="form-group">
                <label for="email" class="form-label">Email address</label>
                <input 
                    type="email" 
                    id="email"
                    wire:model="email" 
                    class="form-input @error('email') error @enderror"
                    placeholder="Enter your email"
                    autocomplete="email"
                    autofocus
                >
                @error('email')
                    <div class="form-error">
                        <span class="material-symbols-outlined" style="font-size: 16px;">error</span>
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <input 
                        type="{{ $showPassword ? 'text' : 'password' }}" 
                        id="password"
                        wire:model="password" 
                        class="form-input @error('password') error @enderror"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                    >
                    <span 
                        class="input-icon material-symbols-outlined" 
                        wire:click="togglePasswordVisibility"
                        style="cursor: pointer;"
                    >
                        {{ $showPassword ? 'visibility_off' : 'visibility' }}
                    </span>
                </div>
                @error('password')
                    <div class="form-error">
                        <span class="material-symbols-outlined" style="font-size: 16px;">error</span>
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="form-group">
                <div class="form-checkbox">
                    <input 
                        type="checkbox" 
                        id="remember"
                        wire:model="remember"
                    >
                    <label for="remember">Remember me</label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="login">Sign in</span>
                <span wire:loading wire:target="login">
                    <span style="display: inline-block; width: 16px; height: 16px; border: 2px solid white; border-top-color: transparent; border-radius: 50%; animation: spin 0.6s linear infinite;"></span>
                    Signing in...
                </span>
            </button>
        </form>

        <div class="auth-links">
            <a wire:navigate href="{{ route('forgot-password') }}">Forgot your password?</a>
        </div>
    </div>

    <div class="auth-card" style="text-align: center; padding: 1.5rem;">
        <p style="color: var(--secondary-color); font-size: 0.875rem; margin: 0;">
            Don't have an account? 
            <a wire:navigate href="{{ route('register') }}" style="color: var(--primary-color); text-decoration: none; font-weight: 500;">Sign up</a>
        </p>
    </div>

    <style>
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</div>
