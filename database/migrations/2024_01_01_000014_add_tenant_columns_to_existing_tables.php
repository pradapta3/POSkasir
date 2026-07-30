<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nullable for now — a later migration in this same batch backfills
     * every existing row into a default company/outlet before a final
     * migration makes these columns NOT NULL. Splitting it this way avoids
     * ever having a not-null column with no valid value to put in it.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            // Null outlet_id = access to every outlet in the company
            // (Superadmin/Manager); a Cashier must have one assigned.
            $table->foreignId('outlet_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            $table->foreignId('outlet_id')->nullable()->after('company_id')->constrained()->restrictOnDelete();
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            $table->foreignId('outlet_id')->nullable()->after('company_id')->constrained()->restrictOnDelete();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            $table->foreignId('outlet_id')->nullable()->after('company_id')->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        foreach (['users', 'categories', 'products', 'customers', 'settings', 'transactions', 'shifts', 'stock_movements'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');

                if (Schema::hasColumn($tableName, 'outlet_id')) {
                    $table->dropForeign(['outlet_id']);
                    $table->dropColumn('outlet_id');
                }
            });
        }
    }
};
