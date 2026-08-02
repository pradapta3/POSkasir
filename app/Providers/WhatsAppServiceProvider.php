<?php

namespace App\Providers;

use App\Contracts\WhatsAppGatewayInterface;
use App\Events\TransactionCheckedOut;
use App\Listeners\QueueWhatsAppInvoice;
use App\Services\WhatsApp\FonnteGateway;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class WhatsAppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WhatsAppGatewayInterface::class, fn () => new FonnteGateway(
            token: (string) config('fonnte.token'),
        ));
    }

    public function boot(): void
    {
        Event::listen(TransactionCheckedOut::class, QueueWhatsAppInvoice::class);
    }
}
