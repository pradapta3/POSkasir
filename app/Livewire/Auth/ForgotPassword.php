<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class ForgotPassword extends Component
{
    public string $email = '';

    public ?string $status = null;

    public function sendResetLink(): void
    {
        $this->status = null;

        $this->validate(['email' => 'required|email']);

        $result = Password::sendResetLink(['email' => $this->email]);

        if ($result === Password::RESET_LINK_SENT) {
            $this->status = __('passwords.sent');
            $this->reset('email');

            return;
        }

        $this->addError('email', __($result));
    }

    public function render()
    {
        return view('livewire.auth.forgot-password')->layoutData(['title' => 'Lupa Kata Sandi']);
    }
}
