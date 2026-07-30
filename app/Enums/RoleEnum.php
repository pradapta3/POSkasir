<?php

namespace App\Enums;

enum RoleEnum: string
{
    case SUPERADMIN = 'superadmin';
    case MANAGER = 'manager';
    case CASHIER = 'cashier';

    // The SaaS operator, not a store's own admin — reviews and
    // approves/rejects new company signups. See EnsureCompanyIsApproved.
    case PLATFORM_ADMIN = 'platform_admin';

    public function label(): string
    {
        return match ($this) {
            self::SUPERADMIN => 'Superadmin',
            self::MANAGER => 'Manajer Toko',
            self::CASHIER => 'Kasir',
            self::PLATFORM_ADMIN => 'Admin Platform',
        };
    }
}
