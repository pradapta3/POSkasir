<?php

namespace App\Livewire\Partials;

use App\Models\Outlet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Shown only to Manager/Superadmin (users.outlet_id === null) — a Cashier
 * is pinned to one outlet and never sees this. Writes the chosen outlet to
 * the session (see Outlet::currentSessionOutlet()), then reloads the page:
 * a sibling component's already-rendered computed properties (Terminal's
 * currentOutlet, Reports' filters) won't pick up a session change made by
 * this component without a fresh request.
 */
class OutletSwitcher extends Component
{
    #[Computed]
    public function outlets(): Collection
    {
        return Outlet::where('company_id', Auth::user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function selectedOutletId(): ?int
    {
        return Outlet::currentSessionOutlet(Auth::user())?->id;
    }

    public function switchOutlet(?int $outletId): void
    {
        if ($outletId) {
            session(['current_outlet_id' => $outletId]);
        } else {
            session()->forget('current_outlet_id');
        }

        $this->js('window.location.reload()');
    }

    public function render()
    {
        return view('livewire.partials.outlet-switcher');
    }
}
