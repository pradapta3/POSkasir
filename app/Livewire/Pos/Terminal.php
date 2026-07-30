<?php

namespace App\Livewire\Pos;

use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Enums\ShiftStatus;
use App\Enums\TransactionStatus;
use App\Livewire\Actions\Logout;
use App\Models\Category;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\Transaction;
use App\Services\Pos\CartCalculator;
use App\Services\Pos\CheckoutService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;

#[Layout('layouts.pos')]
class Terminal extends Component
{
    /** @var array<string, array{product_id:int,name:string,sku:string,price:float,cost_price:float,quantity:int,max_quantity:int}> */
    public array $cart = [];

    public string $search = '';

    public ?int $activeCategoryId = null;

    public ?string $discountType = null;

    public float $discountValue = 0;

    public float $taxPercentage = 0;

    public string $paymentMethod = 'cash';

    public float $amountPaid = 0;

    public ?string $customerName = null;

    public ?string $customerPhone = null;

    public ?int $resumingTransactionId = null;

    public bool $showPaymentModal = false;

    public bool $showHeldOrders = false;

    public bool $showOpenShiftModal = false;

    public bool $showCloseShiftModal = false;

    public float $startingCash = 0;

    public float $actualCash = 0;

    public bool $showQrisModal = false;

    public ?string $qrisUrl = null;

    public ?string $qrisInvoiceNumber = null;

    public ?int $qrisTransactionId = null;

    public function mount(): void
    {
        $this->showOpenShiftModal = $this->activeShift === null;
        $this->taxPercentage = $this->defaultTaxPercentage();
    }

    #[Computed]
    public function activeShift(): ?Shift
    {
        return Auth::user()->activeShift();
    }

    /**
     * A Cashier is pinned to one outlet (users.outlet_id); a Manager or
     * Superadmin has outlet_id = null (every outlet in the company) and,
     * until the outlet switcher lands, defaults to the company's first one.
     */
    #[Computed]
    public function currentOutlet(): ?Outlet
    {
        $user = Auth::user();

        if ($user->outlet_id) {
            return $user->outlet;
        }

        return Outlet::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }

    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->active()->orderBy('name')->get();
    }

    #[Computed]
    public function productList(): Collection
    {
        $outletId = $this->currentOutlet?->id;

        return Product::query()
            ->active()
            ->with(['productStocks' => fn ($q) => $q->where('outlet_id', $outletId)])
            ->when($this->activeCategoryId, fn ($q) => $q->where('category_id', $this->activeCategoryId))
            ->when(mb_strlen($this->search) >= 1, fn ($q) => $q->search($this->search))
            ->orderBy('name')
            ->limit(24)
            ->get();
    }

    #[Computed]
    public function heldOrders(): Collection
    {
        return Transaction::query()
            ->where('status', TransactionStatus::HELD)
            ->where('outlet_id', $this->currentOutlet?->id)
            ->withCount('items')
            ->latest('held_at')
            ->get();
    }

    #[Computed]
    public function totals(): array
    {
        return app(CartCalculator::class)->totals(
            $this->cart,
            $this->discountType ? DiscountType::from($this->discountType) : null,
            $this->discountValue,
            $this->taxPercentage,
        );
    }

    public function filterByCategory(?int $categoryId): void
    {
        $this->activeCategoryId = $categoryId;
    }

    public function scanBarcode(): void
    {
        $code = trim($this->search);

        if ($code === '') {
            return;
        }

        $product = Product::query()
            ->active()
            ->where(fn ($q) => $q->where('barcode', $code)->orWhere('sku', $code))
            ->first();

        if (! $product) {
            $this->addError('search', "Produk untuk \"{$code}\" tidak ditemukan.");

            return;
        }

        $this->addProductToCart($product);
        $this->reset('search');
    }

    public function addToCart(int $productId): void
    {
        $this->addProductToCart(Product::active()->findOrFail($productId));
    }

    private function addProductToCart(Product $product): void
    {
        $key = (string) $product->id;

        if (isset($this->cart[$key])) {
            $this->incrementQuantity($product->id);

            return;
        }

        $available = $product->quantityAt($this->currentOutlet->id);

        if ($available <= 0) {
            $this->addError('search', "Stok {$product->name} habis.");

            return;
        }

        $this->cart[$key] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => (float) $product->selling_price,
            'cost_price' => (float) $product->cost_price,
            'quantity' => 1,
            'max_quantity' => $available,
        ];
    }

    public function incrementQuantity(int $productId): void
    {
        $key = (string) $productId;

        if (! isset($this->cart[$key])) {
            return;
        }

        if ($this->cart[$key]['quantity'] + 1 > $this->cart[$key]['max_quantity']) {
            $this->addError('cart', 'Stok tidak mencukupi.');

            return;
        }

        $this->cart[$key]['quantity']++;
    }

    public function decrementQuantity(int $productId): void
    {
        $key = (string) $productId;

        if (! isset($this->cart[$key])) {
            return;
        }

        $this->cart[$key]['quantity']--;

        if ($this->cart[$key]['quantity'] <= 0) {
            unset($this->cart[$key]);
        }
    }

    public function updateQuantity(int $productId, mixed $quantity): void
    {
        $key = (string) $productId;

        if (! isset($this->cart[$key])) {
            return;
        }

        $quantity = max(0, (int) $quantity);

        if ($quantity === 0) {
            unset($this->cart[$key]);

            return;
        }

        if ($quantity > $this->cart[$key]['max_quantity']) {
            $quantity = $this->cart[$key]['max_quantity'];
            $this->addError('cart', 'Stok tidak mencukupi.');
        }

        $this->cart[$key]['quantity'] = $quantity;
    }

    public function removeFromCart(int $productId): void
    {
        unset($this->cart[(string) $productId]);
    }

    public function applyDiscount(?string $type, float $value): void
    {
        $this->discountType = $value > 0 ? $type : null;
        $this->discountValue = $value;
    }

    public function openPaymentModal(): void
    {
        if (empty($this->cart)) {
            $this->addError('cart', 'Tambahkan minimal satu produk sebelum membayar.');

            return;
        }

        $this->amountPaid = $this->totals['grandTotal'];
        $this->showPaymentModal = true;
    }

    public function setAmountPaid(float $amount): void
    {
        $this->amountPaid = $amount;
    }

    /**
     * Quick cash buttons for the payment modal: the exact total, plus the
     * next round Rp 10k/50k/100k above it — the denominations an Indonesian
     * cashier is actually handed across the counter. Skips any that equal
     * the exact total already, and drops duplicates once amounts coincide.
     */
    #[Computed]
    public function cashSuggestions(): array
    {
        $total = (float) $this->totals['grandTotal'];

        $roundedUp = collect([10_000, 50_000, 100_000])
            ->map(fn (int $denomination) => (int) (ceil($total / $denomination) * $denomination))
            ->filter(fn (int $amount) => $amount > $total);

        return collect([(int) ceil($total)])
            ->merge($roundedUp)
            ->unique()
            ->values()
            ->all();
    }

    public function checkout(CheckoutService $service): void
    {
        $this->validate([
            'paymentMethod' => 'required|string',
            'amountPaid' => 'required|numeric|min:0',
            'customerPhone' => 'nullable|string|max:20',
        ]);

        try {
            $transaction = $service->checkout(
                cart: $this->cart,
                cashier: Auth::user(),
                shift: $this->activeShift,
                paymentMethod: $this->paymentMethod,
                discountType: $this->discountType,
                discountValue: $this->discountValue,
                taxPercentage: $this->taxPercentage,
                amountPaid: $this->amountPaid,
                customerName: $this->customerName,
                customerPhone: $this->customerPhone,
                resumeTransactionId: $this->resumingTransactionId,
            );
        } catch (RuntimeException $e) {
            $this->addError('checkout', $e->getMessage());

            return;
        }

        $this->resetCart();
        $this->showPaymentModal = false;
        unset($this->heldOrders);

        $isPendingQris = $transaction->payment_method !== PaymentMethod::CASH->value
            && $transaction->payment_status->value === 'pending';

        if ($isPendingQris && $transaction->qris_url) {
            $this->qrisUrl = $transaction->qris_url;
            $this->qrisInvoiceNumber = $transaction->invoice_number;
            $this->qrisTransactionId = $transaction->id;
            $this->showQrisModal = true;

            return;
        }

        if ($isPendingQris) {
            // Gateway call failed (see GenerateQrisCode listener log) — the
            // sale is still recorded, just without a scannable code yet.
            $this->addError('checkout', 'Transaksi tercatat, tapi kode QR gagal dibuat. Periksa payment gateway atau coba bayar tunai.');
        }

        $this->dispatch('transaction-completed', invoiceNumber: $transaction->invoice_number, transactionId: $transaction->id);
    }

    /**
     * Polled every few seconds by the QRIS modal (wire:poll) while waiting
     * for MidtransWebhookController to mark the transaction paid.
     */
    public function refreshQrisStatus(): void
    {
        if (! $this->qrisTransactionId) {
            return;
        }

        $isPaid = Transaction::whereKey($this->qrisTransactionId)
            ->where('payment_status', 'paid')
            ->exists();

        if (! $isPaid) {
            return;
        }

        $invoiceNumber = $this->qrisInvoiceNumber;
        $transactionId = $this->qrisTransactionId;

        $this->showQrisModal = false;
        $this->reset(['qrisUrl', 'qrisInvoiceNumber', 'qrisTransactionId']);

        $this->dispatch('transaction-completed', invoiceNumber: $invoiceNumber, transactionId: $transactionId);
    }

    public function holdOrder(CheckoutService $service): void
    {
        try {
            $service->hold($this->cart, Auth::user(), $this->activeShift);
        } catch (RuntimeException $e) {
            $this->addError('cart', $e->getMessage());

            return;
        }

        $this->resetCart();
        unset($this->heldOrders);
        $this->dispatch('order-held');
    }

    public function resumeOrder(int $transactionId): void
    {
        $transaction = Transaction::with('items.product.productStocks')
            ->where('status', TransactionStatus::HELD)
            ->findOrFail($transactionId);

        $outletId = $this->currentOutlet->id;

        $this->cart = $transaction->items->mapWithKeys(fn ($item) => [
            (string) $item->product_id => [
                'product_id' => $item->product_id,
                'name' => $item->product_name,
                'sku' => $item->product_sku,
                'price' => (float) $item->price,
                'cost_price' => (float) $item->cost_price,
                'quantity' => $item->quantity,
                'max_quantity' => max($item->product?->quantityAt($outletId) ?? 0, $item->quantity),
            ],
        ])->all();

        $this->resumingTransactionId = $transaction->id;
        $this->showHeldOrders = false;
    }

    public function deleteHeldOrder(int $transactionId): void
    {
        Transaction::where('status', TransactionStatus::HELD)->whereKey($transactionId)->delete();
        unset($this->heldOrders);
    }

    public function clearCart(): void
    {
        $this->resetCart();
    }

    public function openShift(): void
    {
        $this->validate(['startingCash' => 'required|numeric|min:0']);

        Shift::create([
            'outlet_id' => $this->currentOutlet->id,
            'user_id' => Auth::id(),
            'starting_cash' => $this->startingCash,
            'status' => ShiftStatus::OPEN,
            'opened_at' => now(),
        ]);

        unset($this->activeShift);
        $this->showOpenShiftModal = false;
        $this->reset('startingCash');
    }

    public function closeShift(): void
    {
        $this->validate(['actualCash' => 'required|numeric|min:0']);

        $shift = $this->activeShift;

        $expectedCash = (float) $shift->starting_cash + (float) $shift->transactions()
            ->where('payment_method', PaymentMethod::CASH->value)
            ->where('payment_status', 'paid')
            ->sum('paid_amount');

        $shift->update([
            'expected_cash' => $expectedCash,
            'actual_cash' => $this->actualCash,
            'difference' => round($this->actualCash - $expectedCash, 2),
            'status' => ShiftStatus::CLOSED,
            'closed_at' => now(),
        ]);

        unset($this->activeShift);
        $this->showCloseShiftModal = false;
        $this->showOpenShiftModal = true;
        $this->reset('actualCash');
    }

    private function resetCart(): void
    {
        $this->reset([
            'cart', 'discountType', 'discountValue', 'amountPaid',
            'customerName', 'customerPhone', 'resumingTransactionId',
        ]);
        $this->taxPercentage = $this->defaultTaxPercentage();
        $this->paymentMethod = 'cash';
    }

    private function defaultTaxPercentage(): float
    {
        return (float) Setting::get('tax_percentage', 0);
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/login', navigate: true);
    }

    public function render()
    {
        return view('livewire.pos.terminal');
    }
}
