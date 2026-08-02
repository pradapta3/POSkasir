<?php

namespace App\Livewire\Billing;

use App\Enums\SubscriptionPaymentStatus;
use App\Livewire\Actions\Logout;
use App\Models\SubscriptionPaymentRequest;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The company-facing "Langganan Saya" screen — deliberately sits outside
 * the 'approved' route middleware group (see routes/web.php) so a
 * suspended-by-expiry company can still reach it to renew.
 *
 * There's no live payment gateway wired up: the earlier POS payment
 * simplification (Cash + static QRIS) established the pattern this
 * follows too — start manual, automate later once real gateway
 * credentials exist. A Superadmin picks a plan and month count, sees bank
 * transfer instructions, and files a claim once they've paid; a Platform
 * Admin reviews and confirms it by hand (Platform\PaymentRequests\Index).
 */
#[Layout('layouts.pos')]
class Index extends Component
{
    public ?int $selectedPlanId = null;

    public int $months = 1;

    public string $notes = '';

    public bool $showRequestModal = false;

    public function mount(): void
    {
        $this->selectedPlanId = $this->plans->first()?->id;
    }

    #[Computed]
    public function company()
    {
        return Auth::user()->company;
    }

    #[Computed]
    public function plans(): Collection
    {
        return SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->orderBy('price_per_month')->get();
    }

    #[Computed]
    public function selectedPlan(): ?SubscriptionPlan
    {
        return $this->plans->firstWhere('id', $this->selectedPlanId);
    }

    #[Computed]
    public function totalAmount(): float
    {
        return (float) ($this->selectedPlan?->price_per_month ?? 0) * max(1, $this->months);
    }

    /** Only this company's requests — SubscriptionPaymentRequest has no BelongsToCompany, so this must filter explicitly. */
    #[Computed]
    public function requestHistory(): Collection
    {
        return SubscriptionPaymentRequest::where('company_id', $this->company->id)
            ->with('plan')
            ->latest()
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function hasPendingRequest(): bool
    {
        return SubscriptionPaymentRequest::where('company_id', $this->company->id)
            ->where('status', SubscriptionPaymentStatus::PENDING->value)
            ->exists();
    }

    public function selectPlan(int $planId): void
    {
        $this->selectedPlanId = $planId;
    }

    public function openRequestModal(): void
    {
        $this->notes = '';
        $this->resetValidation();
        $this->showRequestModal = true;
    }

    public function submitRequest(): void
    {
        $this->validate([
            'selectedPlanId' => 'required|exists:subscription_plans,id',
            'months' => 'required|integer|min:1|max:24',
        ]);

        SubscriptionPaymentRequest::create([
            'company_id' => $this->company->id,
            'subscription_plan_id' => $this->selectedPlanId,
            'months' => $this->months,
            'amount' => $this->totalAmount,
            'status' => SubscriptionPaymentStatus::PENDING,
            'notes' => $this->notes ?: null,
            'requested_by' => Auth::id(),
        ]);

        $this->showRequestModal = false;
        unset($this->requestHistory, $this->hasPendingRequest);
        $this->dispatch('request-submitted');
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/login', navigate: true);
    }

    public function render()
    {
        return view('livewire.billing.index')->layoutData(['title' => 'Langganan Saya']);
    }
}
