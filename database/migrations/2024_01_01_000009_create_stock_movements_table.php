<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();

            $table->string('type'); // in | out | adjustment
            $table->integer('quantity'); // signed delta actually applied
            $table->integer('quantity_before');
            $table->integer('quantity_after');

            // Polymorphic origin (e.g. a Transaction for sale deductions, or
            // null for a manual stock-opname adjustment).
            $table->nullableMorphs('reference');

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
