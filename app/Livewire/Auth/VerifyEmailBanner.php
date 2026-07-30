<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Rendered inside layouts.pos for every authenticated page. Non-blocking
 * by design — see User::class docblock — this is just a reminder with a
 * resend button, not a gate on app access.
 */
class VerifyEmailBanner extends Component
{
    public bool $sent = false;

    public function resend(): void
    {
        Auth::user()->sendEmailVerificationNotification();
        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.auth.verify-email-banner');
    }
}
