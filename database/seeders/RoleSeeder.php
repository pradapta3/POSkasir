<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Superadmin',
                'slug' => RoleEnum::SUPERADMIN->value,
                'description' => 'Akses penuh ke seluruh sistem dan toko.',
            ],
            [
                'name' => 'Manajer Toko',
                'slug' => RoleEnum::MANAGER->value,
                'description' => 'Mengelola stok, staf, dan laporan toko.',
            ],
            [
                'name' => 'Kasir',
                'slug' => RoleEnum::CASHIER->value,
                'description' => 'Mengoperasikan kasir dan mengelola shift sendiri.',
            ],
            [
                'name' => 'Admin Platform',
                'slug' => RoleEnum::PLATFORM_ADMIN->value,
                'description' => 'Meninjau dan menyetujui pendaftaran toko baru di seluruh platform.',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
