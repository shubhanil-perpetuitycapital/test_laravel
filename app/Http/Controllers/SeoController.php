<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $xml = Cache::remember('sitemap_xml', 3600, function () {
            $urls = [
                ['loc' => route('home'), 'priority' => '1.0', 'lastmod' => null],
                ['loc' => route('shop.index'), 'priority' => '0.9', 'lastmod' => null],
                ['loc' => route('custom-orders.create'), 'priority' => '0.7', 'lastmod' => null],
                ['loc' => route('contact'), 'priority' => '0.5', 'lastmod' => null],
            ];

            foreach (Category::query()->active()->ordered()->get(['slug', 'updated_at']) as $category) {
                $urls[] = ['loc' => route('shop.index', $category->slug), 'priority' => '0.8', 'lastmod' => $category->updated_at];
            }
            foreach (Product::query()->active()->get(['slug', 'updated_at']) as $product) {
                $urls[] = ['loc' => route('products.show', $product->slug), 'priority' => '0.7', 'lastmod' => $product->updated_at];
            }
            foreach (Page::query()->where('is_active', true)->get(['slug', 'updated_at']) as $page) {
                $urls[] = ['loc' => route('pages.show', $page->slug), 'priority' => '0.4', 'lastmod' => $page->updated_at];
            }

            return view('seo.sitemap', compact('urls'))->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $admin = '/'.trim(config('store.admin_path', 'admin'), '/').'/';

        $lines = app()->environment('production')
            ? ['User-agent: *', 'Disallow: '.$admin, 'Disallow: /cart', 'Disallow: /checkout', 'Disallow: /order/', 'Disallow: /track-order/', 'Allow: /', '', 'Sitemap: '.route('sitemap')]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
