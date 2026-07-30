<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * These columns were globally unique in the single-tenant schema —
     * two different companies must now be free to both use SKU "001" or
     * both name a category "Minuman" independently. Invoice numbers move
     * to unique-per-outlet (not per-company) since CheckoutService's
     * sequence generator already scopes "today's transactions" the same way.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_slug_unique');
            $table->unique(['company_id', 'slug']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_sku_unique');
            $table->dropUnique('products_barcode_unique');
            $table->unique(['company_id', 'sku']);
            $table->unique(['company_id', 'barcode']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_phone_unique');
            $table->unique(['company_id', 'phone']);
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique('settings_key_unique');
            $table->unique(['company_id', 'key']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique('transactions_invoice_number_unique');
            $table->unique(['outlet_id', 'invoice_number']);
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'slug']);
            $table->unique('slug');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'sku']);
            $table->dropUnique(['company_id', 'barcode']);
            $table->unique('sku');
            $table->unique('barcode');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'phone']);
            $table->unique('phone');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'key']);
            $table->unique('key');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['outlet_id', 'invoice_number']);
            $table->unique('invoice_number');
        });
    }
};
