<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Automatically restricts every query on a BelongsToCompany model to the
 * logged-in user's company — this is the single mechanism every tenant
 * data-isolation guarantee in the app rests on. Applied via the
 * BelongsToCompany trait, not attached to models directly.
 *
 * No-ops outside an authenticated context (CLI, seeders, queued jobs) —
 * those call sites must pass company_id explicitly instead of relying on
 * this scope; see Setting::get()'s $companyId parameter for the pattern.
 */
class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if ($companyId = Auth::user()?->company_id) {
            $builder->where($model->getTable().'.company_id', $companyId);
        }
    }
}
