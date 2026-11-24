<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\GalleryItem;
use Illuminate\Database\Seeder;

class HomepageContentSeeder extends Seeder
{
    public function run(): void
    {
        if (Banner::query()->doesntExist()) {
            Banner::query()->create([
                'title' => 'Chocolate, tempered by hand in small batches',
                'subtitle' => 'Bars, truffles and gift boxes made to order in Kolkata. Same-day delivery across the city and courier to the rest of India.',
                'image' => 'images/placeholders/hero.svg',
                'button_text' => 'Shop chocolates',
                'button_url' => '/shop',
                'sort_order' => 1,
                'is_active' => true,
            ]);
        }

        // Placeholder tiles for the Instagram grid; replace with real photos in Admin → Instagram gallery.
        if (GalleryItem::query()->doesntExist()) {
            $images = ['truffles.svg', 'product-ruby.svg', 'giftbox-gold.svg', 'bonbons.svg',
                'product-wrapped.svg', 'giftbox-pink.svg', 'product-milk.svg', 'giftbox-cream.svg'];

            foreach ($images as $i => $image) {
                GalleryItem::query()->create(['image' => 'images/placeholders/'.$image, 'sort_order' => $i, 'is_active' => true]);
            }
        }
    }
}
