<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContentRequest;
use App\Models\Banner;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function __construct(private ImageService $images) {}

    public function index(): View
    {
        return view('admin.banners.index', ['banners' => Banner::query()->orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function create(): View
    {
        return view('admin.banners.form', ['banner' => new Banner(['is_active' => true, 'sort_order' => Banner::query()->max('sort_order') + 1])]);
    }

    public function store(ContentRequest $request): RedirectResponse
    {
        Banner::query()->create($this->attributes($request, new Banner()));

        return redirect()->route('admin.banners.index')->with('success', 'Banner added to the homepage.');
    }

    public function edit(Banner $banner): View
    {
        return view('admin.banners.form', compact('banner'));
    }

    public function update(ContentRequest $request, Banner $banner): RedirectResponse
    {
        $banner->update($this->attributes($request, $banner));

        return redirect()->route('admin.banners.index')->with('success', 'Banner saved.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $this->images->delete($banner->image);
        $this->images->delete($banner->mobile_image);
        $banner->delete();

        return redirect()->route('admin.banners.index')->with('success', 'Banner deleted.');
    }

    private function attributes(ContentRequest $request, Banner $banner): array
    {
        $data = $request->safe()->except(['image', 'mobile_image', 'remove_mobile_image']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        if ($request->hasFile('image')) {
            $this->images->delete($banner->image);
            $data['image'] = $this->images->store($request->file('image'), 'banners', 1600);
        }
        if ($request->hasFile('mobile_image')) {
            $this->images->delete($banner->mobile_image);
            $data['mobile_image'] = $this->images->store($request->file('mobile_image'), 'banners', 900);
        } elseif ($request->boolean('remove_mobile_image')) {
            $this->images->delete($banner->mobile_image);
            $data['mobile_image'] = null;
        }

        return $data;
    }
}
