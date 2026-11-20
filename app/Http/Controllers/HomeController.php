<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\GalleryItem;
use App\Models\Review;
use App\Services\CatalogService;
use App\Services\CompanyProfile;
use App\Services\SeoData;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(CatalogService $catalog, CompanyProfile $company): View
    {
        $banners = Banner::query()->active()->get();

        $categories = Category::query()->active()->ordered()
            ->withCount(['products as products_count' => fn ($q) => $q->where('is_active', true)])
            ->get();

        $bestsellers = $catalog->flagged('is_bestseller', 8);
        $giftBoxes = $catalog->flagged('is_gift_box', 4);

        $reviews = Review::query()->approved()->where('is_featured', true)
            ->latest()->limit(6)->get();

        $gallery = GalleryItem::query()->active()->limit(8)->get();

        $seo = new SeoData(
            title: $company->metaTitle(),
            description: $company->metaDescription(),
            canonical: route('home'),
            schema: [[
                '@context' => 'https://schema.org',
                '@type' => 'Store',
                'name' => $company->name(),
                'description' => $company->about(),
                'url' => route('home'),
                'telephone' => $company->phone(),
                'image' => $company->ogImageUrl(),
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => $company->city(),
                    'addressRegion' => setting('state'),
                    'addressCountry' => 'IN',
                ],
                'sameAs' => array_column($company->socialLinks(), 'url'),
            ]],
        );

        return view('home', compact('banners', 'categories', 'bestsellers', 'giftBoxes', 'reviews', 'gallery', 'seo'));
    }
}
