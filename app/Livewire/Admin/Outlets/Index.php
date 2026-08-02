<?php

namespace App\Livewire\Admin\Outlets;

use App\Livewire\Actions\Logout;
use App\Models\Outlet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Superadmin only (see routes/web.php) — adding/closing a branch is a
 * structural decision, same tier as Pengaturan. No hard delete: every
 * operational table (transactions, shifts, product_stocks, ...) has a
 * required, non-nullable outlet_id, so removing an outlet would either
 * fail on the FK or orphan real sales history. Closing one is
 * deactivation only, same as Products/Categories.
 */
#[Layout('layouts.pos')]
class Index extends Component
{
    public bool $showFormModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public ?string $address = null;

    public ?string $phone = null;

    public bool $isActive = true;

    #[Computed]
    public function outlets(): Collection
    {
        return Outlet::query()->withCount('users')->orderBy('name')->get();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function edit(int $outletId): void
    {
        $outlet = Outlet::findOrFail($outletId);

        $this->editingId = $outlet->id;
        $this->name = $outlet->name;
        $this->address = $outlet->address;
        $this->phone = $outlet->phone;
        $this->isActive = $outlet->is_active;

        $this->resetValidation();
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
        ]);

        $attributes = [
            'name' => $this->name,
            'address' => $this->address ?: null,
            'phone' => $this->phone ?: null,
        ];

        if ($this->editingId) {
            $outlet = Outlet::findOrFail($this->editingId);

            // A company always needs at least one active outlet for its
            // Terminal to have somewhere to operate — block turning off
            // the last one instead of silently locking the store out.
            if ($outlet->is_active && ! $this->isActive && $this->isLastActiveOutlet($outlet)) {
                $this->addError('isActive', 'Tidak bisa menonaktifkan satu-satunya outlet aktif.');

                return;
            }

            $outlet->update($attributes + ['is_active' => $this->isActive]);
        } else {
            if ($this->exceedsOutletLimit()) {
                $max = Auth::user()->company->subscriptionPlan->max_outlets;
                $this->addError('name', "Paket langganan kamu hanya mengizinkan maksimal {$max} outlet. Upgrade paket di Langganan Saya untuk menambah outlet.");

                return;
            }

            Outlet::create($attributes + ['is_active' => $this->isActive]);
        }

        $this->showFormModal = false;
        $this->resetForm();
        unset($this->outlets);
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/login', navigate: true);
    }

    private function isLastActiveOutlet(Outlet $outlet): bool
    {
        return Outlet::where('company_id', Auth::user()->company_id)
            ->where('is_active', true)
            ->where('id', '!=', $outlet->id)
            ->doesntExist();
    }

    /** No plan (still on trial) or a plan with max_outlets = null both mean unlimited. */
    private function exceedsOutletLimit(): bool
    {
        $max = Auth::user()->company->subscriptionPlan?->max_outlets;

        if ($max === null) {
            return false;
        }

        return Outlet::where('company_id', Auth::user()->company_id)->count() >= $max;
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'address', 'phone']);
        $this->isActive = true;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.outlets.index');
    }
}
