<?php

namespace App\Services\Purchasing;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Services\Inventory\StockAdjustmentService;
use Illuminate\Support\Facades\DB;

/**
 * Records stock arriving from a supplier: writes the PurchaseOrder header
 * and line items, moves stock in through StockAdjustmentService (so it
 * shares the one auditable mutation path with checkout and manual
 * adjustments — see that class's docblock), and syncs each product's
 * cost_price to what was just paid. That's a "last cost" costing model:
 * simple, and good enough to keep margin reports current without a full
 * FIFO/weighted-average system. Past sales already snapshot their own
 * cost_price on the transaction item, so this never rewrites history.
 */
class PurchasingService
{
    public function __construct(private StockAdjustmentService $stock) {}

    /**
     * @param  array<int, array{product_id: int, quantity: int, unit_cost: float}>  $items
     */
    public function receive(
        int $outletId,
        ?int $supplierId,
        array $items,
        User $actor,
        ?string $notes = null,
    ): PurchaseOrder {
        return DB::transaction(function () use ($outletId, $supplierId, $items, $actor, $notes) {
            $products = Product::whereIn('id', array_column($items, 'product_id'))->get()->keyBy('id');

            $purchaseOrder = PurchaseOrder::create([
                'outlet_id' => $outletId,
                'supplier_id' => $supplierId,
                'user_id' => $actor->id,
                'po_number' => $this->nextPoNumber($outletId),
                'total_amount' => 0,
                'notes' => $notes,
            ]);

            $total = 0;

            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                $subtotal = $item['quantity'] * $item['unit_cost'];
                $total += $subtotal;

                $purchaseOrder->items()->create([
                    'product_id' => $product?->id,
                    'product_name' => $product?->name ?? 'Produk tidak dikenal',
                    'product_sku' => $product?->sku ?? '-',
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'subtotal' => $subtotal,
                ]);

                $this->stock->adjust(
                    productId: $item['product_id'],
                    outletId: $outletId,
                    delta: $item['quantity'],
                    type: StockMovementType::IN,
                    actor: $actor,
                    notes: "Pembelian {$purchaseOrder->po_number}",
                    reference: $purchaseOrder,
                );

                $product?->update(['cost_price' => $item['unit_cost']]);
            }

            $purchaseOrder->update(['total_amount' => $total]);

            return $purchaseOrder;
        });
    }

    /**
     * Sequential per-outlet PO number: PO-YYYYMMDD-0001 — same scheme as
     * Transaction's invoice number, see CheckoutService::nextInvoiceNumber()
     * for the concurrency caveat (fine for typical single/few-terminal use).
     */
    private function nextPoNumber(int $outletId): string
    {
        $prefix = 'PO-'.now()->format('Ymd').'-';

        $lastNumber = PurchaseOrder::where('outlet_id', $outletId)
            ->where('po_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('po_number')
            ->value('po_number');

        $nextSequence = $lastNumber ? ((int) substr($lastNumber, -4)) + 1 : 1;

        return $prefix.str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
    }
}
