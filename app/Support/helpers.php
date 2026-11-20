<?php

use App\Services\SettingService;

if (! function_exists('setting')) {
    /**
     * Read an admin-configured setting (cached).
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return app(SettingService::class)->get($key, $default);
    }
}

if (! function_exists('media_url')) {
    /**
     * Public URL for an uploaded or bundled image.
     *
     * - "images/..."  bundled assets in /public/images (seeded placeholders)
     * - anything else  files on the "uploads" disk (/public/uploads)
     */
    function media_url(?string $path, ?string $fallback = 'images/placeholders/product-dark.svg'): ?string
    {
        if (blank($path)) {
            return $fallback ? asset($fallback) : null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, 'images/')) {
            return asset($path);
        }

        return asset('uploads/'.ltrim($path, '/'));
    }
}

if (! function_exists('money')) {
    /**
     * Format an amount in Indian Rupees with Indian digit grouping (₹1,25,000).
     * Implemented without the intl extension, which many shared hosts lack.
     */
    function money(float|int|string|null $amount, bool $decimals = false): string
    {
        $amount = (float) $amount;
        $negative = $amount < 0;
        $amount = abs($amount);

        $formatted = number_format($amount, $decimals ? 2 : 0, '.', '');
        $parts = explode('.', $formatted);
        $whole = $parts[0];
        $fraction = isset($parts[1]) ? '.'.$parts[1] : '';

        if (strlen($whole) > 3) {
            $last3 = substr($whole, -3);
            $rest = substr($whole, 0, -3);
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $whole = $rest.','.$last3;
        }

        return ($negative ? '-' : '').'₹'.$whole.$fraction;
    }
}
