<?php

namespace App\Services\Reports;

use App\Models\ProductStock;
use Illuminate\Support\Collection;

/**
 * Stock-on-hand valuation at cost (quantity × Product::cost_price, the
 * "last cost" PurchasingService keeps in sync — see that class's docblock).
 * Read-side only, same $companyId-passed-explicitly reasoning as
 * SalesReportService: ProductStock has no CompanyScope of its own, so
 * every query here filters through the already-scoped products table by hand.
 */
class InventoryReportService
{
    public function valuationByOutlet(int $companyId, ?int $outletId = null): Collection
    {
        return ProductStock::query()
            ->join('products', 'products.id', '=', 'product_stocks.product_id')
            ->join('outlets', 'outlets.id', '=', 'product_stocks.outlet_id')
            ->where('products.company_id', $companyId)
            ->when($outletId, fn ($q) => $q->where('product_stocks.outlet_id', $outletId))
            ->where('products.is_active', true)
            ->selectRaw('outlets.id as outlet_id, outlets.name as outlet_name, SUM(product_stocks.quantity) as total_units, SUM(product_stocks.quantity * products.cost_price) as total_value')
            ->groupBy('outlets.id', 'outlets.name')
            ->orderBy('outlets.name')
            ->get();
    }

    public function topValueProducts(int $companyId, ?int $outletId = null, int $limit = 10): Collection
    {
        return ProductStock::query()
            ->join('products', 'products.id', '=', 'product_stocks.product_id')
            ->where('products.company_id', $companyId)
            ->when($outletId, fn ($q) => $q->where('product_stocks.outlet_id', $outletId))
            ->where('products.is_active', true)
            ->selectRaw('products.name as product_name, products.sku as product_sku, SUM(product_stocks.quantity) as quantity, products.cost_price, SUM(product_stocks.quantity * products.cost_price) as total_value')
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.cost_price')
            ->havingRaw('SUM(product_stocks.quantity) > 0')
            ->orderByDesc('total_value')
            ->limit($limit)
            ->get();
    }
}
