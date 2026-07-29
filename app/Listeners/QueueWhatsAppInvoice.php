<?php

namespace App\Listeners;

use App\Events\TransactionCheckedOut;
use App\Jobs\SendWhatsAppInvoiceJob;

/**
 * Queues the WhatsApp invoice whenever a phone number was captured at
 * checkout — covers both "pay later via QRIS" and "just send my receipt
 * digitally" on an already-paid cash sale. The job itself does the actual
 * formatting/sending in the background.
 */
class QueueWhatsAppInvoice
{
    public function handle(TransactionCheckedOut $event): void
    {
        $transaction = $event->transaction;

        if (! $transaction->customer?->phone) {
            return;
        }

        SendWhatsAppInvoiceJob::dispatch($transaction);
    }
}
