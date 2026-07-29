<?php

namespace App\Services\Inventory;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * The single place that mutates Product::stock_quantity and logs a
 * StockMovement for it — used both by sale checkout (stock leaving) and
 * manual admin adjustments (restocks, corrections), so every change to
 * stock on hand goes through one auditable code path instead of two
 * copies of "lock row, compute before/after, write a movement".
 */
class StockAdjustmentService
{
    /**
     * @param  int  $delta  Signed change to apply — negative for stock leaving
     *                      (sale, shrinkage), positive for stock arriving
     *                      (restock, initial stock, upward correction).
     */
    public function adjust(
        int $productId,
        int $delta,
        StockMovementType $type,
        User $actor,
        ?string $notes = null,
        ?Model $reference = null,
    ): StockMovement {
        return DB::transaction(function () use ($productId, $delta, $type, $actor, $notes, $reference) {
            $product = Product::whereKey($productId)->lockForUpdate()->firstOrFail();

            $before = $product->stock_quantity;
            $after = $before + $delta;

            $product->update(['stock_quantity' => $after]);

            return StockMovement::create([
                'product_id' => $product->id,
                'user_id' => $actor->id,
                'type' => $type,
                'quantity' => $delta,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'notes' => $notes,
            ]);
        });
    }
}
