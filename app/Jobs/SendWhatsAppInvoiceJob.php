<?php

namespace App\Jobs;

use App\Contracts\WhatsAppGatewayInterface;
use App\Enums\PaymentStatus;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Formats the transaction as a WhatsApp invoice and sends it via the bound
 * WhatsAppGatewayInterface. Dispatched by QueueWhatsAppInvoice, never called
 * synchronously from the terminal — this is what keeps checkout from
 * hanging on a third-party API.
 */
class SendWhatsAppInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(public Transaction $transaction)
    {
    }

    public function handle(WhatsAppGatewayInterface $gateway): void
    {
        $transaction = $this->transaction->fresh(['items', 'customer']);

        if (! $transaction || $transaction->whatsapp_notified_at) {
            return;
        }

        $phone = $transaction->customer?->phone;

        if (! $phone) {
            return;
        }

        $message = $this->buildMessage($transaction);

        // A pending non-cash sale with a live QR gets the code as an image
        // with the invoice as its caption; everything else (already paid,
        // or QR generation failed) gets a plain text invoice.
        if ($transaction->payment_status === PaymentStatus::PENDING && $transaction->qris_url) {
            $gateway->sendImage($phone, $transaction->qris_url, $message);
        } else {
            $gateway->sendText($phone, $message);
        }

        $transaction->update(['whatsapp_notified_at' => now()]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('WhatsApp invoice failed permanently for transaction '.$this->transaction->invoice_number, [
            'message' => $exception->getMessage(),
        ]);
    }

    private function buildMessage(Transaction $transaction): string
    {
        // No authenticated user in a queue worker, so Setting's CompanyScope
        // is a no-op here — the company_id must be passed explicitly or this
        // could silently read another company's store settings.
        $companyId = $transaction->company_id;

        $storeName = Setting::get('store_name', companyId: $companyId) ?: 'POS Kasir';

        $lines = [
            "*{$storeName} — Invoice*",
            $transaction->invoice_number,
            $transaction->created_at->format('d M Y, H:i'),
            '',
        ];

        if ($transaction->customer?->name) {
            $lines[] = "Halo {$transaction->customer->name}, berikut rincian belanja kamu:";
            $lines[] = '';
        }

        foreach ($transaction->items as $item) {
            $lines[] = "{$item->quantity}x {$item->product_name} — {$this->rupiah($item->subtotal)}";
        }

        $lines[] = '------------------------------';
        $lines[] = "Subtotal: {$this->rupiah($transaction->subtotal)}";

        if ((float) $transaction->discount_amount > 0) {
            $lines[] = "Diskon: -{$this->rupiah($transaction->discount_amount)}";
        }

        $lines[] = "Pajak ({$transaction->tax_percentage}%): {$this->rupiah($transaction->tax_amount)}";
        $lines[] = "*Total: {$this->rupiah($transaction->grand_total)}*";
        $lines[] = '';

        if ($transaction->payment_status === PaymentStatus::PAID) {
            $lines[] = 'Status: *LUNAS* ✅';
            $lines[] = Setting::get('receipt_footer', companyId: $companyId) ?: 'Terima kasih sudah berbelanja dengan kami!';
        } elseif ($transaction->qris_url) {
            $lines[] = 'Status: *Menunggu Pembayaran*';
            $lines[] = 'Pindai kode QR di atas dengan aplikasi pendukung QRIS (GoPay, OVO, Dana, ShopeePay, m-banking) untuk membayar.';
        } else {
            $lines[] = 'Status: *Menunggu Pembayaran*';
            $lines[] = "Silakan selesaikan pembayaran di kasir dengan menyebutkan nomor invoice {$transaction->invoice_number}.";
        }

        $storeAddress = Setting::get('store_address', companyId: $companyId);
        $storePhone = Setting::get('store_phone', companyId: $companyId);

        if ($storeAddress || $storePhone) {
            $lines[] = '';
            $lines[] = trim("{$storeName}".($storeAddress ? " — {$storeAddress}" : '').($storePhone ? " — {$storePhone}" : ''));
        }

        return implode("\n", $lines);
    }

    private function rupiah(float|string $amount): string
    {
        return 'Rp '.number_format((float) $amount, 0, ',', '.');
    }
}
