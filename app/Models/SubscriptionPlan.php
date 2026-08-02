<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Platform-wide pricing tiers — every company picks from the same list,
 * so this deliberately has no company_id and no BelongsToCompany (same
 * reasoning as Company itself: it's reference data, not a tenant record).
 */
class SubscriptionPlan extends Model
{
    protected $fillable = ['name', 'slug', 'price_per_month', 'max_outlets', 'max_users', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'price_per_month' => 'decimal:2',
            'max_outlets' => 'integer',
            'max_users' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}
