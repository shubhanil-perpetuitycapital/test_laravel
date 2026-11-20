<?php

namespace App\Http\Controllers;

use App\Services\CompanyProfile;
use App\Services\SeoData;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(CompanyProfile $company): View
    {
        $seo = new SeoData(
            title: 'Contact us | '.$company->name(),
            description: 'Call, WhatsApp or email '.$company->name().' for orders, gifting and custom chocolates in '.$company->city().'.',
            canonical: route('contact'),
        );

        return view('pages.contact', compact('seo'));
    }
}
