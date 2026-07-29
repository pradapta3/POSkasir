<?php

namespace App\Contracts;

use App\Models\Transaction;
use App\Services\Payment\DTO\QrisChargeResult;

/**
 * Abstracts the payment gateway so PaymentService (Midtrans) can be swapped
 * for a different provider (e.g. a direct GoPay Merchant API integration)
 * without touching CheckoutService, the QRIS listener, or the webhook
 * controller — they all depend on this contract, not the concrete gateway.
 */
interface PaymentGatewayInterface
{
    public function chargeQris(Transaction $transaction): QrisChargeResult;

    public function verifySignature(array $payload): bool;

    public function isPaid(array $payload): bool;

    public function extractOrderId(array $payload): string;
}
