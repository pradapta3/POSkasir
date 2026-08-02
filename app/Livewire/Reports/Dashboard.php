<?php

namespace App\Livewire\Reports;

use App\Livewire\Actions\Logout;
use App\Models\Outlet;
use App\Services\Reports\SalesReportService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.pos')]
class Dashboard extends Component
{
    public string $range = 'today';

    public string $customFrom = '';

    public string $customTo = '';

    public function setRange(string $range): void
    {
        $this->range = $range;

        if ($range === 'custom' && ! $this->customFrom) {
            $this->customFrom = now()->startOfMonth()->toDateString();
            $this->customTo = now()->toDateString();
        }
    }

    /** @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon} */
    #[Computed]
    public function period(): array
    {
        if ($this->range === 'custom' && $this->customFrom && $this->customTo) {
            $fromDate = \Carbon\Carbon::parse($this->customFrom);
            $toDate = \Carbon\Carbon::parse($this->customTo);

            // A backwards range (from > to) would make every whereBetween
            // below match nothing silently — swap instead of confusing the
            // owner with a report that's just empty for no visible reason.
            if ($fromDate->gt($toDate)) {
                [$fromDate, $toDate] = [$toDate, $fromDate];
            }

            return [$fromDate->startOfDay(), $toDate->endOfDay()];
        }

        return match ($this->range) {
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            default => [now()->startOfDay(), now()->endOfDay()],
        };
    }

    /**
     * Whatever OutletSwitcher currently has selected — null shows every
     * outlet in the company (the default for a Manager/Superadmin
     * overseeing the whole business, not just one branch).
     */
    #[Computed]
    public function outletId(): ?int
    {
        return Outlet::currentSessionOutlet(Auth::user())?->id;
    }

    #[Computed]
    public function summary(): array
    {
        [$from, $to] = $this->period;

        return app(SalesReportService::class)->summary(Auth::user()->company_id, $from, $to, $this->outletId);
    }

    #[Computed]
    public function dailySales(): Collection
    {
        [$from, $to] = $this->period;

        return app(SalesReportService::class)->dailySales(Auth::user()->company_id, $from, $to, $this->outletId);
    }

    #[Computed]
    public function topProducts(): Collection
    {
        [$from, $to] = $this->period;

        return app(SalesReportService::class)->topProducts(Auth::user()->company_id, $from, $to, $this->outletId);
    }

    #[Computed]
    public function lowStockProducts(): Collection
    {
        return app(SalesReportService::class)->lowStockProducts(Auth::user()->company_id, $this->outletId);
    }

    #[Computed]
    public function categoryPerformance(): Collection
    {
        [$from, $to] = $this->period;

        return app(SalesReportService::class)->categoryPerformance(Auth::user()->company_id, $from, $to, $this->outletId);
    }

    #[Computed]
    public function paymentMethodBreakdown(): Collection
    {
        [$from, $to] = $this->period;

        return app(SalesReportService::class)->paymentMethodBreakdown(Auth::user()->company_id, $from, $to, $this->outletId);
    }

    #[Computed]
    public function salesByCashier(): Collection
    {
        [$from, $to] = $this->period;

        return app(SalesReportService::class)->salesByCashier(Auth::user()->company_id, $from, $to, $this->outletId);
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/login', navigate: true);
    }

    public function render()
    {
        return view('livewire.reports.dashboard');
    }
}
