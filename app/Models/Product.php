<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'category_id',
        'name',
        'sku',
        'barcode',
        'description',
        'unit',
        'cost_price',
        'selling_price',
        'image_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Catalog data (name, price, SKU) is shared across a company's
     * outlets; the quantity on hand is not — each outlet has its own row
     * here. Use stockAt()/quantityAt() rather than reading a single
     * product-level stock number, which no longer exists.
     */
    public function productStocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    public function stockAt(Outlet|int $outlet): ?ProductStock
    {
        $outletId = $outlet instanceof Outlet ? $outlet->id : $outlet;

        return $this->relationLoaded('productStocks')
            ? $this->productStocks->firstWhere('outlet_id', $outletId)
            : $this->productStocks()->where('outlet_id', $outletId)->first();
    }

    public function quantityAt(Outlet|int $outlet): int
    {
        return $this->stockAt($outlet)?->quantity ?? 0;
    }

    public function isLowStockAt(Outlet|int $outlet): bool
    {
        return $this->stockAt($outlet)?->isLowStock() ?? false;
    }

    protected function profitMargin(): Attribute
    {
        return Attribute::get(fn () => $this->selling_price - $this->cost_price);
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->image_path ? Storage::disk('public')->url($this->image_path) : null
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('sku', $term)
                ->orWhere('barcode', $term);
        });
    }
}
