<?php

namespace App\Services\Loyalty;

use App\Enums\LoyaltyMovementType;
use App\Models\Customer;
use App\Models\LoyaltyPointMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The single place that mutates Customer::loyalty_points and logs a
 * LoyaltyPointMovement for it — mirrors StockAdjustmentService's shape
 * (lock row, compute before/after, write an auditable movement) for the
 * same reason: every change to a member's balance goes through one path
 * instead of scattered direct updates.
 */
class LoyaltyService
{
    /**
     * @param  int  $delta  Signed change to apply — negative for points
     *                      spent (redeem), positive for points gained
     *                      (earn, upward manual correction).
     *
     * @throws RuntimeException if a negative delta would take the balance below zero.
     */
    public function adjust(
        Customer $customer,
        int $delta,
        LoyaltyMovementType $type,
        User $actor,
        ?string $notes = null,
        ?Model $reference = null,
    ): LoyaltyPointMovement {
        return DB::transaction(function () use ($customer, $delta, $type, $actor, $notes, $reference) {
            $locked = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();

            $before = $locked->loyalty_points;
            $after = $before + $delta;

            if ($after < 0) {
                throw new RuntimeException('Poin tidak mencukupi untuk penukaran ini.');
            }

            $locked->update(['loyalty_points' => $after]);

            return LoyaltyPointMovement::create([
                'customer_id' => $customer->id,
                'user_id' => $actor->id,
                'type' => $type,
                'points' => $delta,
                'points_before' => $before,
                'points_after' => $after,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Turns a plain (non-member) Customer into a loyalty-program member:
     * assigns the next sequential per-company code and starts their point
     * balance accruing. The only place a member_code gets minted — called
     * from CheckoutService (enrollAsMember at checkout) and
     * Admin\Members\Index (enrolling from the back office).
     */
    public function enroll(Customer $customer): Customer
    {
        if ($customer->is_member) {
            return $customer;
        }

        return DB::transaction(function () use ($customer) {
            $locked = Customer::where('company_id', $customer->company_id)
                ->where('member_code', 'like', 'MBR-%')
                ->lockForUpdate()
                ->orderByDesc('member_code')
                ->value('member_code');

            $nextSequence = $locked ? ((int) substr($locked, -4)) + 1 : 1;

            $customer->update([
                'is_member' => true,
                'member_code' => 'MBR-'.str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT),
                'member_since' => now(),
            ]);

            return $customer;
        });
    }
}
