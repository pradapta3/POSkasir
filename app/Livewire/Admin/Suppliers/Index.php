<?php

namespace App\Livewire\Admin\Suppliers;

use App\Livewire\Actions\Logout;
use App\Models\Supplier;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.pos')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showFormModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public ?string $contactPerson = null;

    public ?string $phone = null;

    public ?string $email = null;

    public ?string $address = null;

    public bool $isActive = true;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function suppliers(): LengthAwarePaginator
    {
        return Supplier::query()
            ->withCount('purchaseOrders')
            ->when($this->search, fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('contact_person', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(15);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function edit(int $supplierId): void
    {
        $supplier = Supplier::findOrFail($supplierId);

        $this->editingId = $supplier->id;
        $this->name = $supplier->name;
        $this->contactPerson = $supplier->contact_person;
        $this->phone = $supplier->phone;
        $this->email = $supplier->email;
        $this->address = $supplier->address;
        $this->isActive = $supplier->is_active;

        $this->resetValidation();
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'contactPerson' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
        ]);

        $attributes = [
            'name' => $this->name,
            'contact_person' => $this->contactPerson ?: null,
            'phone' => $this->phone ?: null,
            'email' => $this->email ?: null,
            'address' => $this->address ?: null,
            'is_active' => $this->isActive,
        ];

        if ($this->editingId) {
            Supplier::findOrFail($this->editingId)->update($attributes);
        } else {
            Supplier::create($attributes);
        }

        $this->showFormModal = false;
        $this->resetForm();
        unset($this->suppliers);
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/login', navigate: true);
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'contactPerson', 'phone', 'email', 'address']);
        $this->isActive = true;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.suppliers.index');
    }
}
