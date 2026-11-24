<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Requests\TrackOrderRequest;
use App\Models\Order;
use App\Services\CompanyProfile;
use App\Services\SeoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Order tracking by order number + the mobile number used at checkout.
 * A successful lookup is remembered in the session, so the tracking link
 * in WhatsApp messages asks for the mobile number once.
 */
class OrderTrackingController extends Controller
{
    private const SESSION_KEY = 'tracked_orders';

    public function index(Request $request, CompanyProfile $company): View
    {
        $seo = SeoData::make('Track your order | '.$company->name(), 'Check the status of your chocolate order.');

        return view('track.index', ['prefill' => $request->query('order'), 'seo' => $seo]);
    }

    public function lookup(TrackOrderRequest $request): RedirectResponse
    {
        $order = Order::query()
            ->where('order_number', $request->validated('order_number'))
            ->where(fn ($q) => $q->where('recipient_mobile', $request->validated('mobile'))
                ->orWhereHas('customer', fn ($c) => $c->where('mobile', $request->validated('mobile'))))
            ->first();

        if (! $order) {
            return back()->withInput()->withErrors(['order_number' => 'We could not find an order with this number and mobile number. Check both and try again.']);
        }

        $request->session()->push(self::SESSION_KEY, $order->order_number);

        return redirect()->route('track.show', $order->order_number);
    }

    public function show(Request $request, string $order, CompanyProfile $company): View|RedirectResponse
    {
        $allowed = array_merge($request->session()->get(self::SESSION_KEY, []), $request->session()->get('recent_orders', []));

        if (! in_array($order, $allowed, true)) {
            return redirect()->route('track.index', ['order' => $order]);
        }

        $order = Order::query()->where('order_number', $order)->with([
            'items',
            'statusHistories' => fn ($q) => $q->where('visible_to_customer', true),
        ])->firstOrFail();

        $journey = OrderStatus::journey();
        $currentIndex = array_search($order->status, $journey, true);

        $seo = SeoData::make('Order '.$order->order_number.' | '.$company->name(), null, noindex: true);

        return view('track.show', compact('order', 'journey', 'currentIndex', 'seo'));
    }
}
