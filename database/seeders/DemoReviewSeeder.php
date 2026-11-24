<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Database\Seeder;

/**
 * DEMO DATA FOR LOCAL DEVELOPMENT ONLY.
 *
 * Lets you see the review sections while building. DatabaseSeeder skips
 * this when APP_ENV=production: publishing invented reviews on a real
 * store would mislead customers. Every demo review is labelled as such
 * and can be deleted in Admin → Reviews.
 */
class DemoReviewSeeder extends Seeder
{
    public function run(): void
    {
        if (Review::query()->where('location', 'Demo review')->exists()) {
            return;
        }

        $reviews = [
            ['bar-lovers-trio', 5, 'Demo text: the dark bar had a lovely snap and the box arrived beautifully wrapped.'],
            ['salted-caramel-bonbons', 5, 'Demo text: runny caramel centre, not too sweet. Ordered a second box the same week.'],
            ['signature-assorted-box', 4, 'Demo text: gifted this for a birthday and everyone asked where it was from.'],
            ['nolen-gur-milk-bar', 5, 'Demo text: tastes like winter in Kolkata. Smoky and sweet.'],
        ];

        foreach ($reviews as $i => [$slug, $rating, $comment]) {
            Review::query()->create([
                'product_id' => Product::query()->where('slug', $slug)->value('id'),
                'customer_name' => 'Sample customer '.($i + 1),
                'location' => 'Demo review',
                'rating' => $rating,
                'comment' => $comment,
                'is_approved' => true,
                'is_featured' => true,
            ]);
        }
    }
}
