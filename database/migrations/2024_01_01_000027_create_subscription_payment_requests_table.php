<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The manual "I've transferred, please confirm" claim a company's
 * Superadmin files after a bank transfer — there's no live payment
 * gateway wired up yet (see Billing\Index docblock), so a Platform Admin
 * reviews and confirms each one by hand, same spirit as the static QRIS
 * flow. Deliberately has no BelongsToCompany: a Platform Admin must see
 * every company's requests, and CompanyScope would silently filter that
 * down to just their own anchor company (the exact bug already found and
 * fixed once on Outlet's withCount in Platform\Companies\Index).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_payment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->restrictOnDelete();
            $table->unsignedInteger('months');
            // Snapshot of plan price * months at request time, so a later
            // price change never rewrites what was actually agreed/paid.
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payment_requests');
    }
};
