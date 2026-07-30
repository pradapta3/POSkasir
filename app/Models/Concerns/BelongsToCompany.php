<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Deliberately NOT used by the User model — Auth's internal
 * retrieveById() must be able to load a user without already knowing
 * their company_id, or login would be circular. Every model that uses
 * this trait gets automatic read scoping (CompanyScope) and automatic
 * company_id assignment on create.
 */
trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function ($model): void {
            if (! $model->company_id && Auth::check()) {
                $model->company_id = Auth::user()->company_id;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
