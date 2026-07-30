<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanyApproved extends Notification
{
    public function __construct(private readonly Company $company)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Toko Kamu Telah Disetujui — POS Kasir')
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Kabar baik! Toko "'.$this->company->name.'" telah disetujui dan siap digunakan.')
            ->action('Masuk ke POS Kasir', route('login'))
            ->line('Terima kasih telah bergabung dengan POS Kasir.')
            ->salutation('Salam, Tim POS Kasir');
    }
}
