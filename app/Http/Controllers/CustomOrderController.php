<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomOrderRequest;
use App\Models\CustomOrderEnquiry;
use App\Services\CompanyProfile;
use App\Services\OrderService;
use App\Services\SeoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomOrderController extends Controller
{
    public function create(CompanyProfile $company): View
    {
        $seo = SeoData::make(
            'Custom & personalised chocolates | '.$company->name(),
            'Personalised chocolates for weddings, birthdays, corporate gifting and return gifts, handmade in '.$company->city().'.',
        );

        return view('custom-orders.create', [
            'occasions' => CustomOrderEnquiry::OCCASIONS,
            'types' => CustomOrderEnquiry::CHOCOLATE_TYPES,
            'seo' => $seo,
        ]);
    }

    public function store(CustomOrderRequest $request, OrderService $orders): RedirectResponse
    {
        $data = $request->safe()->except(['reference_image', 'website']);

        if ($request->hasFile('reference_image')) {
            $data['reference_image'] = $request->file('reference_image')->store('enquiries/'.date('Y/m'), 'local');
        }

        $customer = $orders->upsertCustomer($data['name'], $data['mobile'], $data['email'] ?? null);

        $enquiry = CustomOrderEnquiry::query()->create($data + [
            'enquiry_number' => $orders->generateNumber('ENQ'),
            'customer_id' => $customer->id,
            'status' => 'new',
        ]);

        $request->session()->put('last_enquiry', $enquiry->id);

        return redirect()->route('custom-orders.thanks');
    }

    public function thanks(Request $request): View|RedirectResponse
    {
        $enquiry = CustomOrderEnquiry::query()->find($request->session()->get('last_enquiry'));

        if (! $enquiry) {
            return redirect()->route('custom-orders.create');
        }

        return view('custom-orders.thanks', ['enquiry' => $enquiry, 'seo' => SeoData::make('Enquiry received', null, noindex: true)]);
    }
}
