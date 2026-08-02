<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail for every change to a member's loyalty_points balance — mirrors
 * stock_movements' shape (before/after snapshot + polymorphic reference)
 * for the same reason: "why does this member have this many points" needs
 * a real answer, not just the running total on customers.loyalty_points.
 * Points are a company-wide concept (not per-outlet), unlike stock.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_point_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();

            $table->string('type'); // earn | redeem | adjustment
            $table->integer('points'); // signed delta actually applied
            $table->integer('points_before');
            $table->integer('points_after');

            // Polymorphic origin (a Transaction for earn/redeem, null for a
            // manual admin adjustment).
            $table->nullableMorphs('reference');

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_point_movements');
    }
};
