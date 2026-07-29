<?php

namespace App\Services\Pos;

use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementType;
use App\Enums\TransactionStatus;
use App\Events\TransactionCheckedOut;
use App\Models\Customer;
use App\Models\Shift;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Inventory\StockAdjustmentService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * All database-touching POS operations (checkout, hold). Kept separate from
 * the Livewire\Pos\Terminal component so the component stays a thin
 * presentation layer and this logic stays independently testable.
 */
class CheckoutService
{
    public function __construct(
        private readonly CartCalculator $calculator,
        private readonly StockAdjustmentService $stock,
    ) {
    }

    /**
     * Finalize a sale: persist the transaction + line items, deduct stock,
     * and fire TransactionCheckedOut. Stock is deducted immediately for every
     * payment method (including pending QRIS/GoPay) because in an in-person
     * retail POS the goods leave the shelf at the counter, not when a webhook
     * confirms payment — unlike an e-commerce checkout.
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
        ?int $resumeTransactionId = null,
    ): Transaction {
        if (empty($cart)) {
            throw new RuntimeException('Keranjang masih kosong, tidak bisa membayar.');
        }

        $transaction = DB::transaction(function () use (
            $cart, $cashier, $shift, $paymentMethod, $discountType, $discountValue,
            $taxPercentage, $amountPaid, $customerName, $customerPhone, $resumeTransactionId,
        ) {
            $totals = $this->calculator->totals(
                $cart,
                $discountType ? DiscountType::from($discountType) : null,
                $discountValue,
                $taxPercentage,
            );

            $isCash = $paymentMethod === PaymentMethod::CASH->value;

            if ($isCash && $amountPaid < $totals['grandTotal']) {
                throw new RuntimeException('Jumlah uang yang dibayar kurang dari total.');
            }

            $customer = $this->resolveCustomer($customerName, $customerPhone);

            $transaction = $resumeTransactionId
                ? Transaction::where('status', TransactionStatus::HELD)->lockForUpdate()->findOrFail($resumeTransactionId)
                : new Transaction(['invoice_number' => $this->nextInvoiceNumber()]);

            $transaction->fill([
                'user_id' => $cashier->id,
                'shift_id' => $shift->id,
                'customer_id' => $customer?->id,
                'subtotal' => $totals['subtotal'],
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_amount' => $totals['discountAmount'],
                'tax_percentage' => $taxPercentage,
                'tax_amount' => $totals['taxAmount'],
                'grand_total' => $totals['grandTotal'],
                'paid_amount' => $isCash ? $amountPaid : 0,
                'change_amount' => $isCash ? round($amountPaid - $totals['grandTotal'], 2) : 0,
                'payment_method' => $paymentMethod,
                'payment_status' => $isCash ? PaymentStatus::PAID : PaymentStatus::PENDING,
                'status' => TransactionStatus::COMPLETED,
                'held_at' => null,
                'paid_at' => $isCash ? now() : null,
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
                    delta: -$line['quantity'],
                    type: StockMovementType::OUT,
                    actor: $cashier,
                    reference: $transaction,
                );
            }

            $transaction->load(['items', 'customer']);

            return $transaction;
        });

        // Dispatched after the DB transaction commits — TransactionCheckedOut
        // listeners (e.g. GenerateQrisCode) make an external HTTP call, which
        // must never happen while row locks from the block above are held.
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
            $totals = $this->calculator->totals($cart, null, 0, 0);

            $transaction = Transaction::create([
                'invoice_number' => $this->nextInvoiceNumber(),
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

    private function resolveCustomer(?string $name, ?string $phone): ?Customer
    {
        if (! $phone) {
            return null;
        }

        return Customer::firstOrCreate(
            ['phone' => $phone],
            ['name' => $name ?: $phone],
        );
    }

    /**
     * Sequential per-day invoice number: INV-YYYYMMDD-0001.
     *
     * lockForUpdate() here only protects against races within this process's
     * surrounding DB::transaction(); at very high concurrency (many terminals
     * writing the same millisecond) a dedicated atomic counter table would be
     * more robust. Fine for typical single/few-terminal store usage.
     */
    private function nextInvoiceNumber(): string
    {
        $prefix = 'INV-'.now()->format('Ymd').'-';

        $lastNumber = Transaction::withTrashed()
            ->where('invoice_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $nextSequence = $lastNumber ? ((int) substr($lastNumber, -4)) + 1 : 1;

        return $prefix.str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
    }
}
