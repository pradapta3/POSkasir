<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every row now has a company_id (and outlet_id where applicable) from
     * the backfill migration, so it's safe to enforce NOT NULL from here on.
     * Requires doctrine/dbal (composer require doctrine/dbal) — see SETUP.md.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
            $table->foreignId('outlet_id')->nullable(false)->change();
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
            $table->foreignId('outlet_id')->nullable(false)->change();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
            $table->foreignId('outlet_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        foreach (['users', 'categories', 'products', 'customers', 'settings', 'transactions', 'shifts', 'stock_movements'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('company_id')->nullable()->change();
            });
        }

        foreach (['transactions', 'shifts', 'stock_movements'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('outlet_id')->nullable()->change();
            });
        }
    }
};
