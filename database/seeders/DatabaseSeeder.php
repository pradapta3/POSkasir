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

        // No tax charged by default — most small merchants using this app
        // aren't PKP-registered. Adjustable at any time via Pengaturan.
        Setting::set('tax_percentage', '0');
    }
}
