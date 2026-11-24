<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CartService;
use App\Services\CompanyProfile;
use App\Services\CouponService;
use App\Services\SeoData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function index(CompanyProfile $company): View
    {
        $lines = $this->cart->lines();
        $notices = $this->cart->notices();

        $preliminary = $this->cart->summary();
        $quote = $this->cart->rememberedQuote($preliminary['subtotal'] - $preliminary['discount']);
        $summary = $this->cart->summary($quote);
        $remembered = $this->cart->rememberedDelivery();

        $seo = SeoData::make('Your cart | '.$company->name(), null, noindex: true);

        return view('cart.index', compact('lines', 'notices', 'summary', 'remembered', 'seo'));
    }

    /** Add to cart. Responds with JSON for fetch() calls, redirects otherwise. */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:'.config('store.max_line_quantity')],
            'buy_now' => ['nullable', 'boolean'],
        ]);

        $product = Product::query()->with('category')->find($data['product_id']);
        $result = $product
            ? $this->cart->add($product, (int) ($data['quantity'] ?? 1))
            : ['ok' => false, 'message' => 'This product is no longer available.', 'quantity' => 0];

        if ($request->wantsJson()) {
            return response()->json($result + ['count' => $this->cart->count()], $result['ok'] ? 200 : 422);
        }

        if (! $result['ok']) {
            return back()->withErrors(['cart' => $result['message']]);
        }

        if ($request->boolean('buy_now')) {
            return redirect()->route('checkout.create');
        }

        return redirect()->route('cart.index')->with('success', $result['message']);
    }

    public function update(Request $request, int $productId): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:'.config('store.max_line_quantity')]]);
        $this->cart->update($productId, (int) $data['quantity']);

        return redirect()->route('cart.index');
    }

    public function destroy(int $productId): RedirectResponse
    {
        $this->cart->remove($productId);

        return redirect()->route('cart.index')->with('success', 'Item removed from your cart.');
    }

    public function applyCoupon(Request $request, CouponService $coupons): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:40']]);

        $subtotal = $this->cart->summary()['subtotal'];
        $result = $coupons->evaluate($data['code'], $subtotal);

        if ($result['error']) {
            return back()->withErrors(['code' => $result['error']])->withInput();
        }

        $this->cart->applyCoupon($data['code']);

        return back()->with('success', 'Code '.$result['coupon']->code.' applied: you save '.money($result['discount']).'.');
    }

    public function removeCoupon(): RedirectResponse
    {
        $this->cart->applyCoupon(null);

        return back()->with('success', 'Code removed.');
    }
}
