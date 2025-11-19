<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;

class ForgotPassword extends Component
{
    public $email = '';
    public $status = null;

    protected $rules = [
        'email' => 'required|email',
    ];

    protected $messages = [
        'email.required' => 'Email address is required.',
        'email.email' => 'Please enter a valid email address.',
    ];

    public function sendResetLink()
    {
        $this->validate();

        // Rate limiting
        $key = 'password.reset.' . $this->email . '.' . request()->ip();
        
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('email', "Too many reset attempts. Please try again in {$seconds} seconds.");
            return;
        }

        $status = Password::sendResetLink(
            ['email' => $this->email]
        );

        RateLimiter::hit($key, 60);

        if ($status === Password::RESET_LINK_SENT) {
            $this->status = __($status);
            $this->email = '';
        } else {
            $this->addError('email', __($status));
        }
    }

    public function render()
    {
        return view('livewire.auth.forgot-password')
            ->layout('layouts.auth');
    }
}
