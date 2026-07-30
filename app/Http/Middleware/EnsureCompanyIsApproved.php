<?php

namespace App\Http\Middleware;

use App\Enums\CompanyStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks access to the operational app (POS, admin, reports) for a
 * self-registered company still waiting on — or rejected from — Platform
 * Admin approval; see Livewire\Auth\Register and Livewire\Platform\Companies\Index.
 * Platform Admin accounts are exempt: their own company_id row is just an
 * FK anchor (users.company_id is NOT NULL), unrelated to their duties.
 */
class EnsureCompanyIsApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isPlatformAdmin() && $user->company->status !== CompanyStatus::APPROVED) {
            return redirect()->route('company.pending');
        }

        return $next($request);
    }
}
