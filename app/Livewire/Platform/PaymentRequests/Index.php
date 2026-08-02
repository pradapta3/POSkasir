<?php

namespace App\Livewire\Platform\PaymentRequests;

use App\Enums\SubscriptionPaymentStatus;
use App\Livewire\Actions\Logout;
use App\Models\SubscriptionPaymentRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Where a Platform Admin confirms the manual "I've transferred" claims
 * filed from Billing\Index — see that class's docblock for why this is
 * manual rather than a live payment gateway.
 */
#[Layout('layouts.platform')]
class Index extends Component
{
    public string $statusFilter = 'pending';

    public ?int $rejectingId = null;

    public string $rejectionReason = '';

    #[Computed]
    public function requests(): Collection
    {
        return SubscriptionPaymentRequest::query()
            ->with(['company', 'plan', 'requestedBy'])
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->get();
    }

    #[Computed]
    public function pendingCount(): int
    {
        return SubscriptionPaymentRequest::where('status', SubscriptionPaymentStatus::PENDING->value)->count();
    }

    /**
     * Confirming both settles the request and extends the company's
     * subscription — stacked from whatever's left of the current paid
     * period (not from today), so renewing a few days early never wastes
     * the days still remaining.
     */
    public function confirm(int $requestId): void
    {
        $request = SubscriptionPaymentRequest::with('company')->findOrFail($requestId);

        if ($request->status !== SubscriptionPaymentStatus::PENDING) {
            return;
        }

        $company = $request->company;
        $currentExpiry = $company->subscription_ends_at;
        $baseDate = ($currentExpiry && $currentExpiry->isFuture()) ? $currentExpiry : now();

        $company->update([
            'subscription_plan_id' => $request->subscription_plan_id,
            'subscription_ends_at' => $baseDate->copy()->addMonths($request->months),
        ]);

        $request->update([
            'status' => SubscriptionPaymentStatus::CONFIRMED,
            'confirmed_by' => Auth::id(),
            'confirmed_at' => now(),
        ]);

        unset($this->requests, $this->pendingCount);
    }

    public function startReject(int $requestId): void
    {
        $this->rejectingId = $requestId;
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

        $request = SubscriptionPaymentRequest::findOrFail($this->rejectingId);

        if ($request->status === SubscriptionPaymentStatus::PENDING) {
            $request->update([
                'status' => SubscriptionPaymentStatus::REJECTED,
                'rejection_reason' => $this->rejectionReason,
            ]);
        }

        $this->rejectingId = null;
        unset($this->requests, $this->pendingCount);
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/login', navigate: true);
    }

    public function render()
    {
        return view('livewire.platform.payment-requests.index')->layoutData(['title' => 'Permintaan Pembayaran']);
    }
}
