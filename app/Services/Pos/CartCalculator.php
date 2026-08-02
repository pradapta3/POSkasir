<?php

namespace App\Services\Pos;

use App\Enums\DiscountType;

/**
 * Pure, stateless cart math — no DB access — so totals can be unit tested
 * without a database and reused identically by the Livewire component,
 * CheckoutService, and (later) receipt/report generation.
 */
class CartCalculator
{
    public function subtotal(array $cart): float
    {
        return round(array_sum(array_map(
            fn (array $line) => $line['price'] * $line['quantity'],
            $cart
        )), 2);
    }

    public function discountAmount(float $subtotal, ?DiscountType $type, float $value): float
    {
        if (! $type || $value <= 0 || $subtotal <= 0) {
            return 0.0;
        }

        $amount = $type === DiscountType::PERCENTAGE
            ? $subtotal * ($value / 100)
            : $value;

        // Never let a discount exceed the subtotal (would produce a negative total).
        return round(min($amount, $subtotal), 2);
    }

    public function taxAmount(float $taxableAmount, float $taxPercentage): float
    {
        return round($taxableAmount * ($taxPercentage / 100), 2);
    }

    /**
     * $pointsDiscountAmount (a member redeeming loyalty points) stacks with
     * the manual discount but is tracked separately — a sale can have both
     * at once, and reports/receipts need to tell them apart. Capped the
     * same way discountAmount is: never past what's left of the subtotal.
     *
     * @return array{subtotal: float, discountAmount: float, pointsDiscountAmount: float, taxAmount: float, grandTotal: float}
     */
    public function totals(
        array $cart,
        ?DiscountType $discountType,
        float $discountValue,
        float $taxPercentage,
        float $pointsDiscountAmount = 0,
    ): array {
        $subtotal = $this->subtotal($cart);
        $discountAmount = $this->discountAmount($subtotal, $discountType, $discountValue);
        $remainingAfterDiscount = $subtotal - $discountAmount;
        $pointsDiscountAmount = round(max(0, min($pointsDiscountAmount, $remainingAfterDiscount)), 2);
        $taxableAmount = $remainingAfterDiscount - $pointsDiscountAmount;
        $taxAmount = $this->taxAmount($taxableAmount, $taxPercentage);
        $grandTotal = round($taxableAmount + $taxAmount, 2);

        return compact('subtotal', 'discountAmount', 'pointsDiscountAmount', 'taxAmount', 'grandTotal');
    }
}
