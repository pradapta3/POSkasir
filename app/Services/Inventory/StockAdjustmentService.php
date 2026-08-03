<?php

namespace App\Services\Inventory;

use App\Enums\StockMovementType;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The single place that mutates ProductStock::quantity and logs a
 * StockMovement for it — used both by sale checkout (stock leaving) and
 * manual admin adjustments (restocks, corrections), so every change to
 * stock on hand goes through one auditable code path instead of two
 * copies of "lock row, compute before/after, write a movement". Stock is
 * per-outlet: the same catalog product has an independent quantity at
 * each of a company's branches.
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
        int $outletId,
        int $delta,
        StockMovementType $type,
        User $actor,
        ?string $notes = null,
        ?Model $reference = null,
    ): StockMovement {
        return DB::transaction(function () use ($productId, $outletId, $delta, $type, $actor, $notes, $reference) {
            $stock = ProductStock::query()
                ->where('product_id', $productId)
                ->where('outlet_id', $outletId)
                ->lockForUpdate()
                ->first();

            $before = $stock?->quantity ?? 0;
            $after = $before + $delta;

            // The authoritative floor check — runs under the row lock above,
            // so it's what actually protects against negative stock, not
            // any pre-check a caller does beforehand (those only exist for
            // a friendlier error message; two concurrent requests racing
            // for the last unit only get caught here).
            if ($after < 0) {
                throw new RuntimeException('Stok tidak mencukupi untuk operasi ini.');
            }

            if ($stock) {
                $stock->update(['quantity' => $after]);
            } else {
                ProductStock::create([
                    'product_id' => $productId,
                    'outlet_id' => $outletId,
                    'quantity' => $after,
                ]);
            }

            return StockMovement::create([
                'product_id' => $productId,
                'outlet_id' => $outletId,
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
