@extends('layouts.app')

@section('body_class', 'page-cart')

@section('content')
    <section class="shop-hero">
        <div class="container">
            <h1 class="shop-hero__title">Your cart</h1>
        </div>
    </section>

    <div class="container pb-5">
        @foreach ($notices as $notice)
            <div class="alert alert-warning">{{ $notice }}</div>
        @endforeach

        @if ($lines->isEmpty())
            <div class="empty-state">
                <i class="bi bi-bag-heart" aria-hidden="true"></i>
                <h2>Your cart is empty</h2>
                <p>Bars, truffles and gift boxes are waiting for you.</p>
                <a href="{{ route('shop.index') }}" class="btn btn-choc btn-lg">Start shopping</a>
            </div>
        @else
            <div class="cart-layout">
                <div class="cart-lines">
                    @foreach ($lines as $line)
                        @php $product = $line['product']; @endphp
                        <article class="cart-line">
                            <a href="{{ $product->url }}" class="cart-line__img">
                                <img src="{{ $product->image_url }}" alt="" width="96" height="96" loading="lazy">
                            </a>
                            <div class="cart-line__info">
                                <h2 class="cart-line__name"><a href="{{ $product->url }}">{{ $product->name }}</a></h2>
                                <p class="cart-line__meta">{{ $product->weight }} · {{ money($line['unit_price']) }} each</p>
                                @if ($product->is_low_stock)
                                    <p class="cart-line__warn">Only {{ $product->stock_quantity }} left</p>
                                @endif
                                <div class="cart-line__actions">
                                    <form method="post" action="{{ route('cart.update', $product->id) }}" class="qty qty--sm" data-cart-qty>
                                        @csrf @method('PATCH')
                                        <button type="submit" name="quantity" value="{{ $line['quantity'] - 1 }}" class="qty__btn"
                                                aria-label="{{ $line['quantity'] > 1 ? 'Decrease quantity' : 'Remove' }}">
                                            <i class="bi {{ $line['quantity'] > 1 ? 'bi-dash' : 'bi-trash3' }}" aria-hidden="true"></i>
                                        </button>
                                        <span class="qty__value" aria-live="polite">{{ $line['quantity'] }}</span>
                                        <button type="submit" name="quantity" value="{{ $line['quantity'] + 1 }}" class="qty__btn" aria-label="Increase quantity"
                                                @disabled($line['quantity'] >= min($product->stock_quantity, config('store.max_line_quantity')))>
                                            <i class="bi bi-plus" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                    <form method="post" action="{{ route('cart.destroy', $product->id) }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-link btn-sm cart-line__remove">Remove</button>
                                    </form>
                                </div>
                            </div>
                            <p class="cart-line__total">{{ money($line['line_total']) }}</p>
                        </article>
                    @endforeach

                    <a href="{{ route('shop.index') }}" class="btn btn-link px-0 mt-2"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Continue shopping</a>
                </div>

                <aside class="order-summary" aria-labelledby="summaryTitle">
                    <h2 id="summaryTitle" class="order-summary__title">Order summary</h2>

                    {{-- Coupon --}}
                    @if ($summary['coupon'])
                        <div class="coupon-applied">
                            <span><i class="bi bi-tag" aria-hidden="true"></i> <strong>{{ $summary['coupon']->code }}</strong> · {{ $summary['coupon']->summary }}</span>
                            <form method="post" action="{{ route('cart.coupon.remove') }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-link btn-sm p-0">Remove</button>
                            </form>
                        </div>
                    @else
                        <form method="post" action="{{ route('cart.coupon') }}" class="coupon-form">
                            @csrf
                            <label for="couponCode" class="visually-hidden">Discount code</label>
                            <input id="couponCode" name="code" value="{{ old('code') }}" maxlength="40" placeholder="Discount code"
                                   @class(['form-control', 'is-invalid' => $errors->has('code')]) autocomplete="off">
                            <button type="submit" class="btn btn-outline-choc">Apply</button>
                        </form>
                        <x-field-error name="code" />
                        @if ($summary['coupon_error'])
                            <p class="small text-danger mt-1">{{ $summary['coupon_error'] }}</p>
                        @endif
                    @endif

                    {{-- Delivery check --}}
                    <form class="delivery-check" data-delivery-check data-url="{{ route('delivery.check') }}">
                        <p class="form-label mb-2">Check delivery</p>
                        <div class="seg-control" role="radiogroup" aria-label="Delivery type">
                            <input type="radio" class="btn-check" name="method" id="cartLocal" value="local" @checked(($remembered['method'] ?? 'local') === 'local')>
                            <label class="btn" for="cartLocal">Kolkata</label>
                            <input type="radio" class="btn-check" name="method" id="cartOut" value="outstation" @checked(($remembered['method'] ?? '') === 'outstation')>
                            <label class="btn" for="cartOut">Outside Kolkata</label>
                        </div>
                        <div class="coupon-form mt-2">
                            <label for="cartPin" class="visually-hidden">PIN code</label>
                            <input id="cartPin" name="pincode" class="form-control" inputmode="numeric" maxlength="6" pattern="[0-9]{6}"
                                   value="{{ $remembered['pincode'] ?? '' }}" placeholder="PIN code" autocomplete="postal-code">
                            <button type="submit" class="btn btn-outline-choc">Check</button>
                        </div>
                        <p class="delivery-result" data-delivery-result aria-live="polite">
                            @if ($summary['delivery'])
                                @if ($summary['delivery']->ok)
                                    <span class="text-success"><i class="bi bi-check-circle" aria-hidden="true"></i> {{ $summary['delivery']->estimate }}</span>
                                @else
                                    <span class="text-danger">{{ $summary['delivery']->error }}</span>
                                @endif
                            @endif
                        </p>
                    </form>

                    <dl class="totals">
                        <div><dt>Subtotal ({{ $summary['count'] }} {{ \Illuminate\Support\Str::plural('item', $summary['count']) }})</dt><dd data-sum="subtotal">{{ money($summary['subtotal']) }}</dd></div>
                        @if ($summary['discount'] > 0)
                            <div class="totals__discount"><dt>Discount</dt><dd data-sum="discount">−{{ money($summary['discount']) }}</dd></div>
                        @endif
                        <div><dt>Delivery</dt><dd data-sum="delivery">{{ $summary['delivery']?->chargeLabel() ?? 'Enter PIN code' }}</dd></div>
                        <div class="totals__grand"><dt>Total</dt><dd data-sum="total">{{ money($summary['total']) }}</dd></div>
                    </dl>
                    <p class="small text-muted-choc" data-sum="total_note">@if ($summary['delivery']?->pending)+ courier charge, confirmed on WhatsApp @endif</p>

                    <a href="{{ route('checkout.create') }}" class="btn btn-choc btn-lg w-100">Proceed to checkout</a>
                </aside>
            </div>
        @endif
    </div>
@endsection
