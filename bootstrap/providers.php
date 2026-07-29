<?php

use App\Providers\AppServiceProvider;
use App\Providers\PaymentServiceProvider;
use App\Providers\WhatsAppServiceProvider;

return [
    AppServiceProvider::class,
    // WhatsAppServiceProvider must come after PaymentServiceProvider so the
    // QRIS-generation listener runs before the WhatsApp-queueing listener
    // for the same event (matters when QUEUE_CONNECTION=sync).
    PaymentServiceProvider::class,
    WhatsAppServiceProvider::class,
];
