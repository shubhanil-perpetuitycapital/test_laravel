<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Services\CompanyProfile;
use App\Services\SeoData;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(Page $page, CompanyProfile $company): View
    {
        abort_unless($page->is_active, 404);

        $seo = new SeoData(
            title: ($page->meta_title ?: $page->title).' | '.$company->name(),
            description: $page->meta_description ?: Str::limit(strip_tags((string) $page->content), 160),
            canonical: route('pages.show', $page),
        );

        return view('pages.show', compact('page', 'seo'));
    }
}
