<?php

namespace App\Enums;

enum CouponType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::Percentage => 'Percentage',
            self::Fixed => 'Fixed amount',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Percentage => 'blue',
            self::Fixed => 'amber',
        };
    }
}
