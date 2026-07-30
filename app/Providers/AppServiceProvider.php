<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    /**
     * Both notifications ship in English by default and Laravel has no
     * built-in locale for their mail content — overriding toMailUsing()
     * keeps the framework's token/signed-url handling intact while
     * swapping in Indonesian copy that matches the rest of the app.
     */
    public function boot(): void
    {
        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            // Unlike VerifyEmail's callback, ResetPassword's toMailUsing()
            // hands back the raw token, not a resolved URL — the route
            // must be built here or the email links to a bare token.
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject('Atur Ulang Kata Sandi — POS Kasir')
                ->greeting('Halo '.$notifiable->name.',')
                ->line('Kami menerima permintaan untuk mengatur ulang kata sandi akun POS Kasir kamu.')
                ->action('Atur Ulang Kata Sandi', $url)
                ->line('Tautan ini berlaku selama 60 menit.')
                ->line('Jika kamu tidak meminta ini, abaikan email ini — kata sandi kamu tidak akan berubah.')
                ->salutation('Salam, Tim POS Kasir');
        });

        VerifyEmail::toMailUsing(function ($notifiable, string $url) {
            return (new MailMessage)
                ->subject('Verifikasi Alamat Email — POS Kasir')
                ->greeting('Halo '.$notifiable->name.',')
                ->line('Klik tombol di bawah untuk memverifikasi alamat email akun POS Kasir kamu.')
                ->action('Verifikasi Email', $url)
                ->line('Jika kamu tidak membuat akun ini, abaikan email ini.')
                ->salutation('Salam, Tim POS Kasir');
        });
    }
}
