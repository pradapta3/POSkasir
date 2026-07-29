<?php

namespace App\Livewire\Admin\Products;

use App\Enums\StockMovementType;
use App\Livewire\Actions\Logout;
use App\Models\Category;
use App\Models\Product;
use App\Services\Inventory\StockAdjustmentService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.pos')]
class Index extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $search = '';

    public ?int $categoryFilter = null;

    public bool $showFormModal = false;

    public ?int $editingId = null;

    public ?int $categoryId = null;

    public string $name = '';

    public string $sku = '';

    public ?string $barcode = null;

    public ?string $description = null;

    public string $unit = 'pcs';

    public float $costPrice = 0;

    public float $sellingPrice = 0;

    public int $initialStock = 0;

    public int $lowStockThreshold = 5;

    public bool $isActive = true;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $image = null;

    public ?string $editingImageUrl = null;

    public bool $showStockModal = false;

    public ?int $stockProductId = null;

    public ?string $stockProductName = null;

    public int $stockDelta = 0;

    public string $stockNotes = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->orderBy('name')->get();
    }

    #[Computed]
    public function products(): LengthAwarePaginator
    {
        return Product::query()
            ->with('category')
            ->when($this->search, fn ($q) => $q->search($this->search))
            ->when($this->categoryFilter, fn ($q) => $q->where('category_id', $this->categoryFilter))
            ->orderBy('name')
            ->paginate(15);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function edit(int $productId): void
    {
        $product = Product::findOrFail($productId);

        $this->editingId = $product->id;
        $this->categoryId = $product->category_id;
        $this->name = $product->name;
        $this->sku = $product->sku;
        $this->barcode = $product->barcode;
        $this->description = $product->description;
        $this->unit = $product->unit;
        $this->costPrice = (float) $product->cost_price;
        $this->sellingPrice = (float) $product->selling_price;
        $this->lowStockThreshold = $product->low_stock_threshold;
        $this->isActive = $product->is_active;
        $this->image = null;
        $this->editingImageUrl = $product->image_url;

        $this->resetValidation();
        $this->showFormModal = true;
    }

    public function save(StockAdjustmentService $stock): void
    {
        $rules = [
            'categoryId' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($this->editingId)],
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->ignore($this->editingId)],
            'description' => 'nullable|string',
            'unit' => 'required|string|max:20',
            'costPrice' => 'required|numeric|min:0',
            'sellingPrice' => 'required|numeric|min:0|gte:costPrice',
            'lowStockThreshold' => 'required|integer|min:0',
            'image' => 'nullable|image|max:2048',
        ];

        if (! $this->editingId) {
            $rules['initialStock'] = 'required|integer|min:0';
        }

        $this->validate($rules);

        $imagePath = $this->image ? $this->image->store('products', 'public') : null;

        $attributes = [
            'category_id' => $this->categoryId,
            'name' => $this->name,
            'sku' => $this->sku,
            'barcode' => $this->barcode ?: null,
            'description' => $this->description,
            'unit' => $this->unit,
            'cost_price' => $this->costPrice,
            'selling_price' => $this->sellingPrice,
            'low_stock_threshold' => $this->lowStockThreshold,
            'is_active' => $this->isActive,
        ];

        if ($this->editingId) {
            $product = Product::findOrFail($this->editingId);
            $product->update($attributes + ['image_path' => $imagePath ?? $product->image_path]);
        } else {
            $product = Product::create($attributes + ['stock_quantity' => 0, 'image_path' => $imagePath]);

            if ($this->initialStock > 0) {
                $stock->adjust(
                    productId: $product->id,
                    delta: $this->initialStock,
                    type: StockMovementType::IN,
                    actor: Auth::user(),
                    notes: 'Stok awal saat produk dibuat.',
                );
            }
        }

        $this->showFormModal = false;
        $this->resetForm();
        unset($this->products);
    }

    public function delete(int $productId): void
    {
        Product::whereKey($productId)->delete();
        unset($this->products);
    }

    public function openStockModal(int $productId): void
    {
        $product = Product::findOrFail($productId);

        $this->stockProductId = $product->id;
        $this->stockProductName = $product->name;
        $this->stockDelta = 0;
        $this->stockNotes = '';
        $this->resetValidation();
        $this->showStockModal = true;
    }

    public function adjustStock(StockAdjustmentService $stock): void
    {
        $this->validate([
            'stockDelta' => 'required|integer|not_in:0',
            'stockNotes' => 'nullable|string|max:255',
        ]);

        $product = Product::findOrFail($this->stockProductId);

        if ($product->stock_quantity + $this->stockDelta < 0) {
            $this->addError('stockDelta', 'Penyesuaian ini akan membuat stok menjadi negatif.');

            return;
        }

        $stock->adjust(
            productId: $product->id,
            delta: $this->stockDelta,
            type: StockMovementType::ADJUSTMENT,
            actor: Auth::user(),
            notes: $this->stockNotes ?: null,
        );

        $this->showStockModal = false;
        unset($this->products);
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/login', navigate: true);
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'categoryId', 'name', 'sku', 'barcode', 'description',
            'initialStock', 'image', 'editingImageUrl',
        ]);
        $this->unit = 'pcs';
        $this->costPrice = 0;
        $this->sellingPrice = 0;
        $this->lowStockThreshold = 5;
        $this->isActive = true;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.products.index');
    }
}
