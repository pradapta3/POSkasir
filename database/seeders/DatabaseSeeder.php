<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        User::updateOrCreate(
            ['email' => 'admin@poskasir.test'],
            [
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
            Setting::firstOrCreate(['key' => $key], ['value' => $default]);
        }
    }
}
