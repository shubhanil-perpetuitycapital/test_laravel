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

    public function badgeClass(): string
    {
        return match ($this) {
            self::Unpaid => 'text-bg-light border',
            self::Submitted => 'text-bg-warning',
            self::Paid => 'text-bg-success',
            self::Refunded => 'text-bg-secondary',
        };
    }

    /** @return array<string,string> */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
