<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CatalogService;
use App\Services\CompanyProfile;
use App\Services\SeoData;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(Product $product, CatalogService $catalog, CompanyProfile $company): View
    {
        $product->load(['category', 'images']);

        abort_if(! $product->is_active || ! $product->category?->is_active, 404);

        $reviews = $product->reviews()->approved()->latest()->limit(10)->get();
        $reviewCount = $product->reviews()->approved()->count();
        $averageRating = $reviewCount ? round((float) $product->reviews()->approved()->avg('rating'), 1) : null;
        $related = $catalog->related($product);

        $description = $product->meta_description
            ?: Str::limit(strip_tags((string) ($product->short_description ?: $product->description)), 160);

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'sku' => $product->sku,
            'description' => $description,
            'image' => $product->images->map->url->values()->all() ?: [$product->image_url],
            'brand' => ['@type' => 'Brand', 'name' => $company->name()],
            'category' => $product->category->name,
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => 'INR',
                'price' => number_format($product->effective_price, 2, '.', ''),
                'availability' => $product->in_stock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'url' => $product->url,
            ],
        ];
        if ($averageRating) {
            $schema['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => $averageRating, 'reviewCount' => $reviewCount];
        }

        $seo = new SeoData(
            title: ($product->meta_title ?: $product->name).' | '.$company->name(),
            description: $description,
            image: $product->image_url,
            canonical: $product->url,
            type: 'product',
            schema: [$schema],
        );

        return view('products.show', compact('product', 'reviews', 'reviewCount', 'averageRating', 'related', 'seo'));
    }
}
