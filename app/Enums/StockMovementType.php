<?php

namespace App\Enums;

enum StockMovementType: string
{
    case IN = 'in';
    case OUT = 'out';
    case ADJUSTMENT = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::IN => 'Stok Masuk',
            self::OUT => 'Stok Keluar',
            self::ADJUSTMENT => 'Penyesuaian',
        };
    }
}
