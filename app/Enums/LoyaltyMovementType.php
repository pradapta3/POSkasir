<?php

namespace App\Enums;

enum LoyaltyMovementType: string
{
    case EARN = 'earn';
    case REDEEM = 'redeem';
    case ADJUSTMENT = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::EARN => 'Poin Didapat',
            self::REDEEM => 'Poin Ditukar',
            self::ADJUSTMENT => 'Penyesuaian',
        };
    }
}
