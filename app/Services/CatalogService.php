<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Storefront product queries: filtering, sorting and related products.
 */
class CatalogService
{
    public const SORTS = [
        'featured' => 'Featured',
        'newest' => 'Newest',
        'price_asc' => 'Price: low to high',
        'price_desc' => 'Price: high to low',
        'name' => 'Name: A to Z',
    ];

    public function paginate(array $filters, ?Category $category = null, int $perPage = 12): LengthAwarePaginator
    {
        $query = $this->baseQuery();

        if ($category) {
            $query->where('category_id', $category->id);
        }

        if (filled($filters['q'] ?? null)) {
            $term = '%'.addcslashes((string) $filters['q'], '%_\\').'%';
            $query->where(fn (Builder $q) => $q->where('name', 'like', $term)
                ->orWhere('short_description', 'like', $term)
                ->orWhere('ingredients', 'like', $term)
                ->orWhere('sku', 'like', $term));
        }

        if (filled($filters['min_price'] ?? null)) {
            $query->whereEffectivePrice('>=', (float) $filters['min_price']);
        }

        if (filled($filters['max_price'] ?? null)) {
            $query->whereEffectivePrice('<=', (float) $filters['max_price']);
        }

        match ($filters['type'] ?? null) {
            'gift' => $query->where('is_gift_box', true),
            'bestseller' => $query->where('is_bestseller', true),
            'featured' => $query->where('is_featured', true),
            default => null,
        };

        if (! empty($filters['in_stock'])) {
            $query->inStock();
        }

        $this->applySort($query, $filters['sort'] ?? 'featured');

        return $query->paginate($perPage)->withQueryString();
    }

    /** @return array{min:int,max:int} */
    public function priceRange(): array
    {
        $prices = Product::query()->active()->get(['price', 'discount_price'])
            ->map(fn (Product $p) => $p->effective_price);

        return ['min' => (int) floor($prices->min() ?? 0), 'max' => (int) ceil($prices->max() ?? 0)];
    }

    public function related(Product $product, int $limit = 4): Collection
    {
        return $this->baseQuery()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->getKey())
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public function flagged(string $flag, int $limit = 8): Collection
    {
        return $this->baseQuery()
            ->where($flag, true)
            ->orderByRaw('CASE WHEN stock_quantity > 0 THEN 0 ELSE 1 END')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    private function baseQuery(): Builder
    {
        return Product::query()->active()->with(['primaryImage', 'category:id,name,slug']);
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'price_asc' => $query->orderByEffectivePrice('asc'),
            'price_desc' => $query->orderByEffectivePrice('desc'),
            'newest' => $query->latest('id'),
            'name' => $query->orderBy('name'),
            default => $query->orderByDesc('is_featured')
                ->orderByRaw('CASE WHEN stock_quantity > 0 THEN 0 ELSE 1 END')
                ->latest('id'),
        };
    }
}
