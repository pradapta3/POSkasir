<?php

namespace App\Livewire\Platform\SubscriptionPlans;

use App\Livewire\Actions\Logout;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Platform-wide pricing tiers every company picks from — see
 * SubscriptionPlan::class. No hard delete: a plan a company is already
 * on shouldn't vanish out from under them, so closing one out is
 * deactivation only (is_active = false), same as Outlets/Categories.
 */
#[Layout('layouts.platform')]
class Index extends Component
{
    public bool $showFormModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public float $pricePerMonth = 0;

    public ?int $maxOutlets = null;

    public ?int $maxUsers = null;

    public bool $isActive = true;

    #[Computed]
    public function plans(): Collection
    {
        return SubscriptionPlan::query()->withCount('companies')->orderBy('sort_order')->orderBy('price_per_month')->get();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function edit(int $planId): void
    {
        $plan = SubscriptionPlan::findOrFail($planId);

        $this->editingId = $plan->id;
        $this->name = $plan->name;
        $this->pricePerMonth = (float) $plan->price_per_month;
        $this->maxOutlets = $plan->max_outlets;
        $this->maxUsers = $plan->max_users;
        $this->isActive = $plan->is_active;

        $this->resetValidation();
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'pricePerMonth' => 'required|numeric|min:0',
            'maxOutlets' => 'nullable|integer|min:1',
            'maxUsers' => 'nullable|integer|min:1',
        ]);

        $attributes = [
            'name' => $this->name,
            'price_per_month' => $this->pricePerMonth,
            'max_outlets' => $this->maxOutlets ?: null,
            'max_users' => $this->maxUsers ?: null,
            'is_active' => $this->isActive,
        ];

        if ($this->editingId) {
            SubscriptionPlan::findOrFail($this->editingId)->update($attributes);
        } else {
            SubscriptionPlan::create($attributes + ['slug' => $this->uniqueSlug(Str::slug($this->name))]);
        }

        $this->showFormModal = false;
        $this->resetForm();
        unset($this->plans);
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/login', navigate: true);
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base ?: 'paket';
        $suffix = 1;

        while (SubscriptionPlan::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'maxOutlets', 'maxUsers']);
        $this->pricePerMonth = 0;
        $this->isActive = true;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.platform.subscription-plans.index')->layoutData(['title' => 'Paket Langganan']);
    }
}
