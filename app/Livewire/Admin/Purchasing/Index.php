<?php

namespace App\Livewire\Admin\Purchasing;

use App\Livewire\Actions\Logout;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\Purchasing\PurchasingService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * "Pembelian" — recording stock arriving from a supplier. Every receipt
 * goes through PurchasingService so stock-in and cost-price sync always
 * happen together in one transaction; this component is only responsible
 * for collecting the header (outlet/supplier/notes) and line items
 * (product/quantity/unit cost) before handing off.
 */
#[Layout('layouts.pos')]
class Index extends Component
{
    use WithPagination;

    public bool $showFormModal = false;

    public ?int $outletId = null;

    public ?int $supplierId = null;

    public string $notes = '';

    /** @var array<string, array{product_id:int, name:string, sku:string, quantity:int, unit_cost:float}> */
    public array $items = [];

    public string $productSearch = '';

    public bool $showDetailModal = false;

    public ?int $viewingId = null;

    #[Computed]
    public function outlets(): Collection
    {
        return Outlet::where('company_id', Auth::user()->company_id)->orderBy('name')->get();
    }

    #[Computed]
    public function suppliers(): Collection
    {
        return Supplier::active()->orderBy('name')->get();
    }

    #[Computed]
    public function purchaseOrders(): LengthAwarePaginator
    {
        return PurchaseOrder::query()
            ->with(['outlet', 'supplier', 'user'])
            ->withCount('items')
            ->latest()
            ->paginate(15);
    }

    #[Computed]
    public function viewingPurchaseOrder(): ?PurchaseOrder
    {
        if (! $this->viewingId) {
            return null;
        }

        return PurchaseOrder::with(['items', 'outlet', 'supplier', 'user'])->find($this->viewingId);
    }

    /** Typeahead results while adding a line item — capped, and excludes what's already in the cart. */
    #[Computed]
    public function productResults(): Collection
    {
        if (mb_strlen($this->productSearch) < 1) {
            return collect();
        }

        return Product::active()
            ->search($this->productSearch)
            ->whereNotIn('id', array_column($this->items, 'product_id'))
            ->orderBy('name')
            ->limit(8)
            ->get();
    }

    /** Products at or below their reorder threshold for the outlet currently selected in the form. */
    #[Computed]
    public function lowStockAlerts(): Collection
    {
        if (! $this->outletId) {
            return collect();
        }

        return ProductStock::query()
            ->where('outlet_id', $this->outletId)
            ->whereColumn('quantity', '<=', 'low_stock_threshold')
            ->whereNotIn('product_id', array_column($this->items, 'product_id'))
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->with('product:id,name,sku')
            ->orderBy('quantity')
            ->limit(5)
            ->get();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->outletId = Outlet::currentSessionOutlet(Auth::user())?->id ?? $this->outlets->first()?->id;
        $this->showFormModal = true;
    }

    public function addItem(int $productId): void
    {
        $product = Product::active()->findOrFail($productId);
        $key = (string) $product->id;

        if (isset($this->items[$key])) {
            return;
        }

        $this->items[$key] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 1,
            'unit_cost' => (float) $product->cost_price,
        ];

        $this->reset('productSearch');
    }

    public function removeItem(string $key): void
    {
        unset($this->items[$key]);
    }

    #[Computed]
    public function itemsTotal(): float
    {
        return collect($this->items)->sum(fn ($item) => $item['quantity'] * $item['unit_cost']);
    }

    public function save(PurchasingService $purchasing): void
    {
        $this->validate([
            'outletId' => ['required', Rule::exists('outlets', 'id')->where('company_id', Auth::user()->company_id)],
            'supplierId' => ['nullable', Rule::exists('suppliers', 'id')->where('company_id', Auth::user()->company_id)],
            'notes' => 'nullable|string|max:500',
        ]);

        if (empty($this->items)) {
            $this->addError('items', 'Tambahkan minimal satu produk.');

            return;
        }

        foreach ($this->items as $item) {
            if ($item['quantity'] < 1 || $item['unit_cost'] < 0) {
                $this->addError('items', 'Jumlah dan harga beli harus lebih dari 0.');

                return;
            }
        }

        $purchasing->receive(
            outletId: $this->outletId,
            supplierId: $this->supplierId,
            items: array_values($this->items),
            actor: Auth::user(),
            notes: $this->notes ?: null,
        );

        $this->showFormModal = false;
        $this->resetForm();
        unset($this->purchaseOrders);
    }

    public function viewDetail(int $purchaseOrderId): void
    {
        $this->viewingId = $purchaseOrderId;
        $this->showDetailModal = true;
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/login', navigate: true);
    }

    private function resetForm(): void
    {
        $this->reset(['outletId', 'supplierId', 'notes', 'items', 'productSearch']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.purchasing.index');
    }
}
