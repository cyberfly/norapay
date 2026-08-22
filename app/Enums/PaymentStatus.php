<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Expired = 'expired';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Paid => 'Paid',
            self::Expired => 'Expired',
            self::Failed => 'Failed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Paid => 'green',
            self::Expired => 'zinc',
            self::Failed => 'red',
        };
    }

    /**
     * Map a Xendit invoice `status` value to our internal status.
     */
    public static function fromXenditStatus(string $status): self
    {
        return match (strtoupper($status)) {
            'PAID', 'SETTLED' => self::Paid,
            'EXPIRED' => self::Expired,
            'PENDING' => self::Pending,
            default => self::Failed,
        };
    }
}
