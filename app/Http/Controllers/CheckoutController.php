<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Services\CartService;
use App\Services\CompanyProfile;
use App\Services\DeliveryService;
use App\Services\OrderService;
use App\Services\SeoData;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cart,
        private DeliveryService $delivery,
        private OrderService $orders,
    ) {}

    public function create(CompanyProfile $company, SettingService $settings): View|RedirectResponse
    {
        if ($this->cart->lines()->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Your cart is empty. Add some chocolates first.']);
        }

        $remembered = $this->cart->rememberedDelivery();
        $methods = $this->delivery->methodsEnabled();
        $method = old('delivery_method', $remembered['method'] ?? ($methods[0] ?? 'local'));
        $pincode = old('pincode', $remembered['pincode'] ?? '');

        $base = $this->cart->summary();
        $quote = $pincode ? $this->delivery->quote($method, $pincode, $base['subtotal'] - $base['discount']) : null;
        $summary = $this->cart->summary($quote);
        $paymentMethods = $this->orders->paymentMethods($method, $summary['total']);

        $lines = $this->cart->lines();
        $minOrder = $settings->float('min_order_amount');
        $belowMinimum = $minOrder && $summary['subtotal'] < $minOrder ? $minOrder : null;

        $seo = SeoData::make('Checkout | '.$company->name(), null, noindex: true);

        return view('checkout.create', compact('lines', 'summary', 'quote', 'method', 'methods', 'pincode', 'paymentMethods', 'belowMinimum', 'seo'));
    }

    /**
     * Live delivery check used by the cart and checkout pages.
     */
    public function quote(Request $request): JsonResponse
    {
        $data = $request->validate([
            'method' => ['required', Rule::in(['local', 'outstation'])],
            'pincode' => ['required', 'string', 'max:10'],
        ]);

        $base = $this->cart->summary();
        $quote = $this->delivery->quote($data['method'], $data['pincode'], $base['subtotal'] - $base['discount']);

        if ($quote->ok) {
            $this->cart->rememberDelivery($data['method'], preg_replace('/\D/', '', $data['pincode']));
        }

        $summary = $this->cart->summary($quote);

        return response()->json([
            'quote' => $quote->toArray(),
            'html' => view('checkout._quote', ['quote' => $quote])->render(),
            'summary' => [
                'subtotal' => money($summary['subtotal']),
                'discount' => $summary['discount'] > 0 ? '−'.money($summary['discount']) : null,
                'delivery' => $quote->chargeLabel(),
                'total' => money($summary['total']),
                'total_note' => $quote->pending ? '+ courier charge, confirmed on WhatsApp' : null,
            ],
            'payment_methods' => $this->orders->paymentMethods($data['method'], $summary['total']),
            'local_pin' => $data['method'] === 'outstation' && DeliveryService::isValidPincode($data['pincode'])
                && $this->delivery->isLocalPincode($data['pincode']),
        ]);
    }

    public function store(CheckoutRequest $request): RedirectResponse
    {
        $this->cart->lines(); // drops unavailable items and caps quantities before placing

        $order = $this->orders->placeFromCart(
            $this->cart->items(),
            $this->cart->couponCode(),
            $request->validated(),
        );

        $this->cart->clear();
        $request->session()->put('recent_orders', array_slice(array_merge([$order->order_number], $request->session()->get('recent_orders', [])), 0, 5));

        return redirect()->to($order->confirmationUrl());
    }
}
