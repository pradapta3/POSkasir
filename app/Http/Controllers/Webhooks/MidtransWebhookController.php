<?php

namespace App\Http\Controllers\Webhooks;

use App\Contracts\PaymentGatewayInterface;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Public, unauthenticated endpoint hit by Midtrans's servers — protected by
 * signature verification (see PaymentService::verifySignature()) rather than
 * Laravel auth. Must be excluded from CSRF verification; see SETUP.md.
 */
class MidtransWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGatewayInterface $gateway): JsonResponse
    {
        $payload = $request->all();

        if (! $gateway->verifySignature($payload)) {
            Log::warning('Rejected Midtrans webhook with invalid signature', [
                'order_id' => $payload['order_id'] ?? null,
            ]);

            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        $orderId = $gateway->extractOrderId($payload);

        $transaction = Transaction::where('invoice_number', $orderId)->first();

        if (! $transaction) {
            Log::warning('Midtrans webhook for unknown invoice', ['order_id' => $orderId]);

            return response()->json(['message' => 'Transaction not found.'], 404);
        }

        // Midtrans may retry notifications; acknowledge idempotently rather
        // than re-processing an already-settled transaction.
        if ($transaction->payment_status === PaymentStatus::PAID) {
            return response()->json(['message' => 'Already processed.']);
        }

        if ($gateway->isPaid($payload)) {
            $transaction->update([
                'payment_status' => PaymentStatus::PAID,
                'paid_amount' => $transaction->grand_total,
                'change_amount' => 0,
                'paid_at' => now(),
            ]);
        } elseif (in_array($payload['transaction_status'] ?? null, ['deny', 'cancel', 'expire'], true)) {
            $transaction->update([
                'payment_status' => PaymentStatus::FAILED,
            ]);
        }

        return response()->json(['message' => 'OK']);
    }
}
