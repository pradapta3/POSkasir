<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A goods receipt from a supplier — recorded after the fact once stock
     * has physically arrived (no draft/ordered/partial-receipt lifecycle),
     * matching this app's "start manual, simple, add stages later" pattern
     * already used for billing/payments. See PurchasingService.
     */
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outlet_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();

            $table->string('po_number');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();

            $table->timestamps();

            // Sequential per-outlet, same scheme as transactions.invoice_number.
            $table->unique(['outlet_id', 'po_number']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
