<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Transaction;
use App\Services\Payment\DTO\QrisChargeResult;
use App\Services\Payment\Exceptions\PaymentGatewayException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Midtrans Core API integration for Dynamic QRIS.
 *
 * Both the "QRIS" and "GoPay" payment options in the POS terminal route
 * through this same payment_type=qris charge: QRIS is Indonesia's unified
 * QR standard, and GoPay (like OVO, Dana, ShopeePay) can scan a standard
 * QRIS code natively — so one gateway call serves both UI options without
 * a separate GoPay-specific integration.
 */
class PaymentService implements PaymentGatewayInterface
{
    private const QRIS_EXPIRY_MINUTES = 15;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $serverKey,
    ) {
    }

    public function chargeQris(Transaction $transaction): QrisChargeResult
    {
        $response = Http::withBasicAuth($this->serverKey, '')
            ->acceptJson()
            ->asJson()
            ->post("{$this->baseUrl}/v2/charge", [
                'payment_type' => 'qris',
                'transaction_details' => [
                    'order_id' => $transaction->invoice_number,
                    'gross_amount' => (int) round((float) $transaction->grand_total),
                ],
                'qris' => [
                    'acquirer' => 'gopay',
                ],
                'expiry' => [
                    'unit' => 'minute',
                    'duration' => self::QRIS_EXPIRY_MINUTES,
                ],
            ]);

        if ($response->failed()) {
            Log::error('Midtrans QRIS charge failed', [
                'invoice' => $transaction->invoice_number,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new PaymentGatewayException(
                'Failed to generate QRIS code: '.($response->json('status_message') ?? 'Unknown gateway error.')
            );
        }

        $data = $response->json();

        if (empty($data['qr_string'])) {
            throw new PaymentGatewayException('Gateway response missing qr_string.');
        }

        $qrImageUrl = collect($data['actions'] ?? [])
            ->firstWhere('name', 'generate-qr-code')['url'] ?? null;

        $expiresAt = isset($data['expiry_time'])
            ? CarbonImmutable::parse($data['expiry_time'])
            : CarbonImmutable::now()->addMinutes(self::QRIS_EXPIRY_MINUTES);

        return new QrisChargeResult(
            referenceId: $data['transaction_id'],
            qrString: $data['qr_string'],
            qrImageUrl: $qrImageUrl,
            expiresAt: $expiresAt,
        );
    }

    public function verifySignature(array $payload): bool
    {
        $expected = hash('sha512',
            ($payload['order_id'] ?? '')
            .($payload['status_code'] ?? '')
            .($payload['gross_amount'] ?? '')
            .$this->serverKey
        );

        return hash_equals($expected, $payload['signature_key'] ?? '');
    }

    public function isPaid(array $payload): bool
    {
        $status = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? 'accept';

        return in_array($status, ['capture', 'settlement'], true) && $fraudStatus === 'accept';
    }

    public function extractOrderId(array $payload): string
    {
        if (empty($payload['order_id'])) {
            throw new PaymentGatewayException('Webhook payload missing order_id.');
        }

        return $payload['order_id'];
    }
}
