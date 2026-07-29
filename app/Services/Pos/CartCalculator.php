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
     * @return array{subtotal: float, discountAmount: float, taxAmount: float, grandTotal: float}
     */
    public function totals(array $cart, ?DiscountType $discountType, float $discountValue, float $taxPercentage): array
    {
        $subtotal = $this->subtotal($cart);
        $discountAmount = $this->discountAmount($subtotal, $discountType, $discountValue);
        $taxableAmount = $subtotal - $discountAmount;
        $taxAmount = $this->taxAmount($taxableAmount, $taxPercentage);
        $grandTotal = round($taxableAmount + $taxAmount, 2);

        return compact('subtotal', 'discountAmount', 'taxAmount', 'grandTotal');
    }
}
