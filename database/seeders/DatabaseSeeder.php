<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Company;
use App\Models\Outlet;
use App\Models\Role;
use App\Models\Setting;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        // On an upgrade from the pre-multitenant schema, the Fase 1 backfill
        // migration already created this company. On a brand new install
        // there's no Company/Outlet yet — User::create() and Setting::set()
        // both require one, so it must exist before anything below runs.
        $company = Company::firstOrCreate(
            ['slug' => 'toko-saya'],
            [
                'name' => 'Toko Saya',
                'owner_email' => 'admin@poskasir.test',
                'is_active' => true,
            ]
        );

        Outlet::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Outlet Utama'],
            ['is_active' => true]
        );

        User::updateOrCreate(
            ['email' => 'admin@poskasir.test'],
            [
                'company_id' => $company->id,
                'outlet_id' => null,
                'role_id' => Role::where('slug', RoleEnum::SUPERADMIN->value)->value('id'),
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
                'is_active' => true,
            ]
        );

        // Sensible starting defaults, editable any time via Pengaturan.
        // firstOrCreate (not set()) so re-running this seeder never
        // clobbers values a store has already configured.
        // No tax charged by default — most small merchants using this app
        // aren't PKP-registered.
        foreach ([
            'tax_percentage' => '0',
            'store_name' => 'Toko Saya',
            'store_address' => '',
            'store_phone' => '',
            'receipt_footer' => 'Terima kasih telah berbelanja!',
        ] as $key => $default) {
            Setting::firstOrCreate(['key' => $key, 'company_id' => $company->id], ['value' => $default]);
        }

        // The SaaS operator's own account — reviews/approves new company
        // signups at /platform/companies. Anchored to Toko Saya only to
        // satisfy users.company_id's NOT NULL constraint; unrelated to
        // their actual duties (see User::isPlatformAdmin()).
        User::updateOrCreate(
            ['email' => 'platform@poskasir.test'],
            [
                'company_id' => $company->id,
                'outlet_id' => null,
                'role_id' => Role::where('slug', RoleEnum::PLATFORM_ADMIN->value)->value('id'),
                'name' => 'Admin Platform',
                'password' => bcrypt('password'),
                'is_active' => true,
            ]
        );

        // Starting pricing tiers — Platform Admin can adjust these anytime
        // at /platform/plans without a code change. updateOrCreate (not
        // firstOrCreate) so re-running this seeder keeps limits/pricing in
        // sync with whatever's defined here, matching RoleSeeder's pattern.
        foreach ([
            ['name' => 'Basic', 'slug' => 'basic', 'price_per_month' => 99000, 'max_outlets' => 1, 'max_users' => 5, 'sort_order' => 1],
            ['name' => 'Pro', 'slug' => 'pro', 'price_per_month' => 249000, 'max_outlets' => 5, 'max_users' => 20, 'sort_order' => 2],
            ['name' => 'Enterprise', 'slug' => 'enterprise', 'price_per_month' => 499000, 'max_outlets' => null, 'max_users' => null, 'sort_order' => 3],
        ] as $plan) {
            SubscriptionPlan::updateOrCreate(['slug' => $plan['slug']], $plan + ['is_active' => true]);
        }
    }
}
