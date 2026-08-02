<?php

namespace App\Livewire\Auth;

use App\Enums\CompanyStatus;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class PendingApproval extends Component
{
    public function mount(): void
    {
        // Genuinely cleared to operate (or a Platform Admin who has no
        // business here) — don't show a stale waiting screen, just send
        // them where they belong. See EnsureCompanyIsApproved for why
        // status, is_active, and expiry are checked separately.
        $company = Auth::user()->company;
        $clearedToOperate = $company->status === CompanyStatus::APPROVED
            && $company->is_active
            && ! $company->hasExpiredAccess();

        if (Auth::user()->isPlatformAdmin() || $clearedToOperate) {
            $this->redirectRoute('pos.terminal', navigate: true);
        }
    }

    #[Computed]
    public function company()
    {
        return Auth::user()->company;
    }

    /** 'pending' | 'rejected' | 'suspended' | 'expired' — cleared-to-operate never reaches this page. */
    #[Computed]
    public function state(): string
    {
        $company = $this->company;

        return match (true) {
            $company->status === CompanyStatus::REJECTED => 'rejected',
            $company->status === CompanyStatus::APPROVED && ! $company->is_active => 'suspended',
            $company->status === CompanyStatus::APPROVED && $company->hasExpiredAccess() => 'expired',
            default => 'pending',
        };
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/login', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.pending-approval')->layoutData(['title' => 'Menunggu Persetujuan']);
    }
}
