<?php

namespace App\Livewire\Admin\Labels;

use App\Livewire\Actions\Logout;
use App\Models\Product;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Picks products + a quantity of labels each, then hands off to
 * LabelPrintController for the actual printable sheet. Deliberately two
 * separate pages (like Terminal's "Cetak Struk" -> ReceiptController)
 * rather than one Livewire page that toggles a print view: the label sheet
 * needs a hard page load so its barcode-rendering CDN script (JsBarcode)
 * runs fresh every time, which a wire:navigate SPA transition can't
 * guarantee. The selection is handed off via session — ephemeral by
 * design, there's nothing here worth persisting once the sheet is printed.
 */
#[Layout('layouts.pos')]
class Index extends Component
{
    /** @var array<string, array{product_id:int, name:string, sku:string, barcode:?string, price:float, qty:int}> */
    public array $items = [];

    public string $productSearch = '';

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
            'barcode' => $product->barcode,
            'price' => (float) $product->selling_price,
            'qty' => 1,
        ];

        $this->reset('productSearch');
    }

    public function removeItem(string $key): void
    {
        unset($this->items[$key]);
    }

    #[Computed]
    public function totalLabels(): int
    {
        return collect($this->items)->sum('qty');
    }

    public function printLabels(): void
    {
        if (empty($this->items)) {
            $this->addError('items', 'Pilih minimal satu produk.');

            return;
        }

        foreach ($this->items as $item) {
            if ($item['qty'] < 1) {
                $this->addError('items', 'Jumlah label harus lebih dari 0.');

                return;
            }
        }

        session(['label_print_items' => array_values($this->items)]);

        $this->js("window.open('".route('admin.labels.print')."', '_blank')");
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/login', navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.labels.index');
    }
}
