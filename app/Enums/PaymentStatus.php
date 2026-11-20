<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Submitted = 'submitted';   // customer sent UPI reference, awaiting verification
    case Paid = 'paid';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::Submitted => 'Awaiting verification',
            self::Paid => 'Paid',
            self::Refunded => 'Refunded',
        };
    }
}
