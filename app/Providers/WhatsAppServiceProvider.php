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

    /**
     * Must be registered AFTER PaymentServiceProvider in
     * bootstrap/providers.php: when QUEUE_CONNECTION=sync, dispatching a
     * job runs it inline, so GenerateQrisCode (Phase 3) needs to have
     * already saved qris_url before this listener's job executes and reads
     * the transaction back from the database. See SETUP.md.
     */
    public function boot(): void
    {
        Event::listen(TransactionCheckedOut::class, QueueWhatsAppInvoice::class);
    }
}
