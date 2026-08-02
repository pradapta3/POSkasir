<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A paying SaaS tenant — one signed-up business, which may run several
 * physical outlets. Deliberately has no CompanyScope of its own (it's the
 * scope's *target*, not a scoped model).
 */
class Company extends Model
{
    protected $fillable = [
        'name', 'slug', 'owner_email', 'trial_ends_at', 'is_active',
        'status', 'rejection_reason', 'approved_at',
        'subscription_plan_id', 'subscription_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'is_active' => 'boolean',
            'status' => CompanyStatus::class,
            'approved_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
        ];
    }

    public function outlets(): HasMany
    {
        return $this->hasMany(Outlet::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(SubscriptionPaymentRequest::class);
    }

    /**
     * Whichever is later: the free trial, or the paid period from the
     * last confirmed payment. Null means never expires — every company
     * that existed before this feature landed keeps working untouched,
     * since neither column was backfilled for them. See
     * EnsureCompanyIsApproved for how this is enforced.
     */
    public function accessExpiresAt(): ?\Illuminate\Support\Carbon
    {
        return collect([$this->trial_ends_at, $this->subscription_ends_at])
            ->filter()
            ->max();
    }

    public function hasExpiredAccess(): bool
    {
        $expiresAt = $this->accessExpiresAt();

        return $expiresAt !== null && $expiresAt->isPast();
    }
}
