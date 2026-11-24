<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Services\ImageService;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private ImageService $images) {}

    public function index(): View
    {
        $categories = Category::query()->ordered()->withCount('products')->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.categories.form', ['category' => new Category(['is_active' => true, 'display_order' => Category::query()->max('display_order') + 1])]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $data = $this->attributes($request, new Category());
        Category::query()->create($data);
        $this->flush();

        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($this->attributes($request, $category));
        $this->flush();

        return redirect()->route('admin.categories.index')->with('success', 'Category saved.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'Move or delete the '.$category->products()->count().' products in "'.$category->name.'" first, or hide the category instead.');
        }

        $this->images->delete($category->image);
        $category->delete();
        $this->flush();

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted.');
    }

    private function attributes(CategoryRequest $request, Category $category): array
    {
        $data = $request->safe()->except(['image', 'remove_image']);
        $data['slug'] = ($data['slug'] ?? null) ?: ($category->slug ?: Slug::unique(Category::class, $data['name'], $category->id));
        $data['display_order'] = (int) ($data['display_order'] ?? 0);

        if ($request->hasFile('image')) {
            $this->images->delete($category->image);
            $data['image'] = $this->images->store($request->file('image'), 'categories');
        } elseif ($request->boolean('remove_image')) {
            $this->images->delete($category->image);
            $data['image'] = null;
        }

        return $data;
    }

    private function flush(): void
    {
        Cache::forget('nav_categories');
        Cache::forget('sitemap_xml');
    }
}
