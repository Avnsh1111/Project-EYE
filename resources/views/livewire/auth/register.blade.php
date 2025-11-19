<div>
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <span class="auth-logo-icon">📸</span>
                <span>Avinash-EYE</span>
            </div>
            <h1 class="auth-title">Create your account</h1>
            <p class="auth-subtitle">Get started with your free account</p>
        </div>

        <form wire:submit="register">
            <div class="form-group">
                <label for="name" class="form-label">Full name</label>
                <input 
                    type="text" 
                    id="name"
                    wire:model="name" 
                    class="form-input @error('name') error @enderror"
                    placeholder="Enter your full name"
                    autocomplete="name"
                    autofocus
                >
                @error('name')
                    <div class="form-error">
                        <span class="material-symbols-outlined" style="font-size: 16px;">error</span>
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email address</label>
                <input 
                    type="email" 
                    id="email"
                    wire:model="email" 
                    class="form-input @error('email') error @enderror"
                    placeholder="Enter your email"
                    autocomplete="email"
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
                        placeholder="Create a password"
                        autocomplete="new-password"
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
                <div class="password-requirements">
                    <strong>Password must contain:</strong>
                    <ul>
                        <li :class="{ valid: password.length >= 8 }">At least 8 characters</li>
                        <li :class="{ valid: /[a-z]/.test(password) && /[A-Z]/.test(password) }">Both uppercase and lowercase letters</li>
                        <li :class="{ valid: /\d/.test(password) }">At least one number</li>
                        <li :class="{ valid: /[!@#$%^&*(),.?":{}|<>]/.test(password) }">At least one special character</li>
                    </ul>
                </div>
            </div>

            <div class="form-group">
                <label for="password_confirmation" class="form-label">Confirm password</label>
                <div class="input-group">
                    <input 
                        type="{{ $showPasswordConfirmation ? 'text' : 'password' }}" 
                        id="password_confirmation"
                        wire:model="password_confirmation" 
                        class="form-input @error('password_confirmation') error @enderror"
                        placeholder="Confirm your password"
                        autocomplete="new-password"
                    >
                    <span 
                        class="input-icon material-symbols-outlined" 
                        wire:click="togglePasswordConfirmationVisibility"
                        style="cursor: pointer;"
                    >
                        {{ $showPasswordConfirmation ? 'visibility_off' : 'visibility' }}
                    </span>
                </div>
                @error('password_confirmation')
                    <div class="form-error">
                        <span class="material-symbols-outlined" style="font-size: 16px;">error</span>
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="register">Create account</span>
                <span wire:loading wire:target="register">
                    <span style="display: inline-block; width: 16px; height: 16px; border: 2px solid white; border-top-color: transparent; border-radius: 50%; animation: spin 0.6s linear infinite;"></span>
                    Creating account...
                </span>
            </button>
        </form>
    </div>

    <div class="auth-card" style="text-align: center; padding: 1.5rem;">
        <p style="color: var(--secondary-color); font-size: 0.875rem; margin: 0;">
            Already have an account? 
            <a wire:navigate href="{{ route('login') }}" style="color: var(--primary-color); text-decoration: none; font-weight: 500;">Sign in</a>
        </p>
    </div>

    <style>
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('password-updated', () => {
                // Password validation feedback handled by Alpine.js
            });
        });
    </script>
</div>
