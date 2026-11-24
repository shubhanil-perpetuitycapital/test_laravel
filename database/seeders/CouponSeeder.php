<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    /** One example code, switched off: the owner turns it on in Admin → Coupons. */
    public function run(): void
    {
        Coupon::query()->firstOrCreate(['code' => 'WELCOME10'], [
            'description' => 'Example: 10% off a first order (inactive until you switch it on)',
            'type' => 'percent',
            'value' => 10,
            'min_order_amount' => 800,
            'max_discount' => 200,
            'is_active' => false,
        ]);
    }
}
