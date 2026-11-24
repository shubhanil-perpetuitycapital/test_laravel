<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\ImageService;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private ImageService $images) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'category' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:active,hidden,out_of_stock,low_stock'],
        ]);

        $products = Product::query()
            ->with(['primaryImage', 'category:id,name'])
            ->when($filters['q'] ?? null, function ($q, $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('sku', 'like', $like));
            })
            ->when($filters['category'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => match ($status) {
                'active' => $q->where('is_active', true),
                'hidden' => $q->where('is_active', false),
                'out_of_stock' => $q->where('stock_quantity', 0),
                'low_stock' => $q->whereBetween('stock_quantity', [1, Product::LOW_STOCK_THRESHOLD]),
            })
            ->latest('id')
            ->paginate(config('store.admin_per_page'))
            ->withQueryString();

        $categories = Category::query()->ordered()->pluck('name', 'id');

        return view('admin.products.index', compact('products', 'categories', 'filters'));
    }

    public function create(): View
    {
        return view('admin.products.form', [
            'product' => new Product(['is_active' => true, 'stock_quantity' => 0]),
            'categories' => Category::query()->ordered()->pluck('name', 'id'),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = DB::transaction(function () use ($request) {
            $product = Product::query()->create($this->attributes($request));
            $this->syncImages($request, $product);

            return $product;
        });

        $this->flushCaches();

        return redirect()->route('admin.products.edit', $product)->with('success', 'Product created.');
    }

    public function edit(Product $product): View
    {
        $product->load('images');

        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::query()->ordered()->pluck('name', 'id'),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        DB::transaction(function () use ($request, $product) {
            $product->update($this->attributes($request, $product));
            $this->syncImages($request, $product);
        });

        $this->flushCaches();

        return redirect()->route('admin.products.edit', $product)->with('success', 'Product saved.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        // Soft delete: past orders keep their snapshot and the product can be restored from the database.
        $product->delete();
        $this->flushCaches();

        return redirect()->route('admin.products.index')->with('success', '"'.$product->name.'" deleted.');
    }

    private function attributes(ProductRequest $request, ?Product $product = null): array
    {
        $data = $request->safe()->except(['images', 'existing', 'primary_image']);
        $data['slug'] = ($data['slug'] ?? null) ?: ($product?->slug ?: Slug::unique(Product::class, $data['name'], $product?->id));
        $data['discount_price'] = $data['discount_price'] ?: null;

        return $data;
    }

    /**
     * Update captions/order, remove ticked photos, add uploads and make sure
     * exactly one photo is primary.
     */
    private function syncImages(ProductRequest $request, Product $product): void
    {
        foreach ((array) $request->input('existing', []) as $imageId => $values) {
            $image = $product->images()->whereKey($imageId)->first();
            if (! $image) {
                continue;
            }

            if (! empty($values['remove'])) {
                $this->images->delete($image->path);
                $image->delete();

                continue;
            }

            $image->update([
                'alt_text' => $values['alt_text'] ?? null,
                'sort_order' => (int) ($values['sort_order'] ?? $image->sort_order),
            ]);
        }

        $nextOrder = (int) $product->images()->max('sort_order') + 1;
        foreach ((array) $request->file('images', []) as $file) {
            $product->images()->create([
                'path' => $this->images->store($file, 'products'),
                'alt_text' => $product->name,
                'sort_order' => $nextOrder++,
                'is_primary' => false,
            ]);
        }

        $primaryId = (int) $request->input('primary_image');
        $images = $product->images()->get();
        if ($images->isEmpty()) {
            return;
        }
        if (! $images->contains('id', $primaryId)) {
            $primaryId = ($images->firstWhere('is_primary', true) ?? $images->sortBy('sort_order')->first())->id;
        }
        $product->images()->update(['is_primary' => false]);
        $product->images()->whereKey($primaryId)->update(['is_primary' => true]);
    }

    private function flushCaches(): void
    {
        Cache::forget('sitemap_xml');
    }
}
