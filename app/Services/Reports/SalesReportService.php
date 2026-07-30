<?php

namespace App\Services\Reports;

use App\Models\ProductStock;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Read-side aggregate queries for the sales dashboard and Excel export.
 * $companyId is passed explicitly everywhere rather than relying on
 * Transaction/TransactionItem's ambient CompanyScope: several of these
 * queries originate from TransactionItem (which has no scope of its own)
 * and reach transactions via a raw join, so the automatic scope on
 * Transaction never gets a chance to apply — an implicit-scoping bug
 * would leak another company's sales data into a manager's dashboard.
 * All figures are computed with SQL aggregates (not by loading transactions
 * into PHP and summing collections) so this stays fast as sales volume
 * grows, and are scoped to `payment_status = paid` — a pending QRIS sale
 * isn't revenue yet.
 */
class SalesReportService
{
    public function summary(int $companyId, CarbonInterface $from, CarbonInterface $to): array
    {
        $paidQuery = fn () => Transaction::query()
            ->where('company_id', $companyId)
            ->where('payment_status', 'paid')
            ->whereBetween('paid_at', [$from, $to]);

        $revenue = (float) $paidQuery()->sum('grand_total');
        $transactionCount = $paidQuery()->count();
        $grossProfit = (float) ($this->grossProfitQuery($companyId, $from, $to)->value('profit') ?? 0);

        return [
            'revenue' => $revenue,
            'grossProfit' => $grossProfit,
            'transactionCount' => $transactionCount,
            'averageOrderValue' => $transactionCount > 0 ? round($revenue / $transactionCount, 2) : 0.0,
        ];
    }

    public function dailySales(int $companyId, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return Transaction::query()
            ->where('company_id', $companyId)
            ->where('payment_status', 'paid')
            ->whereBetween('paid_at', [$from, $to])
            ->selectRaw('DATE(paid_at) as date, SUM(grand_total) as revenue, COUNT(*) as transactions')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    public function topProducts(int $companyId, CarbonInterface $from, CarbonInterface $to, int $limit = 5): Collection
    {
        return TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.company_id', $companyId)
            ->where('transactions.payment_status', 'paid')
            ->whereBetween('transactions.paid_at', [$from, $to])
            ->selectRaw('transaction_items.product_name, SUM(transaction_items.quantity) as quantity_sold, SUM(transaction_items.subtotal) as revenue, SUM((transaction_items.price - transaction_items.cost_price) * transaction_items.quantity) as profit')
            ->groupBy('transaction_items.product_name')
            ->orderByDesc('quantity_sold')
            ->limit($limit)
            ->get();
    }

    /**
     * Stock is per-outlet, so "low stock" is really "low at this specific
     * branch" — each row names both the product and the outlet it's
     * running low at, since the same item can be fine at one branch and
     * nearly out at another.
     */
    public function lowStockProducts(int $companyId, int $limit = 10): Collection
    {
        return ProductStock::query()
            ->join('products', 'products.id', '=', 'product_stocks.product_id')
            ->join('outlets', 'outlets.id', '=', 'product_stocks.outlet_id')
            ->where('products.company_id', $companyId)
            ->where('products.is_active', true)
            ->whereColumn('product_stocks.quantity', '<=', 'product_stocks.low_stock_threshold')
            ->orderBy('product_stocks.quantity')
            ->limit($limit)
            ->select(['products.name as product_name', 'outlets.name as outlet_name', 'product_stocks.quantity'])
            ->get();
    }

    private function grossProfitQuery(int $companyId, CarbonInterface $from, CarbonInterface $to)
    {
        return TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.company_id', $companyId)
            ->where('transactions.payment_status', 'paid')
            ->whereBetween('transactions.paid_at', [$from, $to])
            ->selectRaw('SUM((transaction_items.price - transaction_items.cost_price) * transaction_items.quantity) as profit');
    }
}
