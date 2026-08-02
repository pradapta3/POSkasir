<?php

namespace App\Http\Middleware;

use App\Enums\CompanyStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks access to the operational app (POS, admin, reports) for a company
 * that isn't cleared to operate right now — still waiting on (or rejected
 * from) Platform Admin approval, suspended, or past its trial/subscription
 * without a confirmed renewal; see Livewire\Auth\Register,
 * Livewire\Platform\Companies\Index, and Livewire\Billing\Index. status,
 * is_active, and the trial/subscription dates are deliberately three
 * separate concerns: status is the one-time pending→approved(→rejected)
 * outcome of registration, is_active is a switch a Platform Admin can flip
 * at any point to suspend a problem account without touching that
 * history, and expiry is just time passing — see
 * Company::accessExpiresAt(). Platform Admin accounts are exempt: their
 * own company_id row is just an FK anchor (users.company_id is NOT NULL),
 * unrelated to their duties.
 */
class EnsureCompanyIsApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isPlatformAdmin()) {
            $company = $user->company;

            if ($company->status !== CompanyStatus::APPROVED || ! $company->is_active || $company->hasExpiredAccess()) {
                return redirect()->route('company.pending');
            }
        }

        return $next($request);
    }
}
