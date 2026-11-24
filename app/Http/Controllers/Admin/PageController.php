<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContentRequest;
use App\Models\Page;
use App\Services\HtmlSanitizer;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(private HtmlSanitizer $sanitizer) {}

    public function index(): View
    {
        return view('admin.pages.index', ['pages' => Page::query()->orderBy('title')->get()]);
    }

    public function create(): View
    {
        return view('admin.pages.form', ['page' => new Page(['is_active' => true])]);
    }

    public function store(ContentRequest $request): RedirectResponse
    {
        $page = Page::query()->create($this->attributes($request, new Page()));
        Cache::forget('sitemap_xml');

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Page created.');
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.form', compact('page'));
    }

    public function update(ContentRequest $request, Page $page): RedirectResponse
    {
        $page->update($this->attributes($request, $page));
        Cache::forget('sitemap_xml');

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Page saved.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        if ($page->isSystem()) {
            return back()->with('error', '"'.$page->title.'" is linked from the footer and checkout, so it can be hidden but not deleted.');
        }

        $page->delete();
        Cache::forget('sitemap_xml');

        return redirect()->route('admin.pages.index')->with('success', 'Page deleted.');
    }

    private function attributes(ContentRequest $request, Page $page): array
    {
        $data = $request->validated();
        $data['content'] = $this->sanitizer->clean($data['content'] ?? '');

        // Footer links depend on the fixed slugs of the standard pages.
        if ($page->exists && $page->isSystem()) {
            $data['slug'] = $page->slug;
        } else {
            $data['slug'] = ($data['slug'] ?? null) ?: Slug::unique(Page::class, $data['title'], $page->id);
        }

        return $data;
    }
}
