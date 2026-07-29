<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case QRIS = 'qris';
    case GOPAY = 'gopay';
    case CARD = 'card';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Tunai',
            self::QRIS => 'QRIS',
            self::GOPAY => 'GoPay',
            self::CARD => 'Kartu',
            self::OTHER => 'Lainnya',
        };
    }
}
