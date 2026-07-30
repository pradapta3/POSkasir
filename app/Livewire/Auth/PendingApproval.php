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
        // Already approved (or a Platform Admin who has no business here) —
        // don't show a stale waiting screen, just send them where they belong.
        if (Auth::user()->isPlatformAdmin() || Auth::user()->company->status === CompanyStatus::APPROVED) {
            $this->redirectRoute('pos.terminal', navigate: true);
        }
    }

    #[Computed]
    public function company()
    {
        return Auth::user()->company;
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
