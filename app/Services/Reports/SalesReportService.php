<?php

namespace App\Services\Reports;

use App\Models\Outlet;
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
 *
 * $outletId is optional everywhere: null means "every outlet in the
 * company" (a Superadmin/Manager's default view, or the Riwayat screen for
 * an unfiltered look), matching OutletSwitcher's "Semua Outlet" option.
 */
class SalesReportService
{
    public function summary(int $companyId, CarbonInterface $from, CarbonInterface $to, ?int $outletId = null): array
    {
        $paidQuery = fn () => Transaction::query()
            ->where('company_id', $companyId)
            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
            ->where('payment_status', 'paid')
            ->whereBetween('paid_at', [$from, $to]);

        $revenue = (float) $paidQuery()->sum('grand_total');
        $transactionCount = $paidQuery()->count();
        $grossProfit = (float) ($this->grossProfitQuery($companyId, $from, $to, $outletId)->value('profit') ?? 0);

        return [
            'revenue' => $revenue,
            'grossProfit' => $grossProfit,
            'transactionCount' => $transactionCount,
            'averageOrderValue' => $transactionCount > 0 ? round($revenue / $transactionCount, 2) : 0.0,
        ];
    }

    public function dailySales(int $companyId, CarbonInterface $from, CarbonInterface $to, ?int $outletId = null): Collection
    {
        return Transaction::query()
            ->where('company_id', $companyId)
            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
            ->where('payment_status', 'paid')
            ->whereBetween('paid_at', [$from, $to])
            ->selectRaw('DATE(paid_at) as date, SUM(grand_total) as revenue, COUNT(*) as transactions')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    public function topProducts(int $companyId, CarbonInterface $from, CarbonInterface $to, ?int $outletId = null, int $limit = 5): Collection
    {
        return TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.company_id', $companyId)
            ->when($outletId, fn ($q) => $q->where('transactions.outlet_id', $outletId))
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
     * nearly out at another. $outletId narrows to one branch; left null it
     * still shows every branch, just like the other methods here.
     */
    public function lowStockProducts(int $companyId, ?int $outletId = null, int $limit = 10): Collection
    {
        return ProductStock::query()
            ->join('products', 'products.id', '=', 'product_stocks.product_id')
            ->join('outlets', 'outlets.id', '=', 'product_stocks.outlet_id')
            ->where('products.company_id', $companyId)
            ->when($outletId, fn ($q) => $q->where('product_stocks.outlet_id', $outletId))
            ->where('products.is_active', true)
            ->whereColumn('product_stocks.quantity', '<=', 'product_stocks.low_stock_threshold')
            ->orderBy('product_stocks.quantity')
            ->limit($limit)
            ->select(['products.name as product_name', 'outlets.name as outlet_name', 'product_stocks.quantity'])
            ->get();
    }

    /**
     * Left-joined (not inner) so a paid item whose product was later
     * deleted or never had a category still counts toward revenue —
     * it just lands in "Tanpa Kategori" instead of vanishing from the total.
     */
    public function categoryPerformance(int $companyId, CarbonInterface $from, CarbonInterface $to, ?int $outletId = null): Collection
    {
        return TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->leftJoin('products', 'products.id', '=', 'transaction_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('transactions.company_id', $companyId)
            ->when($outletId, fn ($q) => $q->where('transactions.outlet_id', $outletId))
            ->where('transactions.payment_status', 'paid')
            ->whereBetween('transactions.paid_at', [$from, $to])
            ->selectRaw("COALESCE(categories.name, 'Tanpa Kategori') as category_name, SUM(transaction_items.quantity) as quantity_sold, SUM(transaction_items.subtotal) as revenue, SUM((transaction_items.price - transaction_items.cost_price) * transaction_items.quantity) as profit")
            ->groupBy('category_name')
            ->orderByDesc('revenue')
            ->get();
    }

    public function paymentMethodBreakdown(int $companyId, CarbonInterface $from, CarbonInterface $to, ?int $outletId = null): Collection
    {
        return Transaction::query()
            ->where('company_id', $companyId)
            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
            ->where('payment_status', 'paid')
            ->whereBetween('paid_at', [$from, $to])
            ->selectRaw('payment_method, COUNT(*) as transaction_count, SUM(grand_total) as revenue')
            ->groupBy('payment_method')
            ->orderByDesc('revenue')
            ->get();
    }

    public function salesByCashier(int $companyId, CarbonInterface $from, CarbonInterface $to, ?int $outletId = null): Collection
    {
        return Transaction::query()
            ->join('users', 'users.id', '=', 'transactions.user_id')
            ->where('transactions.company_id', $companyId)
            ->when($outletId, fn ($q) => $q->where('transactions.outlet_id', $outletId))
            ->where('transactions.payment_status', 'paid')
            ->whereBetween('transactions.paid_at', [$from, $to])
            ->selectRaw('users.name as cashier_name, COUNT(*) as transaction_count, SUM(transactions.grand_total) as revenue')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('revenue')
            ->get();
    }

    /**
     * Company-wide by design — this report's whole point is comparing
     * outlets against each other, so (unlike every other method here) it
     * takes no $outletId filter. Starts from every outlet the company has
     * (not just ones with sales in range) so a branch with zero activity
     * shows up as Rp0 rather than silently disappearing from the table.
     * Revenue/count and profit come from separate grouped queries (joining
     * both onto one row would multiply transaction counts by item rows) and
     * are merged in PHP.
     */
    public function outletComparison(int $companyId, CarbonInterface $from, CarbonInterface $to): Collection
    {
        $revenue = Transaction::query()
            ->where('company_id', $companyId)
            ->where('payment_status', 'paid')
            ->whereBetween('paid_at', [$from, $to])
            ->selectRaw('outlet_id, COUNT(*) as transaction_count, SUM(grand_total) as revenue')
            ->groupBy('outlet_id')
            ->get()
            ->keyBy('outlet_id');

        $profit = TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.company_id', $companyId)
            ->where('transactions.payment_status', 'paid')
            ->whereBetween('transactions.paid_at', [$from, $to])
            ->selectRaw('transactions.outlet_id, SUM((transaction_items.price - transaction_items.cost_price) * transaction_items.quantity) as profit')
            ->groupBy('transactions.outlet_id')
            ->pluck('profit', 'outlet_id');

        return Outlet::where('company_id', $companyId)
            ->orderBy('name')
            ->get()
            ->map(fn (Outlet $outlet) => (object) [
                'outlet_id' => $outlet->id,
                'outlet_name' => $outlet->name,
                'transaction_count' => (int) ($revenue->get($outlet->id)?->transaction_count ?? 0),
                'revenue' => (float) ($revenue->get($outlet->id)?->revenue ?? 0),
                'profit' => (float) ($profit->get($outlet->id) ?? 0),
            ]);
    }

    private function grossProfitQuery(int $companyId, CarbonInterface $from, CarbonInterface $to, ?int $outletId = null)
    {
        return TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.company_id', $companyId)
            ->when($outletId, fn ($q) => $q->where('transactions.outlet_id', $outletId))
            ->where('transactions.payment_status', 'paid')
            ->whereBetween('transactions.paid_at', [$from, $to])
            ->selectRaw('SUM((transaction_items.price - transaction_items.cost_price) * transaction_items.quantity) as profit');
    }
}
