<?php

namespace App\Exports;

use App\Enums\PaymentMethod;
use App\Models\Transaction;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Itemized transaction ledger for financial reconciliation — one row per
 * sale with cost of goods and gross profit broken out, not just revenue.
 */
class TransactionsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        private readonly CarbonInterface $from,
        private readonly CarbonInterface $to,
        private readonly ?int $outletId = null,
    ) {
    }

    public function query(): Builder
    {
        return Transaction::query()
            ->with(['user', 'customer', 'items'])
            ->when($this->outletId, fn ($q) => $q->where('outlet_id', $this->outletId))
            ->whereBetween('created_at', [$this->from, $this->to])
            ->orderBy('created_at');
    }

    public function headings(): array
    {
        return [
            'No. Invoice', 'Tanggal', 'Kasir', 'Pelanggan', 'Jumlah Item', 'Subtotal',
            'Diskon', 'Pajak', 'Total', 'Modal', 'Laba Kotor',
            'Metode Pembayaran', 'Status Pembayaran', 'Status',
        ];
    }

    public function map($transaction): array
    {
        $costOfGoods = $transaction->items->sum(fn ($item) => $item->cost_price * $item->quantity);

        return [
            $transaction->invoice_number,
            $transaction->created_at->format('Y-m-d H:i'),
            $transaction->user->name,
            $transaction->customer->name ?? '-',
            $transaction->items->sum('quantity'),
            (float) $transaction->subtotal,
            (float) $transaction->discount_amount,
            (float) $transaction->tax_amount,
            (float) $transaction->grand_total,
            (float) $costOfGoods,
            $transaction->grossProfit(),
            $this->paymentMethodLabel($transaction->payment_method),
            $transaction->payment_status->label(),
            $transaction->status->label(),
        ];
    }

    /**
     * tryFrom (not from) — PaymentMethod has shrunk over time (GoPay/Kartu/
     * Lainnya were removed once QRIS became a static store-uploaded code),
     * but old transactions still hold those raw string values in the DB.
     * Falls back to the raw value so historical exports don't crash.
     */
    private function paymentMethodLabel(?string $value): string
    {
        if (! $value) {
            return '-';
        }

        return PaymentMethod::tryFrom($value)?->label() ?? ucfirst($value);
    }
}
