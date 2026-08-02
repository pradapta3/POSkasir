<?php

namespace App\Models;

use App\Enums\SubscriptionPaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Deliberately has no BelongsToCompany — a Platform Admin must be able to
 * see every company's requests in one list, and CompanyScope would
 * silently filter that down to just their own anchor company. Any
 * company-side screen must filter by company_id explicitly instead (same
 * pattern User::class already uses, for the same underlying reason).
 */
class SubscriptionPaymentRequest extends Model
{
    protected $fillable = [
        'company_id', 'subscription_plan_id', 'months', 'amount',
        'status', 'notes', 'requested_by', 'confirmed_by', 'confirmed_at', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionPaymentStatus::class,
            'amount' => 'decimal:2',
            'months' => 'integer',
            'confirmed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
