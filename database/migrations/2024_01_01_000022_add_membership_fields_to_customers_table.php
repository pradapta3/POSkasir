<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Not every Customer row is a "member" — a phone captured at checkout just
 * for a WhatsApp invoice stays a plain Customer. Membership (and loyalty
 * points) is an explicit opt-in: enrolled via Admin\Members or the "Daftarkan
 * sebagai member baru" checkbox at checkout. See LoyaltyService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('is_member')->default(false)->after('address');
            $table->string('member_code')->nullable()->after('is_member');
            $table->unsignedInteger('loyalty_points')->default(0)->after('member_code');
            $table->timestamp('member_since')->nullable()->after('loyalty_points');

            $table->unique(['company_id', 'member_code']);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'member_code']);
            $table->dropColumn(['is_member', 'member_code', 'loyalty_points', 'member_since']);
        });
    }
};
