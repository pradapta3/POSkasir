<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * No BelongsToCompany here — this table has no company_id column of its
 * own (company is derivable via product_id/outlet_id, both of which are
 * already company-scoped whenever they're loaded). Access always goes
 * through an already-scoped Product or Outlet.
 */
class ProductStock extends Model
{
    protected $fillable = ['product_id', 'outlet_id', 'quantity', 'low_stock_threshold'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'low_stock_threshold' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function isLowStock(): bool
    {
        return $this->quantity <= $this->low_stock_threshold;
    }
}
