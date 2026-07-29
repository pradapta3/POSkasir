<?php

namespace App\Listeners;

use App\Contracts\PaymentGatewayInterface;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\TransactionCheckedOut;
use App\Services\Payment\Exceptions\PaymentGatewayException;
use Illuminate\Support\Facades\Log;

/**
 * Runs synchronously (not queued) because the cashier needs the QR code on
 * screen immediately after tapping "Confirm Payment" — unlike the WhatsApp
 * invoice job, this can't be deferred to a background queue.
 *
 * A gateway failure here is logged and swallowed rather than re-thrown: the
 * sale itself must still complete even if Midtrans is unreachable, so the
 * cashier can fall back to cash instead of losing the transaction.
 */
class GenerateQrisCode
{
    public function __construct(private readonly PaymentGatewayInterface $gateway)
    {
    }

    public function handle(TransactionCheckedOut $event): void
    {
        $transaction = $event->transaction;

        $isQrisPayment = in_array($transaction->payment_method, [
            PaymentMethod::QRIS->value,
            PaymentMethod::GOPAY->value,
        ], true);

        if (! $isQrisPayment || $transaction->payment_status !== PaymentStatus::PENDING) {
            return;
        }

        try {
            $result = $this->gateway->chargeQris($transaction);
        } catch (PaymentGatewayException $e) {
            Log::error('QRIS generation failed for transaction '.$transaction->invoice_number, [
                'message' => $e->getMessage(),
            ]);

            return;
        }

        $transaction->update([
            'qris_payload' => $result->qrString,
            'qris_url' => $result->qrImageUrl,
            'payment_gateway_reference' => $result->referenceId,
        ]);
    }
}
