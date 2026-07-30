<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stock is per-outlet, not per-product: the same catalog item (shared
     * across a company's branches) has an independent quantity on hand at
     * each outlet. Replaces products.stock_quantity/low_stock_threshold,
     * which are dropped once this table is backfilled (see the migrations
     * that follow this one).
     */
    public function up(): void
    {
        Schema::create('product_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->timestamps();

            $table->unique(['product_id', 'outlet_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stocks');
    }
};
