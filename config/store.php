<?php

/*
|--------------------------------------------------------------------------
| Store bootstrap configuration
|--------------------------------------------------------------------------
|
| Values here are only first-run defaults read from .env. Once the site is
| live the owner manages company details from Admin → Company setup, which
| stores them in the `settings` table and overrides these.
|
*/

return [
    'brand_name' => env('BRAND_NAME', 'Your Brand'),
    'business_phone' => env('BUSINESS_PHONE', '+91 90000 00000'),
    'business_whatsapp' => env('BUSINESS_WHATSAPP', '919000000000'),
    'business_email' => env('BUSINESS_EMAIL', 'hello@example.com'),

    // Default back-office account created by `php artisan db:seed`.
    'admin' => [
        'name' => env('ADMIN_NAME', 'Store Owner'),
        'email' => env('ADMIN_EMAIL', 'admin@example.com'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    // URL prefix for the back office, e.g. "admin" → /admin/login
    'admin_path' => env('ADMIN_PATH', 'admin'),

    'products_per_page' => (int) env('PRODUCTS_PER_PAGE', 12),
];
