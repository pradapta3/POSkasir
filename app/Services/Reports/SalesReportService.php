<?php

namespace App\Services\Reports;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Read-side aggregate queries for the sales dashboard and Excel export.
 * All figures are computed with SQL aggregates (not by loading transactions
 * into PHP and summing collections) so this stays fast as sales volume
 * grows, and are scoped to `payment_status = paid` — a pending QRIS sale
 * isn't revenue yet.
 */
class SalesReportService
{
    public function summary(CarbonInterface $from, CarbonInterface $to): array
    {
        $paidQuery = fn () => Transaction::query()
            ->where('payment_status', 'paid')
            ->whereBetween('paid_at', [$from, $to]);

        $revenue = (float) $paidQuery()->sum('grand_total');
        $transactionCount = $paidQuery()->count();
        $grossProfit = (float) ($this->grossProfitQuery($from, $to)->value('profit') ?? 0);

        return [
            'revenue' => $revenue,
            'grossProfit' => $grossProfit,
            'transactionCount' => $transactionCount,
            'averageOrderValue' => $transactionCount > 0 ? round($revenue / $transactionCount, 2) : 0.0,
        ];
    }

    public function dailySales(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return Transaction::query()
            ->where('payment_status', 'paid')
            ->whereBetween('paid_at', [$from, $to])
            ->selectRaw('DATE(paid_at) as date, SUM(grand_total) as revenue, COUNT(*) as transactions')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    public function topProducts(CarbonInterface $from, CarbonInterface $to, int $limit = 5): Collection
    {
        return TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.payment_status', 'paid')
            ->whereBetween('transactions.paid_at', [$from, $to])
            ->selectRaw('transaction_items.product_name, SUM(transaction_items.quantity) as quantity_sold, SUM(transaction_items.subtotal) as revenue, SUM((transaction_items.price - transaction_items.cost_price) * transaction_items.quantity) as profit')
            ->groupBy('transaction_items.product_name')
            ->orderByDesc('quantity_sold')
            ->limit($limit)
            ->get();
    }

    public function lowStockProducts(int $limit = 10): Collection
    {
        return Product::query()
            ->active()
            ->lowStock()
            ->orderBy('stock_quantity')
            ->limit($limit)
            ->get();
    }

    private function grossProfitQuery(CarbonInterface $from, CarbonInterface $to)
    {
        return TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.payment_status', 'paid')
            ->whereBetween('transactions.paid_at', [$from, $to])
            ->selectRaw('SUM((transaction_items.price - transaction_items.cost_price) * transaction_items.quantity) as profit');
    }
}
