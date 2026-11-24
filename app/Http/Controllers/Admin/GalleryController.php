<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContentRequest;
use App\Models\GalleryItem;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GalleryController extends Controller
{
    public function __construct(private ImageService $images) {}

    public function index(): View
    {
        return view('admin.gallery.index', ['items' => GalleryItem::query()->orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function create(): View
    {
        return view('admin.gallery.form', ['item' => new GalleryItem(['is_active' => true, 'sort_order' => GalleryItem::query()->max('sort_order') + 1])]);
    }

    public function store(ContentRequest $request): RedirectResponse
    {
        GalleryItem::query()->create($this->attributes($request, new GalleryItem()));

        return redirect()->route('admin.gallery.index')->with('success', 'Photo added.');
    }

    public function edit(GalleryItem $galleryItem): View
    {
        return view('admin.gallery.form', ['item' => $galleryItem]);
    }

    public function update(ContentRequest $request, GalleryItem $galleryItem): RedirectResponse
    {
        $galleryItem->update($this->attributes($request, $galleryItem));

        return redirect()->route('admin.gallery.index')->with('success', 'Photo saved.');
    }

    public function destroy(GalleryItem $galleryItem): RedirectResponse
    {
        $this->images->delete($galleryItem->image);
        $galleryItem->delete();

        return redirect()->route('admin.gallery.index')->with('success', 'Photo removed.');
    }

    private function attributes(ContentRequest $request, GalleryItem $item): array
    {
        $data = $request->safe()->except('image');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        if ($request->hasFile('image')) {
            $this->images->delete($item->image);
            $data['image'] = $this->images->store($request->file('image'), 'gallery', 900);
        }

        return $data;
    }
}
