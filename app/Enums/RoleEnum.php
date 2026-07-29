<?php

namespace App\Enums;

enum RoleEnum: string
{
    case SUPERADMIN = 'superadmin';
    case MANAGER = 'manager';
    case CASHIER = 'cashier';

    public function label(): string
    {
        return match ($this) {
            self::SUPERADMIN => 'Superadmin',
            self::MANAGER => 'Manajer Toko',
            self::CASHIER => 'Kasir',
        };
    }
}
