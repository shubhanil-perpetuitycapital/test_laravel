<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case ReadyForDispatch = 'ready_for_dispatch';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Processing => 'Processing',
            self::ReadyForDispatch => 'Ready for dispatch',
            self::Shipped => 'Shipped',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }

    /** Plain-language explanation shown on the customer tracking page. */
    public function customerText(): string
    {
        return match ($this) {
            self::Pending => 'We have received your order and will confirm it shortly.',
            self::Confirmed => 'Your order is confirmed.',
            self::Processing => 'Your chocolates are being made and packed.',
            self::ReadyForDispatch => 'Your order is packed and ready to leave our kitchen.',
            self::Shipped => 'Your order is on its way.',
            self::Delivered => 'Your order has been delivered. Enjoy!',
            self::Cancelled => 'This order has been cancelled.',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'text-bg-warning',
            self::Confirmed, self::Processing => 'text-bg-info',
            self::ReadyForDispatch, self::Shipped => 'text-bg-primary',
            self::Delivered => 'text-bg-success',
            self::Cancelled => 'text-bg-secondary',
        };
    }

    /** Steps shown on the tracking timeline (cancelled is shown separately). */
    public static function journey(): array
    {
        return [self::Pending, self::Confirmed, self::Processing, self::ReadyForDispatch, self::Shipped, self::Delivered];
    }

    /** @return array<string,string> value => label */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
