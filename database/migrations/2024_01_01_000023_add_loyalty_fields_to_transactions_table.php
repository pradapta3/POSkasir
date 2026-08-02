<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Separate from the existing manual discount_amount — a sale can have both
 * a manual discount AND a points redemption applied at once, and reports/
 * receipts need to tell them apart.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('loyalty_discount_amount', 15, 2)->default(0)->after('discount_amount');
            $table->unsignedInteger('loyalty_points_redeemed')->default(0)->after('loyalty_discount_amount');
            $table->unsignedInteger('loyalty_points_earned')->default(0)->after('loyalty_points_redeemed');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['loyalty_discount_amount', 'loyalty_points_redeemed', 'loyalty_points_earned']);
        });
    }
};
