<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * 3 categories and 15 sample products with placeholder illustrations from
 * public/images/placeholders. Replace photos in Admin → Products.
 */
class CatalogueSeeder extends Seeder
{
    private const MILK_NUTS = 'Contains milk and soy. Made in a kitchen that also handles tree nuts, peanuts and gluten.';

    public function run(): void
    {
        $categories = [
            'bars' => Category::query()->updateOrCreate(['slug' => 'chocolate-bars'], [
                'name' => 'Chocolate bars',
                'description' => 'Single-origin dark, creamy milk and flavoured bars, poured and wrapped by hand.',
                'image' => 'images/placeholders/category-bars.svg', 'display_order' => 1, 'is_active' => true,
            ]),
            'truffles' => Category::query()->updateOrCreate(['slug' => 'truffles-bonbons'], [
                'name' => 'Truffles & bonbons',
                'description' => 'Ganache-filled truffles and painted bonbons, best eaten within two weeks.',
                'image' => 'images/placeholders/category-truffles.svg', 'display_order' => 2, 'is_active' => true,
            ]),
            'gifts' => Category::query()->updateOrCreate(['slug' => 'gift-boxes'], [
                'name' => 'Gift boxes',
                'description' => 'Assorted boxes and hampers for birthdays, Diwali, weddings and thank-yous.',
                'image' => 'images/placeholders/category-gifts.svg', 'display_order' => 3, 'is_active' => true,
            ]),
        ];

        foreach ($this->products() as $data) {
            $category = $categories[$data['cat']];
            $images = $data['images'];
            unset($data['cat'], $data['images']);

            $product = Product::query()->updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                $data + [
                    'category_id' => $category->id,
                    'allergens' => $data['allergens'] ?? self::MILK_NUTS,
                    'is_active' => true,
                    'meta_description' => $data['short_description'],
                ]
            );

            if ($product->images()->doesntExist()) {
                foreach ($images as $i => $path) {
                    $product->images()->create([
                        'path' => 'images/placeholders/'.$path,
                        'alt_text' => $product->name,
                        'sort_order' => $i,
                        'is_primary' => $i === 0,
                    ]);
                }
            }
        }
    }

    private function products(): array
    {
        return [
            ['cat' => 'bars', 'name' => '70% Dark Chocolate Bar', 'sku' => 'BAR-DK70',
                'short_description' => 'Deep, fruity dark chocolate with a clean snap. Our everyday favourite.',
                'description' => "A 70% dark couverture bar with notes of red berries and roasted cocoa.\n\nTempered by hand and poured into thick segments so it breaks cleanly. Vegan-friendly recipe; see allergen note for kitchen traces.",
                'ingredients' => 'Cocoa mass, sugar, cocoa butter, soy lecithin (emulsifier), natural vanilla.',
                'allergens' => 'Contains soy. Made in a kitchen that also handles milk, tree nuts, peanuts and gluten.',
                'weight' => '80 g', 'price' => 280, 'discount_price' => null, 'stock_quantity' => 40,
                'is_featured' => true, 'is_bestseller' => true, 'is_gift_box' => false, 'images' => ['product-dark.svg', 'product-wrapped.svg']],
            ['cat' => 'bars', 'name' => 'Classic Milk Chocolate Bar', 'sku' => 'BAR-MLK',
                'short_description' => 'Smooth, creamy milk chocolate with caramel notes.',
                'description' => 'A 38% milk chocolate bar that melts slowly and tastes of caramel and fresh cream. A safe pick for children and anyone new to good chocolate.',
                'ingredients' => 'Sugar, cocoa butter, milk solids, cocoa mass, soy lecithin, natural vanilla.',
                'weight' => '80 g', 'price' => 260, 'discount_price' => null, 'stock_quantity' => 35,
                'is_featured' => false, 'is_bestseller' => true, 'is_gift_box' => false, 'images' => ['product-milk.svg']],
            ['cat' => 'bars', 'name' => 'Ruby Rose Bar', 'sku' => 'BAR-RUBY',
                'short_description' => 'Naturally pink ruby chocolate with dried rose petals.',
                'description' => 'Ruby chocolate gets its colour and berry tartness from the ruby cocoa bean, with no added colouring. We finish it with crushed dried rose petals.',
                'ingredients' => 'Ruby chocolate (sugar, cocoa butter, milk solids, cocoa mass, soy lecithin, citric acid, natural vanilla), dried rose petals.',
                'weight' => '70 g', 'price' => 340, 'discount_price' => 299, 'stock_quantity' => 18,
                'is_featured' => true, 'is_bestseller' => false, 'is_gift_box' => false, 'images' => ['product-ruby.svg']],
            ['cat' => 'bars', 'name' => 'Nolen Gur Milk Bar', 'sku' => 'BAR-NOLEN',
                'short_description' => 'Milk chocolate with winter date-palm jaggery from Bengal.',
                'description' => "A winter special: milk chocolate swirled with nolen gur (date-palm jaggery), which adds a smoky, caramel sweetness.\n\nAvailable only while the season's gur lasts.",
                'ingredients' => 'Milk chocolate (sugar, cocoa butter, milk solids, cocoa mass, soy lecithin), nolen gur (date-palm jaggery).',
                'weight' => '80 g', 'price' => 320, 'discount_price' => null, 'stock_quantity' => 22,
                'is_featured' => true, 'is_bestseller' => true, 'is_gift_box' => false, 'images' => ['product-milk.svg', 'product-wrapped.svg']],
            ['cat' => 'bars', 'name' => 'Almond & Sea Salt Dark Bar', 'sku' => 'BAR-ALMSS',
                'short_description' => 'Roasted almonds and a pinch of sea salt in 60% dark chocolate.',
                'description' => 'Whole roasted almonds pressed into 60% dark chocolate and finished with flaky sea salt.',
                'ingredients' => 'Dark chocolate (cocoa mass, sugar, cocoa butter, soy lecithin), roasted almonds, sea salt.',
                'allergens' => 'Contains tree nuts (almonds) and soy. May contain milk, peanuts and gluten.',
                'weight' => '90 g', 'price' => 330, 'discount_price' => null, 'stock_quantity' => 3,
                'is_featured' => false, 'is_bestseller' => false, 'is_gift_box' => false, 'images' => ['product-dark.svg']],
            ['cat' => 'bars', 'name' => 'Coffee White Chocolate Bar', 'sku' => 'BAR-CFWH',
                'short_description' => 'White chocolate infused with South Indian filter coffee.',
                'description' => 'Creamy white chocolate infused with finely ground filter coffee from Chikmagalur estates. Sweet, with a gentle bitter finish.',
                'ingredients' => 'White chocolate (sugar, cocoa butter, milk solids, soy lecithin, natural vanilla), ground coffee.',
                'weight' => '80 g', 'price' => 300, 'discount_price' => null, 'stock_quantity' => 0,
                'is_featured' => false, 'is_bestseller' => false, 'is_gift_box' => false, 'images' => ['product-white.svg']],
            ['cat' => 'truffles', 'name' => 'Classic Dark Truffles', 'sku' => 'TRF-DK6',
                'short_description' => 'Six cocoa-dusted dark ganache truffles.',
                'description' => 'Soft dark-chocolate ganache made with fresh cream, rolled by hand and dusted with cocoa powder. Keep refrigerated in warm weather.',
                'ingredients' => 'Dark chocolate, fresh cream, butter, cocoa powder.',
                'weight' => 'Box of 6', 'price' => 360, 'discount_price' => null, 'stock_quantity' => 25,
                'is_featured' => true, 'is_bestseller' => true, 'is_gift_box' => false, 'images' => ['truffles.svg']],
            ['cat' => 'truffles', 'name' => 'Salted Caramel Bonbons', 'sku' => 'BON-SC9',
                'short_description' => 'Nine painted bonbons with a runny salted caramel centre.',
                'description' => 'Thin milk-chocolate shells, hand-painted with cocoa butter colours and filled with salted caramel that we cook in small pans.',
                'ingredients' => 'Milk chocolate, sugar, cream, butter, glucose, sea salt, coloured cocoa butter.',
                'weight' => 'Box of 9', 'price' => 540, 'discount_price' => 480, 'stock_quantity' => 14,
                'is_featured' => true, 'is_bestseller' => true, 'is_gift_box' => false, 'images' => ['bonbons.svg']],
            ['cat' => 'truffles', 'name' => 'Mishti Doi Bonbons', 'sku' => 'BON-MD9',
                'short_description' => 'White chocolate bonbons filled with a sweet-yoghurt ganache.',
                'description' => 'A Kolkata-inspired filling: caramelised sweet-yoghurt ganache in a white chocolate shell, finished with a pink cocoa-butter swirl.',
                'ingredients' => 'White chocolate, hung curd, condensed milk, cream, sugar, coloured cocoa butter.',
                'weight' => 'Box of 9', 'price' => 560, 'discount_price' => null, 'stock_quantity' => 10,
                'is_featured' => false, 'is_bestseller' => false, 'is_gift_box' => false, 'images' => ['bonbons.svg']],
            ['cat' => 'truffles', 'name' => 'Hazelnut Praline Truffles', 'sku' => 'TRF-HZ6',
                'short_description' => 'Crunchy hazelnut praline in milk chocolate.',
                'description' => 'House-made hazelnut praline folded with crisp feuilletine and enrobed in milk chocolate.',
                'ingredients' => 'Milk chocolate, roasted hazelnuts, sugar, wheat feuilletine, cocoa butter.',
                'allergens' => 'Contains milk, tree nuts (hazelnuts), gluten (wheat) and soy.',
                'weight' => 'Box of 6', 'price' => 420, 'discount_price' => null, 'stock_quantity' => 16,
                'is_featured' => false, 'is_bestseller' => true, 'is_gift_box' => false, 'images' => ['truffles.svg']],
            ['cat' => 'truffles', 'name' => 'Paan Rose Truffles', 'sku' => 'TRF-PAAN6',
                'short_description' => 'Betel leaf and gulkand ganache in dark chocolate.',
                'description' => 'Fresh betel leaf, gulkand and a little fennel, set in a dark-chocolate ganache. An after-dinner favourite.',
                'ingredients' => 'Dark chocolate, cream, gulkand (rose petal preserve), betel leaf, fennel seeds.',
                'weight' => 'Box of 6', 'price' => 390, 'discount_price' => null, 'stock_quantity' => 12,
                'is_featured' => false, 'is_bestseller' => false, 'is_gift_box' => false, 'images' => ['truffles.svg']],
            ['cat' => 'gifts', 'name' => 'Signature Assorted Box', 'sku' => 'GFT-SIG16',
                'short_description' => 'Sixteen of our best truffles and bonbons in a keepsake box.',
                'description' => "Our most-gifted box: a changing selection of 16 truffles and bonbons, with a flavour card.\n\nAdd a handwritten note by mentioning it in your order notes.",
                'ingredients' => 'Assorted chocolates. Full flavour list in the box.',
                'allergens' => 'Contains milk, soy, tree nuts and gluten in some pieces.',
                'weight' => 'Box of 16', 'price' => 1250, 'discount_price' => null, 'stock_quantity' => 12,
                'is_featured' => true, 'is_bestseller' => true, 'is_gift_box' => true, 'images' => ['giftbox-gold.svg', 'bonbons.svg', 'truffles.svg']],
            ['cat' => 'gifts', 'name' => 'Festive Hamper', 'sku' => 'GFT-HAMP',
                'short_description' => 'Two bars, nine bonbons and chocolate-coated almonds in a gift basket.',
                'description' => 'A ready-to-give hamper for Diwali, Christmas or Bijoya: two chocolate bars, a box of nine bonbons and a jar of chocolate-coated almonds.',
                'ingredients' => 'Assorted chocolates and chocolate-coated almonds.',
                'allergens' => 'Contains milk, soy and tree nuts. May contain gluten and peanuts.',
                'weight' => 'Approx. 650 g', 'price' => 2400, 'discount_price' => 2150, 'stock_quantity' => 6,
                'is_featured' => true, 'is_bestseller' => false, 'is_gift_box' => true, 'images' => ['giftbox-pink.svg', 'product-wrapped.svg']],
            ['cat' => 'gifts', 'name' => 'Petite Thank-You Box', 'sku' => 'GFT-TY4',
                'short_description' => 'Four truffles in a small box — a thoughtful return gift.',
                'description' => 'Four assorted truffles in a small printed box. Popular as return gifts; bulk orders of 20 or more can carry your own tag.',
                'ingredients' => 'Assorted truffles.',
                'weight' => 'Box of 4', 'price' => 240, 'discount_price' => null, 'stock_quantity' => 50,
                'is_featured' => false, 'is_bestseller' => true, 'is_gift_box' => true, 'images' => ['giftbox-cream.svg']],
            ['cat' => 'gifts', 'name' => 'Bar Lover\'s Trio', 'sku' => 'GFT-TRIO',
                'short_description' => 'Dark, milk and ruby bars together in a gift sleeve.',
                'description' => 'Our three most-loved bars — 70% dark, classic milk and ruby rose — wrapped together in a gift sleeve.',
                'ingredients' => 'See individual bars.',
                'weight' => '3 × 80 g', 'price' => 820, 'discount_price' => 749, 'stock_quantity' => 9,
                'is_featured' => false, 'is_bestseller' => false, 'is_gift_box' => true, 'images' => ['giftbox-gold.svg', 'product-dark.svg', 'product-ruby.svg']],
        ];
    }
}
