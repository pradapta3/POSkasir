<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Turns the app's existing single-tenant data into the first tenant:
     * every row created before multi-tenancy existed gets attached to a
     * "Toko Saya" company with one "Outlet Utama" outlet, and every
     * product's stock_quantity becomes that outlet's row in product_stocks.
     * A pure data migration — no schema changes here.
     */
    public function up(): void
    {
        $companyId = DB::table('companies')->insertGetId([
            'name' => 'Toko Saya',
            'slug' => 'toko-saya',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $outletId = DB::table('outlets')->insertGetId([
            'company_id' => $companyId,
            'name' => 'Outlet Utama',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['users', 'categories', 'products', 'customers', 'settings'] as $table) {
            DB::table($table)->whereNull('company_id')->update(['company_id' => $companyId]);
        }

        foreach (['transactions', 'shifts', 'stock_movements'] as $table) {
            DB::table($table)->whereNull('company_id')->update([
                'company_id' => $companyId,
                'outlet_id' => $outletId,
            ]);
        }

        // Cashiers are pinned to the one outlet that exists so far;
        // Manager/Superadmin keep outlet_id null (every outlet in the company).
        DB::table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('roles.slug', 'cashier')
            ->update(['users.outlet_id' => $outletId]);

        foreach (DB::table('products')->select('id', 'stock_quantity', 'low_stock_threshold')->get() as $product) {
            DB::table('product_stocks')->insert([
                'product_id' => $product->id,
                'outlet_id' => $outletId,
                'quantity' => $product->stock_quantity,
                'low_stock_threshold' => $product->low_stock_threshold,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Deliberately empty — reversing a data backfill is ambiguous
        // (can't tell which rows were touched by this migration vs.
        // created independently afterwards). Roll back the surrounding
        // schema migrations instead if this needs to be undone.
    }
};
