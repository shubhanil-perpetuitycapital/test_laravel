@extends('layouts.app')

@section('body_class', 'page-confirmation')

@section('content')
    <div class="container confirm-page">
        <div class="confirm-hero">
            <i class="bi bi-check2-circle" aria-hidden="true"></i>
            <h1>Thank you, {{ \Illuminate\Support\Str::before($order->recipient_name, ' ') }}!</h1>
            <p>Your order <strong>{{ $order->order_number }}</strong> has been placed. Keep this number to track it.</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                {{-- Step 1: send to WhatsApp --}}
                <section class="confirm-card confirm-card--wa">
                    <h2><i class="bi bi-whatsapp" aria-hidden="true"></i> Send us your order on WhatsApp</h2>
                    <p>This opens WhatsApp with your order details filled in. Tap send so we can confirm quickly{{ $order->delivery_charge_pending ? ' and tell you the courier charge' : '' }}.</p>
                    <a href="{{ $whatsapp->orderToBusinessLink($order) }}" class="btn btn-wa btn-lg" target="_blank" rel="noopener">Open WhatsApp</a>
                </section>

                {{-- Step 2: UPI payment --}}
                @if ($order->payment_method === 'upi')
                    <section class="confirm-card" id="pay">
                        <h2>Pay {{ money($order->total, true) }} by UPI</h2>

                        @if ($order->payment_status === \App\Enums\PaymentStatus::Paid)
                            <p class="text-success mb-0"><i class="bi bi-check-circle-fill"></i> Payment received. Thank you!</p>
                        @else
                            @if ($order->delivery_charge_pending)
                                <p class="alert alert-info py-2">This total doesn't include the courier charge yet. You can pay it now and the courier charge later, or wait for our WhatsApp message with the full amount.</p>
                            @endif

                            <div class="upi-box">
                                <div>
                                    <p class="upi-box__label">UPI ID</p>
                                    <p class="upi-box__id">
                                        <span data-copy-source>{{ $upi['id'] }}</span>
                                        <button type="button" class="btn btn-sm btn-outline-choc" data-copy aria-label="Copy UPI ID">Copy</button>
                                    </p>
                                    <p class="upi-box__name">Paying to: {{ $upi['name'] }}</p>
                                    <a href="{{ $upi['link'] }}" class="btn btn-choc d-md-none mt-2">Pay with a UPI app</a>
                                </div>
                                @if ($upi['qr'])
                                    <img src="{{ $upi['qr'] }}" alt="UPI QR code for {{ $upi['name'] }}" class="upi-box__qr" width="180" height="180">
                                @endif
                            </div>
                            @if ($upi['instructions'])
                                <p class="small text-muted-choc">{{ $upi['instructions'] }}</p>
                            @endif

                            @if ($order->payment_status === \App\Enums\PaymentStatus::Submitted)
                                <div class="alert alert-success">We received reference <strong>{{ $order->payment_reference }}</strong> and will confirm once we see the payment. Sent the wrong one? Submit it again below.</div>
                            @endif

                            @if ($order->canSubmitPayment())
                                <form method="post" action="{{ \Illuminate\Support\Facades\URL::signedRoute('orders.payment', ['order' => $order->order_number]) }}"
                                      enctype="multipart/form-data" class="payment-form" novalidate>
                                    @csrf
                                    <div class="mb-3">
                                        <label for="payRef" class="form-label">UPI transaction / UTR number</label>
                                        <input id="payRef" name="payment_reference" value="{{ old('payment_reference') }}" maxlength="40" required
                                               autocomplete="off" @class(['form-control', 'is-invalid' => $errors->has('payment_reference')])
                                               placeholder="e.g. 432198765432">
                                        <x-field-error name="payment_reference" />
                                    </div>
                                    <div class="mb-3">
                                        <label for="payShot" class="form-label">Payment screenshot <span class="text-muted-choc">(optional)</span></label>
                                        <input id="payShot" name="screenshot" type="file" accept="image/jpeg,image/png,image/webp"
                                               @class(['form-control', 'is-invalid' => $errors->has('screenshot')])>
                                        <x-field-error name="screenshot" />
                                    </div>
                                    <button type="submit" class="btn btn-choc">Send payment details</button>
                                </form>
                            @endif
                        @endif
                    </section>
                @else
                    <section class="confirm-card">
                        <h2>Cash on delivery</h2>
                        <p class="mb-0">Please keep {{ money($order->total, true) }}{{ $order->delivery_charge_pending ? ' plus the courier charge' : '' }} ready when your order arrives.</p>
                    </section>
                @endif

                <p class="mt-3">
                    <a href="{{ route('track.show', $order->order_number) }}" class="btn btn-outline-choc">Track this order</a>
                    <a href="{{ route('shop.index') }}" class="btn btn-link">Continue shopping</a>
                </p>
            </div>

            <div class="col-lg-5">
                <aside class="order-summary">
                    <h2 class="order-summary__title">Order {{ $order->order_number }}</h2>
                    <ul class="summary-lines summary-lines--plain">
                        @foreach ($order->items as $item)
                            <li><span class="summary-lines__name">{{ $item->product_name }} <small>× {{ $item->quantity }}</small></span><span>{{ money($item->line_total) }}</span></li>
                        @endforeach
                    </ul>
                    <dl class="totals">
                        <div><dt>Subtotal</dt><dd>{{ money($order->subtotal) }}</dd></div>
                        @if ((float) $order->discount_amount > 0)
                            <div class="totals__discount"><dt>Discount ({{ $order->coupon_code }})</dt><dd>−{{ money($order->discount_amount) }}</dd></div>
                        @endif
                        <div><dt>Delivery</dt><dd>{{ $order->delivery_charge_pending ? 'To be confirmed' : ((float) $order->delivery_charge > 0 ? money($order->delivery_charge) : 'Free') }}</dd></div>
                        <div class="totals__grand"><dt>Total</dt><dd>{{ money($order->total, true) }}</dd></div>
                    </dl>
                    <h3 class="h6 mt-3">Delivering to</h3>
                    <p class="small mb-1">{{ $order->recipient_name }} · +91 {{ $order->recipient_mobile }}</p>
                    <p class="small mb-1">{{ $order->full_address }}</p>
                    <p class="small mb-0">{{ $order->delivery_method_label }}@if ($order->delivery_estimate_text) — {{ $order->delivery_estimate_text }}@endif</p>
                </aside>
            </div>
        </div>
    </div>
@endsection
