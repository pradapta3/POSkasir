<?php

namespace App\Services\Pos;

use App\Enums\DiscountType;
use App\Enums\LoyaltyMovementType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementType;
use App\Enums\TransactionStatus;
use App\Events\TransactionCheckedOut;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Inventory\StockAdjustmentService;
use App\Services\Loyalty\LoyaltyService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * All database-touching POS operations (checkout, hold). Kept separate from
 * the Livewire\Pos\Terminal component so the component stays a thin
 * presentation layer and this logic stays independently testable. The
 * outlet a sale belongs to is always taken from the cashier's Shift (a
 * shift is opened at one specific outlet), never passed separately.
 */
class CheckoutService
{
    public function __construct(
        private readonly CartCalculator $calculator,
        private readonly StockAdjustmentService $stock,
        private readonly LoyaltyService $loyalty,
    ) {
    }

    /**
     * Finalize a sale: persist the transaction + line items, deduct stock,
     * settle any loyalty-point redemption/earning, and fire
     * TransactionCheckedOut. Every payment method completes immediately —
     * QRIS is a static store-wide code (Setting 'qris_image_path'), so the
     * cashier confirms payment visually the same way they would for cash.
     *
     * $customerId (an existing member picked at checkout) takes priority
     * over $customerName/$customerPhone, which only resolve-or-create a
     * plain (non-member) Customer for WhatsApp-invoice purposes unless
     * $enrollAsMember is set.
     *
     * @param  array<string, array{product_id:int,name:string,sku:string,price:float,cost_price:float,quantity:int}>  $cart
     */
    public function checkout(
        array $cart,
        User $cashier,
        Shift $shift,
        string $paymentMethod,
        ?string $discountType,
        float $discountValue,
        float $taxPercentage,
        float $amountPaid,
        ?string $customerName = null,
        ?string $customerPhone = null,
        ?int $customerId = null,
        int $redeemPoints = 0,
        bool $enrollAsMember = false,
        ?int $resumeTransactionId = null,
    ): Transaction {
        if (empty($cart)) {
            throw new RuntimeException('Keranjang masih kosong, tidak bisa membayar.');
        }

        $transaction = DB::transaction(function () use (
            $cart, $cashier, $shift, $paymentMethod, $discountType, $discountValue,
            $taxPercentage, $amountPaid, $customerName, $customerPhone, $customerId,
            $redeemPoints, $enrollAsMember, $resumeTransactionId,
        ) {
            $cart = $this->verifyCart($cart, $shift->outlet_id);

            $customer = $customerId
                ? Customer::findOrFail($customerId)
                : $this->resolveCustomer($customerName, $customerPhone, $enrollAsMember);

            $pointsDiscountAmount = $this->validateAndPriceRedemption($customer, $redeemPoints);

            $totals = $this->calculator->totals(
                $cart,
                $discountType ? DiscountType::from($discountType) : null,
                $discountValue,
                $taxPercentage,
                $pointsDiscountAmount,
            );

            $isCash = $paymentMethod === PaymentMethod::CASH->value;

            if ($isCash && $amountPaid < $totals['grandTotal']) {
                throw new RuntimeException('Jumlah uang yang dibayar kurang dari total.');
            }

            $pointsEarned = $this->earnedPoints($customer, (float) $totals['grandTotal']);

            $transaction = $resumeTransactionId
                ? Transaction::where('status', TransactionStatus::HELD)->lockForUpdate()->findOrFail($resumeTransactionId)
                : new Transaction(['invoice_number' => $this->nextInvoiceNumber($shift->outlet_id)]);

            $transaction->fill([
                'outlet_id' => $shift->outlet_id,
                'user_id' => $cashier->id,
                'shift_id' => $shift->id,
                'customer_id' => $customer?->id,
                'subtotal' => $totals['subtotal'],
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_amount' => $totals['discountAmount'],
                'loyalty_discount_amount' => $totals['pointsDiscountAmount'],
                'loyalty_points_redeemed' => $redeemPoints,
                'loyalty_points_earned' => $pointsEarned,
                'tax_percentage' => $taxPercentage,
                'tax_amount' => $totals['taxAmount'],
                'grand_total' => $totals['grandTotal'],
                'paid_amount' => $isCash ? $amountPaid : $totals['grandTotal'],
                'change_amount' => $isCash ? round($amountPaid - $totals['grandTotal'], 2) : 0,
                'payment_method' => $paymentMethod,
                'payment_status' => PaymentStatus::PAID,
                'status' => TransactionStatus::COMPLETED,
                'held_at' => null,
                'paid_at' => now(),
            ]);
            $transaction->save();

            // Replace any previously-held line items with the final cart contents.
            $transaction->items()->delete();

            foreach ($cart as $line) {
                $transaction->items()->create([
                    'product_id' => $line['product_id'],
                    'product_name' => $line['name'],
                    'product_sku' => $line['sku'],
                    'price' => $line['price'],
                    'cost_price' => $line['cost_price'],
                    'quantity' => $line['quantity'],
                    'discount_amount' => 0,
                    'subtotal' => $line['price'] * $line['quantity'],
                ]);

                $this->stock->adjust(
                    productId: $line['product_id'],
                    outletId: $shift->outlet_id,
                    delta: -$line['quantity'],
                    type: StockMovementType::OUT,
                    actor: $cashier,
                    reference: $transaction,
                );
            }

            if ($redeemPoints > 0) {
                $this->loyalty->adjust(
                    customer: $customer,
                    delta: -$redeemPoints,
                    type: LoyaltyMovementType::REDEEM,
                    actor: $cashier,
                    notes: "Ditukar untuk transaksi {$transaction->invoice_number}",
                    reference: $transaction,
                );
            }

            if ($pointsEarned > 0) {
                $this->loyalty->adjust(
                    customer: $customer,
                    delta: $pointsEarned,
                    type: LoyaltyMovementType::EARN,
                    actor: $cashier,
                    notes: "Dari transaksi {$transaction->invoice_number}",
                    reference: $transaction,
                );
            }

            $transaction->load(['items', 'customer']);

            return $transaction;
        });

        // Dispatched after the DB transaction commits — TransactionCheckedOut
        // listeners (QueueWhatsAppInvoice, when QUEUE_CONNECTION=sync) may
        // make an external HTTP call, which must never happen while row
        // locks from the block above are held.
        event(new TransactionCheckedOut($transaction));

        return $transaction;
    }

    /**
     * Park the current cart as a HELD transaction with no stock impact,
     * so it can be resumed later (by this cashier or another one).
     *
     * @param  array<string, array{product_id:int,name:string,sku:string,price:float,cost_price:float,quantity:int}>  $cart
     */
    public function hold(array $cart, User $cashier, Shift $shift, ?string $notes = null): Transaction
    {
        if (empty($cart)) {
            throw new RuntimeException('Keranjang masih kosong, tidak bisa ditahan.');
        }

        return DB::transaction(function () use ($cart, $cashier, $shift, $notes) {
            $cart = $this->verifyCart($cart, $shift->outlet_id);

            $totals = $this->calculator->totals($cart, null, 0, 0);

            $transaction = Transaction::create([
                'outlet_id' => $shift->outlet_id,
                'invoice_number' => $this->nextInvoiceNumber($shift->outlet_id),
                'user_id' => $cashier->id,
                'shift_id' => $shift->id,
                'subtotal' => $totals['subtotal'],
                'grand_total' => $totals['subtotal'],
                'status' => TransactionStatus::HELD,
                'payment_status' => PaymentStatus::PENDING,
                'held_at' => now(),
                'notes' => $notes,
            ]);

            foreach ($cart as $line) {
                $transaction->items()->create([
                    'product_id' => $line['product_id'],
                    'product_name' => $line['name'],
                    'product_sku' => $line['sku'],
                    'price' => $line['price'],
                    'cost_price' => $line['cost_price'],
                    'quantity' => $line['quantity'],
                    'discount_amount' => 0,
                    'subtotal' => $line['price'] * $line['quantity'],
                ]);
            }

            return $transaction->load('items');
        });
    }

    /**
     * Re-fetches every cart line's product from the database and rebuilds
     * the cart from the server's own name/price/cost_price — $cart is a
     * Livewire component property, which means the client controls
     * whatever's in it, so nothing in the incoming array (least of all
     * price) can be trusted to move money or stock without this check
     * first. Product::active() is already scoped to the cashier's company
     * via CompanyScope, so a foreign, deleted, or deactivated product_id
     * simply won't be found here and fails the whole checkout instead of
     * silently being dropped or trusted.
     *
     * @param  array<string, array{product_id:int,quantity:int}>  $cart
     * @return array<string, array{product_id:int,name:string,sku:string,price:float,cost_price:float,quantity:int}>
     *
     * @throws RuntimeException if a line's product can't be found or asks for more than is in stock.
     */
    private function verifyCart(array $cart, int $outletId): array
    {
        $products = Product::active()
            ->whereIn('id', array_column($cart, 'product_id'))
            ->get()
            ->keyBy('id');

        $verified = [];

        foreach ($cart as $key => $line) {
            $product = $products->get($line['product_id']);

            if (! $product) {
                throw new RuntimeException('Salah satu produk di keranjang sudah tidak tersedia. Muat ulang keranjang.');
            }

            $quantity = (int) $line['quantity'];

            if ($quantity < 1) {
                throw new RuntimeException('Jumlah produk di keranjang tidak valid.');
            }

            // Only a friendlier message than the negative-stock guard in
            // StockAdjustmentService — that guard (checked under a row
            // lock) is what actually protects against a concurrent sale of
            // the same last unit; this plain read can't.
            if ($quantity > $product->quantityAt($outletId)) {
                throw new RuntimeException("Stok {$product->name} tidak mencukupi.");
            }

            $verified[$key] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => (float) $product->selling_price,
                'cost_price' => (float) $product->cost_price,
                'quantity' => $quantity,
            ];
        }

        return $verified;
    }

    /**
     * Not every phone captured at checkout is a member — only enrollAsMember
     * (ticked at the terminal) or the Admin\Members screen turns a plain
     * Customer into one. A plain capture still resolves/creates the row so
     * WhatsApp invoicing keeps working exactly as before.
     */
    private function resolveCustomer(?string $name, ?string $phone, bool $enrollAsMember): ?Customer
    {
        if (! $phone) {
            return null;
        }

        // Company-scoped automatically (Customer uses BelongsToCompany) —
        // this can never match or leak into another company's customer
        // record even if the phone number happens to coincide.
        $customer = Customer::firstOrCreate(
            ['phone' => $phone],
            ['name' => $name ?: $phone],
        );

        if ($enrollAsMember) {
            $this->loyalty->enroll($customer);
        }

        return $customer;
    }

    /**
     * Rupiah value of $redeemPoints against the store's configured
     * loyalty_redeem_value — 0 if the program is off, no points were
     * requested, or the requested amount fails validation (caller passes
     * 0 through so an invalid/stale UI state can't silently redeem
     * something the member didn't actually have).
     *
     * @throws RuntimeException if points were requested but can't be honored.
     */
    private function validateAndPriceRedemption(?Customer $customer, int $redeemPoints): float
    {
        if ($redeemPoints <= 0) {
            return 0.0;
        }

        if (! Setting::get('loyalty_enabled')) {
            throw new RuntimeException('Program loyalitas sedang tidak aktif.');
        }

        if (! $customer || ! $customer->is_member) {
            throw new RuntimeException('Pelanggan ini bukan member, tidak bisa menukar poin.');
        }

        $minRedeem = (int) Setting::get('loyalty_min_redeem_points', 0);

        if ($customer->loyalty_points < max($redeemPoints, $minRedeem)) {
            throw new RuntimeException('Poin member tidak mencukupi untuk penukaran ini.');
        }

        $redeemValue = (float) Setting::get('loyalty_redeem_value', 0);

        if ($redeemValue <= 0) {
            throw new RuntimeException('Nilai tukar poin belum diatur oleh Superadmin.');
        }

        return $redeemPoints * $redeemValue;
    }

    /**
     * Earned on grand_total (what was actually paid, after any discount
     * and point redemption already netted out) — not the pre-discount
     * subtotal, so redeeming points doesn't also farm fresh ones on the
     * amount just wiped out.
     */
    private function earnedPoints(?Customer $customer, float $grandTotal): int
    {
        if (! $customer || ! $customer->is_member || $grandTotal <= 0) {
            return 0;
        }

        if (! Setting::get('loyalty_enabled')) {
            return 0;
        }

        $earnPerRupiah = (float) Setting::get('loyalty_earn_per_rupiah', 0);

        if ($earnPerRupiah <= 0) {
            return 0;
        }

        return (int) floor($grandTotal / $earnPerRupiah);
    }

    /**
     * Sequential per-outlet invoice number: INV-YYYYMMDD-0001. Scoped to
     * outlet (matching the unique constraint on transactions), so two
     * outlets under the same company can both have their own "0001" today.
     *
     * lockForUpdate() here only protects against races within this process's
     * surrounding DB::transaction(); at very high concurrency (many terminals
     * writing the same millisecond) a dedicated atomic counter table would be
     * more robust. Fine for typical single/few-terminal-per-outlet usage.
     */
    private function nextInvoiceNumber(int $outletId): string
    {
        $prefix = 'INV-'.now()->format('Ymd').'-';

        $lastNumber = Transaction::withTrashed()
            ->where('outlet_id', $outletId)
            ->where('invoice_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $nextSequence = $lastNumber ? ((int) substr($lastNumber, -4)) + 1 : 1;

        return $prefix.str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
    }
}
