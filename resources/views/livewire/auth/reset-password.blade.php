<div>
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <span class="auth-logo-icon">📸</span>
                <span>Avinash-EYE</span>
            </div>
            <h1 class="auth-title">Reset password</h1>
            <p class="auth-subtitle">Enter your new password</p>
        </div>

        @if (session('status'))
            <div class="alert alert-success">
                <span class="material-symbols-outlined">check_circle</span>
                {{ session('status') }}
            </div>
        @endif

        <form wire:submit="resetPassword">
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
                        <span class="material-symbols-outlined text-base">error</span>
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">New password</label>
                <div class="input-group">
                    <input 
                        type="{{ $showPassword ? 'text' : 'password' }}" 
                        id="password"
                        wire:model="password" 
                        class="form-input @error('password') error @enderror"
                        placeholder="Enter new password"
                        autocomplete="new-password"
                    >
                    <span 
                        class="input-icon material-symbols-outlined" 
                        wire:click="togglePasswordVisibility"
                        class="cursor-pointer"
                    >
                        {{ $showPassword ? 'visibility_off' : 'visibility' }}
                    </span>
                </div>
                @error('password')
                    <div class="form-error">
                        <span class="material-symbols-outlined text-base">error</span>
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
                <label for="password_confirmation" class="form-label">Confirm new password</label>
                <div class="input-group">
                    <input 
                        type="{{ $showPasswordConfirmation ? 'text' : 'password' }}" 
                        id="password_confirmation"
                        wire:model="password_confirmation" 
                        class="form-input @error('password_confirmation') error @enderror"
                        placeholder="Confirm new password"
                        autocomplete="new-password"
                    >
                    <span 
                        class="input-icon material-symbols-outlined" 
                        wire:click="togglePasswordConfirmationVisibility"
                        class="cursor-pointer"
                    >
                        {{ $showPasswordConfirmation ? 'visibility_off' : 'visibility' }}
                    </span>
                </div>
                @error('password_confirmation')
                    <div class="form-error">
                        <span class="material-symbols-outlined text-base">error</span>
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="resetPassword">Reset password</span>
                <span wire:loading wire:target="resetPassword">
                    <span class="spinner"></span>
                    Resetting...
                </span>
            </button>
        </form>

        <div class="auth-links">
            <a wire:navigate href="{{ route('login') }}">Back to login</a>
        </div>
    </div>

</div>
