<?php

namespace App\Livewire\Admin\Products;

use App\Enums\StockMovementType;
use App\Livewire\Actions\Logout;
use App\Models\Category;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductStock;
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

    public ?int $stockOutletId = null;

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

    /** Every outlet belonging to the logged-in manager's company. */
    #[Computed]
    public function outlets(): Collection
    {
        return Outlet::where('company_id', Auth::user()->company_id)->orderBy('name')->get();
    }

    #[Computed]
    public function products(): LengthAwarePaginator
    {
        return Product::query()
            ->with(['category', 'productStocks'])
            ->when($this->search, fn ($q) => $q->search($this->search))
            ->when($this->categoryFilter, fn ($q) => $q->where('category_id', $this->categoryFilter))
            ->orderBy('name')
            ->paginate(15);
    }

    /** Per-outlet stock rows for whichever product the stock modal is open for. */
    #[Computed]
    public function stockRows(): Collection
    {
        if (! $this->stockProductId) {
            return collect();
        }

        return ProductStock::where('product_id', $this->stockProductId)
            ->with('outlet')
            ->get()
            ->keyBy('outlet_id');
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
        $this->lowStockThreshold = $product->productStocks->first()?->low_stock_threshold ?? 5;
        $this->isActive = $product->is_active;
        $this->image = null;
        $this->editingImageUrl = $product->image_url;

        $this->resetValidation();
        $this->showFormModal = true;
    }

    public function save(StockAdjustmentService $stock): void
    {
        $companyId = Auth::user()->company_id;

        $rules = [
            // exists: queries the table directly and does not see Eloquent
            // global scopes, same issue as Rule::unique() below — without
            // the company_id filter this would accept another company's
            // category id.
            'categoryId' => ['nullable', Rule::exists('categories', 'id')->where('company_id', $companyId)],
            'name' => 'required|string|max:255',
            // Rule::unique() queries the table directly and does not see
            // Eloquent global scopes — company_id must be added explicitly
            // or this would reject a SKU that's only taken in another company.
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->where('company_id', $companyId)->ignore($this->editingId)],
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->where('company_id', $companyId)->ignore($this->editingId)],
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
            'is_active' => $this->isActive,
        ];

        if ($this->editingId) {
            $product = Product::findOrFail($this->editingId);
            $product->update($attributes + ['image_path' => $imagePath ?? $product->image_path]);

            // One threshold field in the form, applied uniformly across
            // every outlet's stock row for this product.
            ProductStock::where('product_id', $product->id)->update(['low_stock_threshold' => $this->lowStockThreshold]);
        } else {
            $product = Product::create($attributes + ['image_path' => $imagePath]);

            // A new product starts stocked at every outlet (0, or the
            // given initial quantity applied the same everywhere) — stock
            // can be corrected per outlet afterward via "Adjust Stock".
            foreach ($this->outlets as $outlet) {
                ProductStock::create([
                    'product_id' => $product->id,
                    'outlet_id' => $outlet->id,
                    'quantity' => 0,
                    'low_stock_threshold' => $this->lowStockThreshold,
                ]);

                if ($this->initialStock > 0) {
                    $stock->adjust(
                        productId: $product->id,
                        outletId: $outlet->id,
                        delta: $this->initialStock,
                        type: StockMovementType::IN,
                        actor: Auth::user(),
                        notes: 'Stok awal saat produk dibuat.',
                    );
                }
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
        $this->stockOutletId = $this->outlets->first()?->id;
        $this->stockDelta = 0;
        $this->stockNotes = '';
        $this->resetValidation();
        $this->showStockModal = true;
    }

    public function adjustStock(StockAdjustmentService $stock): void
    {
        $this->validate([
            'stockOutletId' => ['required', Rule::exists('outlets', 'id')->where('company_id', Auth::user()->company_id)],
            'stockDelta' => 'required|integer|not_in:0',
            'stockNotes' => 'nullable|string|max:255',
        ]);

        $currentQuantity = ProductStock::where('product_id', $this->stockProductId)
            ->where('outlet_id', $this->stockOutletId)
            ->value('quantity') ?? 0;

        if ($currentQuantity + $this->stockDelta < 0) {
            $this->addError('stockDelta', 'Penyesuaian ini akan membuat stok menjadi negatif.');

            return;
        }

        $stock->adjust(
            productId: $this->stockProductId,
            outletId: $this->stockOutletId,
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
