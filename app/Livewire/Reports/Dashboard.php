<?php

namespace App\Livewire\Reports;

use App\Livewire\Actions\Logout;
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

    public function setRange(string $range): void
    {
        $this->range = $range;
    }

    /** @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon} */
    #[Computed]
    public function period(): array
    {
        return match ($this->range) {
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            default => [now()->startOfDay(), now()->endOfDay()],
        };
    }

    #[Computed]
    public function summary(): array
    {
        [$from, $to] = $this->period;

        return app(SalesReportService::class)->summary(Auth::user()->company_id, $from, $to);
    }

    #[Computed]
    public function dailySales(): Collection
    {
        [$from, $to] = $this->period;

        return app(SalesReportService::class)->dailySales(Auth::user()->company_id, $from, $to);
    }

    #[Computed]
    public function topProducts(): Collection
    {
        [$from, $to] = $this->period;

        return app(SalesReportService::class)->topProducts(Auth::user()->company_id, $from, $to);
    }

    #[Computed]
    public function lowStockProducts(): Collection
    {
        return app(SalesReportService::class)->lowStockProducts(Auth::user()->company_id);
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
