<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QRIS is now a static, store-uploaded image (Setting 'qris_image_path')
 * shown to every customer regardless of transaction — there's no more
 * per-transaction dynamic code, gateway reference, or webhook to store.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['payment_gateway_reference']);
            $table->dropColumn(['payment_gateway_reference', 'qris_payload', 'qris_url']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('payment_gateway_reference')->nullable()->unique();
            $table->text('qris_payload')->nullable();
            $table->string('qris_url')->nullable();
        });
    }
};
