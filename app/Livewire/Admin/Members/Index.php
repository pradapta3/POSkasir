<?php

namespace App\Livewire\Admin\Members;

use App\Enums\LoyaltyMovementType;
use App\Livewire\Actions\Logout;
use App\Models\Customer;
use App\Services\Loyalty\LoyaltyService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

/**
 * Every row here is a Customer (see Customer::class docblock) — this
 * screen both lists them all and is where a plain phone-captured Customer
 * gets promoted into a loyalty-program member (member_code assigned,
 * points start accruing). Points math itself always goes through
 * LoyaltyService, never a direct update here.
 */
#[Layout('layouts.pos')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showFormModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public ?string $phone = null;

    public ?string $email = null;

    public ?string $address = null;

    public bool $isMember = false;

    public bool $showPointsModal = false;

    public ?int $pointsCustomerId = null;

    public ?string $pointsCustomerName = null;

    public ?int $pointsCustomerBalance = null;

    public int $pointsDelta = 0;

    public string $pointsNotes = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function customers(): LengthAwarePaginator
    {
        return Customer::query()
            ->when($this->search, fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%")
                ->orWhere('member_code', 'like', "%{$this->search}%"))
            ->orderByDesc('is_member')
            ->orderBy('name')
            ->paginate(15);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function edit(int $customerId): void
    {
        $customer = Customer::findOrFail($customerId);

        $this->editingId = $customer->id;
        $this->name = $customer->name;
        $this->phone = $customer->phone;
        $this->email = $customer->email;
        $this->address = $customer->address;
        $this->isMember = $customer->is_member;

        $this->resetValidation();
        $this->showFormModal = true;
    }

    public function save(LoyaltyService $loyalty): void
    {
        $companyId = Auth::user()->company_id;

        $this->validate([
            'name' => 'required|string|max:255',
            // Rule::unique() queries the table directly and does not see
            // Eloquent global scopes — company_id must be added explicitly,
            // same fix as everywhere else Rule::unique touches a
            // tenant-scoped column.
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('customers', 'phone')->where('company_id', $companyId)->ignore($this->editingId)],
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
        ]);

        $attributes = [
            'name' => $this->name,
            'phone' => $this->phone ?: null,
            'email' => $this->email ?: null,
            'address' => $this->address ?: null,
        ];

        if ($this->editingId) {
            $customer = Customer::findOrFail($this->editingId);
            $customer->update($attributes);
        } else {
            $customer = Customer::create($attributes);
        }

        // Only promote here — becoming a member never happens by unchecking
        // the box (that would silently discard their point history); it's
        // a one-way step, same as at checkout's "Daftarkan sebagai member".
        if ($this->isMember) {
            $loyalty->enroll($customer);
        }

        $this->showFormModal = false;
        $this->resetForm();
        unset($this->customers);
    }

    public function openPointsModal(int $customerId): void
    {
        $customer = Customer::findOrFail($customerId);

        $this->pointsCustomerId = $customer->id;
        $this->pointsCustomerName = $customer->name;
        $this->pointsCustomerBalance = $customer->loyalty_points;
        $this->pointsDelta = 0;
        $this->pointsNotes = '';
        $this->resetValidation();
        $this->showPointsModal = true;
    }

    public function adjustPoints(LoyaltyService $loyalty): void
    {
        $this->validate([
            'pointsDelta' => 'required|integer|not_in:0',
            'pointsNotes' => 'required|string|max:255',
        ]);

        $customer = Customer::findOrFail($this->pointsCustomerId);

        try {
            $loyalty->adjust(
                customer: $customer,
                delta: $this->pointsDelta,
                type: LoyaltyMovementType::ADJUSTMENT,
                actor: Auth::user(),
                notes: $this->pointsNotes,
            );
        } catch (RuntimeException $e) {
            $this->addError('pointsDelta', $e->getMessage());

            return;
        }

        $this->showPointsModal = false;
        unset($this->customers);
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/login', navigate: true);
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'phone', 'email', 'address', 'isMember']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.members.index');
    }
}
