<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use Illuminate\Database\Eloquent\Model;
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
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'is_active' => 'boolean',
            'status' => CompanyStatus::class,
            'approved_at' => 'datetime',
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
}
