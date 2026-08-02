<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * subscription_ends_at is deliberately separate from the existing
 * trial_ends_at: trial_ends_at is the one-time free period set at
 * registration, subscription_ends_at is how far a *paid* period reaches.
 * The access gate (EnsureCompanyIsApproved) checks whichever is later —
 * see that middleware for the exact logic. Both nullable and untouched by
 * this migration's default, so every company that existed before this
 * feature keeps working exactly as before (no expiry enforced on them).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('subscription_plan_id')->nullable()->after('is_active')
                ->constrained('subscription_plans')->nullOnDelete();
            $table->timestamp('subscription_ends_at')->nullable()->after('subscription_plan_id');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscription_plan_id');
            $table->dropColumn('subscription_ends_at');
        });
    }
};
