<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShopFilterRequest;
use App\Models\Category;
use App\Services\CatalogService;
use App\Services\CompanyProfile;
use App\Services\SeoData;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(ShopFilterRequest $request, CatalogService $catalog, CompanyProfile $company, ?Category $category = null): View|JsonResponse
    {
        if ($category && ! $category->is_active) {
            abort(404);
        }

        $filters = $request->filters();
        $products = $catalog->paginate($filters, $category, (int) config('store.products_per_page', 12));

        // Fetch-based filtering: return only the grid HTML and the result count.
        if ($request->wantsJson()) {
            return response()->json([
                'html' => view('shop._results', compact('products', 'filters', 'category'))->render(),
                'count' => $products->total(),
                'title' => $this->heading($category, $filters),
            ]);
        }

        $categories = Category::query()->active()->ordered()
            ->withCount(['products as products_count' => fn ($q) => $q->where('is_active', true)])
            ->get();

        $priceRange = $catalog->priceRange();
        $sorts = CatalogService::SORTS;
        $heading = $this->heading($category, $filters);

        $seo = new SeoData(
            title: ($category?->meta_title ?: $heading).' | '.$company->name(),
            description: $category?->meta_description ?: ($category?->description ?: $company->metaDescription()),
            canonical: $category ? route('shop.index', $category) : route('shop.index'),
            // Filtered and searched listings are thin duplicates of the main shop.
            noindex: filled($filters['q'] ?? null) || filled($filters['min_price'] ?? null) || filled($filters['max_price'] ?? null),
        );

        return view('shop.index', compact('products', 'categories', 'category', 'filters', 'priceRange', 'sorts', 'heading', 'seo'));
    }

    private function heading(?Category $category, array $filters): string
    {
        if (filled($filters['q'] ?? null)) {
            return 'Results for “'.$filters['q'].'”';
        }

        return match ($filters['type'] ?? null) {
            'gift' => 'Gift boxes',
            'bestseller' => 'Bestsellers',
            'featured' => 'Featured chocolates',
            default => $category?->name ?? 'All chocolates',
        };
    }
}
