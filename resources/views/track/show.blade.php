@extends('layouts.app')

@section('content')
    <section class="shop-hero">
        <div class="container">
            <nav aria-label="Breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('track.index') }}">Track order</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $order->order_number }}</li>
                </ol>
            </nav>
            <h1 class="shop-hero__title">Order {{ $order->order_number }}</h1>
            <p class="shop-hero__intro">Placed {{ $order->created_at->format('j M Y, g:i a') }}</p>
        </div>
    </section>

    <div class="container pb-5">
        <div class="row g-4">
            <div class="col-lg-7">
                <section class="confirm-card">
                    <h2 class="h4 mb-1">{{ $order->status->label() }}</h2>
                    <p class="text-muted-choc">{{ $order->status->customerText() }}</p>

                    @if ($order->status === \App\Enums\OrderStatus::Cancelled)
                        <p class="mb-0">Questions? <a href="{{ $whatsapp->generalLink() }}" target="_blank" rel="noopener">Message us on WhatsApp</a>.</p>
                    @else
                        <ol class="timeline">
                            @foreach ($journey as $i => $step)
                                <li @class(['is-done' => $currentIndex !== false && $i < $currentIndex, 'is-current' => $i === $currentIndex])>
                                    <span class="timeline__dot" aria-hidden="true"></span>
                                    <span class="timeline__label">{{ $step->label() }}</span>
                                    @if ($i === $currentIndex)<span class="visually-hidden">(current status)</span>@endif
                                </li>
                            @endforeach
                        </ol>
                    @endif

                    @if ($order->courier_name || $order->tracking_number)
                        <div class="tracking-box">
                            <p class="mb-1"><strong>Courier:</strong> {{ $order->courier_name ?: '—' }}</p>
                            @if ($order->tracking_number)
                                <p class="mb-1"><strong>Tracking number:</strong> {{ $order->tracking_number }}</p>
                            @endif
                            @if ($order->tracking_url)
                                <a href="{{ $order->tracking_url }}" target="_blank" rel="noopener nofollow" class="btn btn-sm btn-outline-choc mt-1">Track with courier</a>
                            @endif
                        </div>
                    @endif

                    @if ($order->expected_delivery_date && ! in_array($order->status, [\App\Enums\OrderStatus::Delivered, \App\Enums\OrderStatus::Cancelled], true))
                        <p class="mt-3 mb-0">Expected delivery: <strong>{{ $order->expected_delivery_date->format('l, j M') }}</strong></p>
                    @elseif ($order->delivery_estimate_text && $order->delivery_method === 'outstation')
                        <p class="mt-3 mb-0 small text-muted-choc">{{ $order->delivery_estimate_text }}</p>
                    @endif
                </section>

                @if ($order->statusHistories->isNotEmpty())
                    <section class="confirm-card">
                        <h2 class="h5">Updates</h2>
                        <ul class="list-unstyled history-list mb-0">
                            @foreach ($order->statusHistories as $entry)
                                <li>
                                    <strong>{{ $entry->to_status_label }}</strong>
                                    <span class="text-muted-choc small">{{ $entry->created_at->format('j M, g:i a') }}</span>
                                    @if ($entry->comment)<br><span class="small">{{ $entry->comment }}</span>@endif
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>

            <div class="col-lg-5">
                <aside class="order-summary">
                    <h2 class="order-summary__title">Items</h2>
                    <ul class="summary-lines summary-lines--plain">
                        @foreach ($order->items as $item)
                            <li><span class="summary-lines__name">{{ $item->product_name }} <small>× {{ $item->quantity }}</small></span><span>{{ money($item->line_total) }}</span></li>
                        @endforeach
                    </ul>
                    <dl class="totals">
                        <div class="totals__grand"><dt>Total</dt><dd>{{ money($order->total, true) }}</dd></div>
                        <div><dt>Payment</dt><dd>{{ $order->payment_method_label }} · {{ $order->payment_status->label() }}</dd></div>
                    </dl>
                    <p class="small mb-0">{{ $order->delivery_method_label }} to {{ $order->city }} {{ $order->pincode }}</p>
                </aside>
            </div>
        </div>
    </div>
@endsection
