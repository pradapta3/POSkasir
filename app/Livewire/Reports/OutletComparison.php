<?php

namespace App\Livewire\Reports;

use App\Livewire\Actions\Logout;
use App\Services\Reports\SalesReportService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Side-by-side outlet performance — only meaningful for a company running
 * more than one branch, and only to a Superadmin (a Manager's view is
 * already scoped to whichever outlet OutletSwitcher has them on, so
 * comparing across outlets isn't a decision that belongs at that level).
 * Deliberately does not take an outlet filter, unlike the main Dashboard —
 * see SalesReportService::outletComparison().
 */
#[Layout('layouts.pos')]
class OutletComparison extends Component
{
    public string $range = 'month';

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

            if ($fromDate->gt($toDate)) {
                [$fromDate, $toDate] = [$toDate, $fromDate];
            }

            return [$fromDate->startOfDay(), $toDate->endOfDay()];
        }

        return match ($this->range) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }

    #[Computed]
    public function outlets(): Collection
    {
        [$from, $to] = $this->period;

        return app(SalesReportService::class)->outletComparison(Auth::user()->company_id, $from, $to);
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/login', navigate: true);
    }

    public function render()
    {
        return view('livewire.reports.outlet-comparison');
    }
}
