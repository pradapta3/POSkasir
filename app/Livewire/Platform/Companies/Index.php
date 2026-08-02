<?php

namespace App\Livewire\Platform\Companies;

use App\Enums\CompanyStatus;
use App\Enums\RoleEnum;
use App\Livewire\Actions\Logout;
use App\Models\Company;
use App\Models\Scopes\CompanyScope;
use App\Notifications\CompanyApproved;
use App\Notifications\CompanyRejected;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The Platform Admin's review queue for self-registered companies — see
 * Auth\Register (creates 'pending') and EnsureCompanyIsApproved (gates the
 * tenant app on the outcome). Deliberately its own minimal layout, not the
 * tenant sidebar: a Platform Admin isn't really that anchor company's
 * admin, even though their user row needs some company_id to satisfy the
 * NOT NULL constraint.
 */
#[Layout('layouts.platform')]
class Index extends Component
{
    public string $statusFilter = 'pending';

    public ?int $rejectingId = null;

    public string $rejectionReason = '';

    #[Computed]
    public function companies(): Collection
    {
        return Company::query()
            // Exclude the platform_admin row itself from every company's
            // user count — it's only there as an FK anchor (see class
            // docblock), not real staff, and would otherwise inflate
            // whichever company happens to be its anchor by one.
            ->withCount(['users' => fn ($q) => $q->whereRelation('role', 'slug', '!=', RoleEnum::PLATFORM_ADMIN->value)])
            // withoutGlobalScope here because Outlet::class uses
            // BelongsToCompany — left alone, CompanyScope would filter
            // this count subquery down to the *viewer's own* (anchor)
            // company for every row, making every other company in the
            // list show 0 outlets regardless of how many they actually have.
            ->withCount(['outlets' => fn ($q) => $q->withoutGlobalScope(CompanyScope::class)])
            ->with(['users' => fn ($q) => $q->oldest()->limit(1)])
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function pendingCount(): int
    {
        return Company::where('status', CompanyStatus::PENDING->value)->count();
    }

    public function approve(int $companyId): void
    {
        $company = Company::findOrFail($companyId);

        $company->update([
            'status' => CompanyStatus::APPROVED,
            'is_active' => true,
            'approved_at' => now(),
        ]);

        // The registrant is always the sole user until they're approved —
        // staffing (Admin\Users\Index) sits behind the same approval gate.
        $company->users()->first()?->notify(new CompanyApproved($company));

        unset($this->companies, $this->pendingCount);
    }

    public function startReject(int $companyId): void
    {
        $this->rejectingId = $companyId;
        $this->rejectionReason = '';
        $this->resetValidation();
    }

    public function cancelReject(): void
    {
        $this->rejectingId = null;
    }

    public function reject(): void
    {
        $this->validate(['rejectionReason' => 'required|string|max:500']);

        $company = Company::findOrFail($this->rejectingId);

        $company->update([
            'status' => CompanyStatus::REJECTED,
            'is_active' => false,
            'rejection_reason' => $this->rejectionReason,
        ]);

        $company->users()->first()?->notify(new CompanyRejected($company));

        $this->rejectingId = null;
        unset($this->companies, $this->pendingCount);
    }

    /**
     * Suspends an already-approved company (e.g. non-payment, abuse
     * report) without touching its status — it was validly approved and
     * stays that way in the record, just temporarily locked out. See
     * EnsureCompanyIsApproved for how this is enforced.
     */
    public function suspend(int $companyId): void
    {
        Company::whereKey($companyId)->where('status', CompanyStatus::APPROVED)->update(['is_active' => false]);

        unset($this->companies);
    }

    public function reactivate(int $companyId): void
    {
        Company::whereKey($companyId)->where('status', CompanyStatus::APPROVED)->update(['is_active' => true]);

        unset($this->companies);
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/login', navigate: true);
    }

    public function render()
    {
        return view('livewire.platform.companies.index')->layoutData(['title' => 'Admin Platform']);
    }
}
