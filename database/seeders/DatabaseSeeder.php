<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * php artisan migrate --seed
     *
     * Safe to run again: seeders use firstOrCreate / updateOrCreate, so
     * re-seeding never duplicates rows or overwrites the owner's settings.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            SettingSeeder::class,
            CatalogueSeeder::class,
            PageSeeder::class,
            DeliverySeeder::class,
            HomepageContentSeeder::class,
            CouponSeeder::class,
        ]);

        // Demo reviews are for local development only — never on a live store.
        if (! app()->environment('production')) {
            $this->call(DemoReviewSeeder::class);
        }
    }
}
