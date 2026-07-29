<?php

namespace App\Providers;

use App\Contracts\PaymentGatewayInterface;
use App\Events\TransactionCheckedOut;
use App\Listeners\GenerateQrisCode;
use App\Services\Payment\PaymentService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayInterface::class, function () {
            $isProduction = (bool) config('midtrans.is_production');

            return new PaymentService(
                baseUrl: $isProduction ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com',
                serverKey: (string) config('midtrans.server_key'),
            );
        });
    }

    public function boot(): void
    {
        Event::listen(TransactionCheckedOut::class, GenerateQrisCode::class);
    }
}
