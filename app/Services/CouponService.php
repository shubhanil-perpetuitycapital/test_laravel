<?php

namespace App\Services;

use App\Models\Coupon;

class CouponService
{
    /**
     * @return array{coupon: ?Coupon, discount: float, error: ?string}
     */
    public function evaluate(?string $code, float $subtotal, bool $lock = false): array
    {
        $code = strtoupper(trim((string) $code));

        if ($code === '') {
            return ['coupon' => null, 'discount' => 0.0, 'error' => null];
        }

        $query = Coupon::query()->where('code', $code);
        if ($lock) {
            $query->lockForUpdate();
        }
        $coupon = $query->first();

        $error = match (true) {
            ! $coupon, ! $coupon->is_active => 'This code is not valid.',
            $coupon->starts_at && $coupon->starts_at->isFuture() => 'This code is not active yet.',
            $coupon->expires_at && $coupon->expires_at->isPast() => 'This code has expired.',
            $coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit => 'This code has been fully used.',
            $coupon->min_order_amount && $subtotal < (float) $coupon->min_order_amount => 'Add '.money((float) $coupon->min_order_amount - $subtotal).' more to use this code (minimum '.money($coupon->min_order_amount).').',
            default => null,
        };

        if ($error) {
            return ['coupon' => $coupon, 'discount' => 0.0, 'error' => $error];
        }

        $discount = $coupon->type === 'percent'
            ? $subtotal * ((float) $coupon->value / 100)
            : (float) $coupon->value;

        if ($coupon->type === 'percent' && $coupon->max_discount) {
            $discount = min($discount, (float) $coupon->max_discount);
        }

        return ['coupon' => $coupon, 'discount' => round(min($discount, $subtotal), 2), 'error' => null];
    }
}
