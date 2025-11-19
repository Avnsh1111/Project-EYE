<div>
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <span class="auth-logo-icon">📸</span>
                <span>Avinash-EYE</span>
            </div>
            <h1 class="auth-title">Reset password</h1>
            <p class="auth-subtitle">Enter your email to receive a password reset link</p>
        </div>

        @if ($status)
            <div class="alert alert-success">
                <span class="material-symbols-outlined">check_circle</span>
                {{ $status }}
            </div>
        @endif

        <form wire:submit="sendResetLink">
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

            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="sendResetLink">Send reset link</span>
                <span wire:loading wire:target="sendResetLink">
                    <span style="display: inline-block; width: 16px; height: 16px; border: 2px solid white; border-top-color: transparent; border-radius: 50%; animation: spin 0.6s linear infinite;"></span>
                    Sending...
                </span>
            </button>
        </form>

        <div class="auth-links">
            <a wire:navigate href="{{ route('login') }}">Back to login</a>
        </div>
    </div>

    <style>
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</div>
