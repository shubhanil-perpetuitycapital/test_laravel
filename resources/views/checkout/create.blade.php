@extends('layouts.app')

@section('body_class', 'page-checkout')

@php
    $field = fn (string $name) => ['form-control', 'is-invalid' => $errors->has($name)];
    $defaultPayment = old('payment_method', array_key_first($paymentMethods));
@endphp

@section('content')
    <section class="shop-hero">
        <div class="container">
            <nav aria-label="Breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('cart.index') }}">Cart</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Checkout</li>
                </ol>
            </nav>
            <h1 class="shop-hero__title">Checkout</h1>
        </div>
    </section>

    <div class="container pb-5">
        @if ($errors->any() && ! $errors->has('cart'))
            <div class="alert alert-danger" role="alert">Please check the highlighted fields.</div>
        @endif
        @error('coupon')
            <div class="alert alert-warning">{{ $message }} <a href="{{ route('cart.index') }}">Go to cart</a></div>
        @enderror
        @if ($belowMinimum)
            <div class="alert alert-warning">The minimum order is {{ money($belowMinimum) }}. <a href="{{ route('shop.index') }}">Add a little more</a></div>
        @endif

        <form method="post" action="{{ route('checkout.store') }}" class="checkout-layout" data-checkout
              data-quote-url="{{ route('delivery.check') }}" novalidate>
            @csrf

            <div class="checkout-main">
                {{-- Contact --}}
                <fieldset class="checkout-step">
                    <legend class="checkout-step__title"><span>1</span> Your details</legend>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="coName" class="form-label">Full name</label>
                            <input id="coName" name="name" value="{{ old('name') }}" autocomplete="name" required maxlength="120" @class($field('name'))>
                            <x-field-error name="name" />
                        </div>
                        <div class="col-md-6">
                            <label for="coMobile" class="form-label">Mobile number</label>
                            <div class="input-group has-validation">
                                <span class="input-group-text">+91</span>
                                <input id="coMobile" name="mobile" value="{{ old('mobile') }}" type="tel" inputmode="numeric" autocomplete="tel-national"
                                       required maxlength="14" @class($field('mobile')) aria-describedby="coMobileHelp">
                                <x-field-error name="mobile" />
                            </div>
                            <div class="form-text" id="coMobileHelp">We'll confirm your order on WhatsApp at this number.</div>
                        </div>
                        <div class="col-12">
                            <label for="coEmail" class="form-label">Email <span class="text-muted-choc">(optional)</span></label>
                            <input id="coEmail" name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="150" @class($field('email'))>
                            <x-field-error name="email" />
                        </div>
                    </div>
                </fieldset>

                {{-- Delivery --}}
                <fieldset class="checkout-step">
                    <legend class="checkout-step__title"><span>2</span> Delivery</legend>

                    @if (empty($methods))
                        <div class="alert alert-warning">Online delivery is paused right now. Please <a href="{{ $whatsapp->generalLink() }}" target="_blank" rel="noopener">message us on WhatsApp</a> to order.</div>
                    @endif

                    <div class="method-cards" role="radiogroup" aria-label="Delivery method">
                        @if (in_array('local', $methods, true))
                            <label class="method-card">
                                <input type="radio" name="delivery_method" value="local" @checked($method === 'local') data-delivery-method>
                                <span class="method-card__body">
                                    <i class="bi bi-scooter" aria-hidden="true"></i>
                                    <strong>Kolkata local delivery</strong>
                                    <small>Hand-delivered by us. Same day in many areas.</small>
                                </span>
                            </label>
                        @endif
                        @if (in_array('outstation', $methods, true))
                            <label class="method-card">
                                <input type="radio" name="delivery_method" value="outstation" @checked($method === 'outstation') data-delivery-method>
                                <span class="method-card__body">
                                    <i class="bi bi-truck" aria-hidden="true"></i>
                                    <strong>Outside Kolkata</strong>
                                    <small>Courier or India Post, packed for the heat.</small>
                                </span>
                            </label>
                        @endif
                    </div>
                    <x-field-error name="delivery_method" />

                    <div class="row g-3 mt-1">
                        <div class="col-12">
                            <label for="coAddress" class="form-label">Address</label>
                            <textarea id="coAddress" name="address" rows="2" autocomplete="street-address" required maxlength="500"
                                      placeholder="House / flat no., building, street, area" @class($field('address'))>{{ old('address') }}</textarea>
                            <x-field-error name="address" />
                        </div>
                        <div class="col-sm-4">
                            <label for="coPin" class="form-label">PIN code</label>
                            <input id="coPin" name="pincode" value="{{ $pincode }}" inputmode="numeric" maxlength="6" autocomplete="postal-code" required
                                   @class($field('pincode')) data-pincode>
                            <x-field-error name="pincode" />
                        </div>
                        <div class="col-sm-4">
                            <label for="coCity" class="form-label">City</label>
                            <input id="coCity" name="city" value="{{ old('city', $method === 'local' ? $company->city() : '') }}" autocomplete="address-level2" data-default="{{ $company->city() }}" required maxlength="80" @class($field('city'))>
                            <x-field-error name="city" />
                        </div>
                        <div class="col-sm-4">
                            <label for="coState" class="form-label">State</label>
                            <input id="coState" name="state" value="{{ old('state', $method === 'local' ? $company->state() : '') }}" autocomplete="address-level1" data-default="{{ $company->state() }}" required maxlength="80" @class($field('state'))>
                            <x-field-error name="state" />
                        </div>
                    </div>

                    <div class="delivery-box" data-quote-box aria-live="polite">
                        @if ($quote)
                            @include('checkout._quote', ['quote' => $quote])
                        @else
                            <p class="mb-0 text-muted-choc">Enter your PIN code to see delivery charges and date.</p>
                        @endif
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label for="coInstr" class="form-label">Delivery instructions <span class="text-muted-choc">(optional)</span></label>
                            <input id="coInstr" name="delivery_instructions" value="{{ old('delivery_instructions') }}" maxlength="500"
                                   placeholder="Landmark, gate code, best time to call" @class($field('delivery_instructions'))>
                        </div>
                        <div class="col-md-6">
                            <label for="coNotes" class="form-label">Order notes <span class="text-muted-choc">(optional)</span></label>
                            <input id="coNotes" name="order_notes" value="{{ old('order_notes') }}" maxlength="1000"
                                   placeholder="Gift message, surprise delivery…" @class($field('order_notes'))>
                        </div>
                    </div>
                </fieldset>

                {{-- Payment --}}
                <fieldset class="checkout-step">
                    <legend class="checkout-step__title"><span>3</span> Payment</legend>
                    <div class="method-cards" data-payment-options>
                        @foreach (\App\Models\Order::PAYMENT_METHODS as $key => $label)
                            <label class="method-card" data-payment="{{ $key }}" @if (! array_key_exists($key, $paymentMethods)) hidden @endif>
                                <input type="radio" name="payment_method" value="{{ $key }}" @checked($defaultPayment === $key)
                                       @disabled(! array_key_exists($key, $paymentMethods))>
                                <span class="method-card__body">
                                    <i class="bi {{ $key === 'upi' ? 'bi-phone' : 'bi-cash-coin' }}" aria-hidden="true"></i>
                                    <strong>{{ $key === 'upi' ? 'Pay by UPI' : 'Cash on delivery' }}</strong>
                                    <small>{{ $key === 'upi' ? 'GPay, PhonePe, Paytm or any UPI app. Payment details on the next page.' : 'Pay in cash when your order arrives.' }}</small>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <p class="small text-muted-choc mt-2" data-no-payment @if (! empty($paymentMethods)) hidden @endif>
                        No payment method is available for this order. Please <a href="{{ $whatsapp->generalLink() }}" target="_blank" rel="noopener">contact us on WhatsApp</a>.
                    </p>
                    <x-field-error name="payment_method" />
                </fieldset>
            </div>

            {{-- Summary --}}
            <aside class="order-summary checkout-summary" aria-labelledby="sumTitle">
                <h2 id="sumTitle" class="order-summary__title">Your order</h2>
                <ul class="summary-lines">
                    @foreach ($lines as $line)
                        <li>
                            <img src="{{ $line['product']->image_url }}" alt="" width="52" height="52" loading="lazy">
                            <span class="summary-lines__name">{{ $line['product']->name }} <small>× {{ $line['quantity'] }}</small></span>
                            <span>{{ money($line['line_total']) }}</span>
                        </li>
                    @endforeach
                </ul>
                <dl class="totals">
                    <div><dt>Subtotal</dt><dd data-sum="subtotal">{{ money($summary['subtotal']) }}</dd></div>
                    @if ($summary['discount'] > 0)
                        <div class="totals__discount"><dt>Discount ({{ $summary['coupon']?->code }})</dt><dd>−{{ money($summary['discount']) }}</dd></div>
                    @endif
                    <div><dt>Delivery</dt><dd data-sum="delivery">{{ $quote?->chargeLabel() ?? '—' }}</dd></div>
                    <div class="totals__grand"><dt>Total</dt><dd data-sum="total">{{ money($summary['total']) }}</dd></div>
                </dl>
                <p class="small text-muted-choc" data-sum="total_note">@if ($quote?->pending)+ courier charge, confirmed on WhatsApp @endif</p>

                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input @error('accept_terms') is-invalid @enderror" id="coTerms" name="accept_terms" value="1" @checked(old('accept_terms'))>
                    <label class="form-check-label small" for="coTerms">
                        I agree to the <a href="{{ route('pages.show', 'terms-and-conditions') }}" target="_blank">terms</a>,
                        <a href="{{ route('pages.show', 'shipping-delivery-policy') }}" target="_blank">delivery</a> and
                        <a href="{{ route('pages.show', 'cancellation-refund-policy') }}" target="_blank">refund</a> policies.
                    </label>
                    <x-field-error name="accept_terms" />
                </div>
                <button type="submit" class="btn btn-choc btn-lg w-100" @disabled(empty($methods) || $belowMinimum) data-place-order>
                    Place order
                </button>
                <p class="small text-muted-choc mt-2 mb-0"><i class="bi bi-shield-lock" aria-hidden="true"></i> We never ask for card or bank passwords.</p>
            </aside>
        </form>
    </div>
@endsection
