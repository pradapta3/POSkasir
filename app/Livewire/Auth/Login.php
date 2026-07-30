<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $this->ensureIsNotRateLimited();

        // is_active is included as a credential condition (not checked after
        // the fact) so a deactivated account fails exactly like a wrong
        // password — it doesn't leak whether the account exists but is disabled.
        $credentials = [
            'email' => $this->email,
            'password' => $this->password,
            'is_active' => true,
        ];

        if (! Auth::attempt($credentials, $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi salah, atau akun ini telah dinonaktifkan.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        request()->session()->regenerate();

        $user = Auth::user();
        $user->update(['last_login_at' => now()]);

        // A Platform Admin has no operational store to land in — their
        // company_id is just an FK anchor (see DatabaseSeeder). Anyone
        // else lands on pos.terminal, which itself bounces a
        // pending/rejected company to company.pending via the 'approved'
        // route middleware.
        $this->redirectRoute($user->isPlatformAdmin() ? 'platform.companies' : 'pos.terminal', navigate: true);
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), maxAttempts: 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$seconds} detik.",
        ]);
    }

    private function throttleKey(): string
    {
        return Str::lower($this->email).'|'.request()->ip();
    }

    public function render()
    {
        return view('livewire.auth.login')->layoutData(['title' => 'Masuk']);
    }
}
