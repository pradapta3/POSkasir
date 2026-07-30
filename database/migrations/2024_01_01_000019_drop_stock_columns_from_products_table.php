<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stock now lives in product_stocks (per outlet), populated by the
     * backfill migration — these columns are no longer read anywhere.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_stock_quantity_index');
            $table->dropColumn(['stock_quantity', 'low_stock_threshold']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('stock_quantity')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->index('stock_quantity');
        });
    }
};
