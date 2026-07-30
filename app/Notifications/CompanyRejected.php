<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanyRejected extends Notification
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
        $message = (new MailMessage)
            ->subject('Pendaftaran Toko Belum Disetujui — POS Kasir')
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Mohon maaf, pendaftaran toko "'.$this->company->name.'" belum dapat kami setujui.');

        if ($this->company->rejection_reason) {
            $message->line('Alasan: '.$this->company->rejection_reason);
        }

        return $message
            ->line('Silakan hubungi tim kami jika kamu memiliki pertanyaan.')
            ->salutation('Salam, Tim POS Kasir');
    }
}
