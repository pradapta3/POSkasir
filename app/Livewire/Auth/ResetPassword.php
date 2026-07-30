<?php

namespace App\Livewire\Auth;

use Illuminate\Auth\Events\PasswordReset as PasswordResetEvent;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class ResetPassword extends Component
{
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
    }

    // Named resetPassword (not reset) to avoid shadowing Livewire's own
    // Component::reset() helper, which several other components rely on.
    public function resetPassword(): void
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        if ($this->password !== $this->passwordConfirmation) {
            $this->addError('passwordConfirmation', 'Konfirmasi kata sandi tidak cocok.');

            return;
        }

        $status = Password::reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->passwordConfirmation,
                'token' => $this->token,
            ],
            function ($user): void {
                // 'hashed' cast on User::$casts hashes this automatically.
                $user->forceFill(['password' => $this->password])->setRememberToken(Str::random(60));
                $user->save();

                event(new PasswordResetEvent($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            session()->flash('status', __('passwords.reset'));
            $this->redirectRoute('login', navigate: true);

            return;
        }

        $this->addError('email', __($status));
    }

    public function render()
    {
        return view('livewire.auth.reset-password')->layoutData(['title' => 'Atur Ulang Kata Sandi']);
    }
}
