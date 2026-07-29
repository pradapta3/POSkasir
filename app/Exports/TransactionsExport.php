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
    ) {
    }

    public function query(): Builder
    {
        return Transaction::query()
            ->with(['user', 'customer', 'items'])
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
            $transaction->payment_method ? PaymentMethod::from($transaction->payment_method)->label() : '-',
            $transaction->payment_status->label(),
            $transaction->status->label(),
        ];
    }
}
