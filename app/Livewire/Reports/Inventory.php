<?php

namespace App\Livewire\Reports;

use App\Livewire\Actions\Logout;
use App\Models\Outlet;
use App\Services\Reports\InventoryReportService;
use App\Services\Reports\SalesReportService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * How much capital is tied up in stock on hand, at cost — closes the loop
 * on Fase 7's purchasing/cost-tracking work by surfacing what it's actually
 * worth. Outlet-aware the same way Dashboard is (OutletSwitcher's current
 * selection, null = every outlet).
 */
#[Layout('layouts.pos')]
class Inventory extends Component
{
    #[Computed]
    public function outletId(): ?int
    {
        return Outlet::currentSessionOutlet(Auth::user())?->id;
    }

    #[Computed]
    public function valuationByOutlet(): Collection
    {
        return app(InventoryReportService::class)->valuationByOutlet(Auth::user()->company_id, $this->outletId);
    }

    #[Computed]
    public function topValueProducts(): Collection
    {
        return app(InventoryReportService::class)->topValueProducts(Auth::user()->company_id, $this->outletId);
    }

    #[Computed]
    public function lowStockProducts(): Collection
    {
        return app(SalesReportService::class)->lowStockProducts(Auth::user()->company_id, $this->outletId, limit: 10);
    }

    #[Computed]
    public function totalValue(): float
    {
        return (float) $this->valuationByOutlet->sum('total_value');
    }

    #[Computed]
    public function totalUnits(): int
    {
        return (int) $this->valuationByOutlet->sum('total_units');
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/login', navigate: true);
    }

    public function render()
    {
        return view('livewire.reports.inventory');
    }
}
