<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Defaults to 'approved' so every company that already exists (created
     * before this Platform Admin approval step existed) is grandfathered
     * in untouched — only new self-service registrations explicitly set
     * 'pending' going forward; see Livewire\Auth\Register.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('status')->default('approved')->after('is_active');
            $table->text('rejection_reason')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['status', 'rejection_reason', 'approved_at']);
        });
    }
};
