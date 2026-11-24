@extends('admin.layouts.app')

@section('title', 'Order '.$order->order_number)

@section('content')
    <div class="admin-toolbar">
        <a href="{{ route('admin.orders.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i> Orders</a>
        <span class="badge fs-6 {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span>
        <span class="badge fs-6 {{ $order->payment_status->badgeClass() }}">{{ $order->payment_status->label() }}</span>
        <span class="text-secondary small">Placed {{ $order->created_at->format('j M Y, g:i a') }} · {{ ucfirst($order->source) }}</span>
        <span class="admin-toolbar__spacer"></span>
        <a href="{{ route('admin.orders.invoice', $order) }}" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer"></i> Invoice</a>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            {{-- Items --}}
            <div class="table-card mb-4">
                <div class="table-responsive">
                    <table class="table items-table">
                        <thead><tr><th>Item</th><th class="text-end">Price</th><th class="text-end">Qty</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td>
                                        @if ($item->product && ! $item->product->trashed())
                                            <a href="{{ route('admin.products.edit', $item->product) }}" class="row-link">{{ $item->product_name }}</a>
                                        @else
                                            <strong>{{ $item->product_name }}</strong>
                                        @endif
                                        <span class="cell-sub">{{ $item->sku }}@if ($item->weight) · {{ $item->weight }}@endif</span>
                                    </td>
                                    <td class="text-end">{{ money($item->unit_price, true) }}</td>
                                    <td class="text-end">{{ $item->quantity }}</td>
                                    <td class="text-end">{{ money($item->line_total, true) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr><td colspan="3" class="text-end">Subtotal</td><td class="text-end">{{ money($order->subtotal, true) }}</td></tr>
                            @if ((float) $order->discount_amount > 0)
                                <tr><td colspan="3" class="text-end">Discount ({{ $order->coupon_code }})</td><td class="text-end text-success">−{{ money($order->discount_amount, true) }}</td></tr>
                            @endif
                            <tr><td colspan="3" class="text-end">Delivery</td><td class="text-end">{{ $order->delivery_charge_pending ? 'To be confirmed' : money($order->delivery_charge, true) }}</td></tr>
                            <tr class="fw-bold"><td colspan="3" class="text-end">Total</td><td class="text-end">{{ money($order->total, true) }}</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="row g-4">
                {{-- Customer & delivery --}}
                <div class="col-md-6">
                    <div class="admin-card h-100">
                        <h2 class="admin-card__title">Customer & delivery</h2>
                        <dl class="dl-grid">
                            <dt>Name</dt><dd><a href="{{ route('admin.customers.show', $order->customer_id) }}">{{ $order->recipient_name }}</a> <span class="text-secondary small">({{ $customerOrders }} {{ \Illuminate\Support\Str::plural('order', $customerOrders) }})</span></dd>
                            <dt>Mobile</dt><dd><a href="tel:+91{{ $order->recipient_mobile }}">+91 {{ $order->recipient_mobile }}</a></dd>
                            @if ($order->customer?->email)<dt>Email</dt><dd>{{ $order->customer->email }}</dd>@endif
                            <dt>Method</dt><dd>{{ $order->delivery_method_label }}@if ($order->deliveryZone) · {{ $order->deliveryZone->name }}@endif</dd>
                            <dt>Address</dt><dd>{{ $order->full_address }}</dd>
                            @if ($order->delivery_instructions)<dt>Instructions</dt><dd>{{ $order->delivery_instructions }}</dd>@endif
                            @if ($order->order_notes)<dt>Order notes</dt><dd>{{ $order->order_notes }}</dd>@endif
                            <dt>Expected</dt><dd>{{ $order->expected_delivery_date?->format('l, j M Y') ?? ($order->delivery_estimate_text ?: '—') }}</dd>
                        </dl>
                    </div>
                </div>

                {{-- Payment --}}
                <div class="col-md-6">
                    <form method="post" action="{{ route('admin.orders.payment', $order) }}" class="admin-card h-100">
                        @csrf @method('PATCH')
                        <h2 class="admin-card__title">Payment · {{ $order->payment_method_label }}</h2>
                        @if ($order->payment_reference || $order->payment_screenshot)
                            <div class="alert alert-light border small">
                                @if ($order->payment_reference)Reference: <strong>{{ $order->payment_reference }}</strong>@endif
                                @if ($order->payment_submitted_at)<br>Sent {{ $order->payment_submitted_at->format('j M, g:i a') }}@endif
                                @if ($order->payment_screenshot)<br><a href="{{ route('admin.orders.screenshot', $order) }}" target="_blank">View payment screenshot</a>@endif
                            </div>
                            <p class="small text-secondary">Check your bank or UPI app for this reference and amount before marking it paid.</p>
                        @endif
                        <div class="mb-2"><x-admin.select name="payment_status" label="Payment status" :options="\App\Enums\PaymentStatus::options()" :value="$order->payment_status" /></div>
                        <div class="mb-2"><x-admin.input name="payment_reference" label="Reference" :value="$order->payment_reference" /></div>
                        @if ($order->delivery_charge_pending)
                            <div class="mb-2"><x-admin.input name="delivery_charge" label="Courier charge (confirmed)" type="number" step="0.01" min="0" value="" prefix="₹" help="Adds to the order total." /></div>
                        @endif
                        <button class="btn btn-choc btn-sm mt-1">Save payment</button>
                    </form>
                </div>

                {{-- Shipping --}}
                <div class="col-md-6">
                    <form method="post" action="{{ route('admin.orders.shipping', $order) }}" class="admin-card h-100">
                        @csrf @method('PATCH')
                        <h2 class="admin-card__title">Courier & tracking</h2>
                        <div class="mb-2"><x-admin.input name="courier_name" label="Courier / delivery partner" :value="$order->courier_name" placeholder="DTDC, India Post, Own rider…" /></div>
                        <div class="mb-2"><x-admin.input name="tracking_number" label="Tracking number" :value="$order->tracking_number" /></div>
                        <div class="mb-2"><x-admin.input name="tracking_url" label="Courier tracking link" type="url" :value="$order->tracking_url" placeholder="https://" /></div>
                        <div class="mb-2"><x-admin.input name="expected_delivery_date" label="Expected delivery date" type="date" :value="$order->expected_delivery_date?->toDateString()" /></div>
                        <button class="btn btn-choc btn-sm mt-1">Save delivery details</button>
                    </form>
                </div>

                {{-- Internal notes --}}
                <div class="col-md-6">
                    <form method="post" action="{{ route('admin.orders.notes', $order) }}" class="admin-card h-100">
                        @csrf @method('PATCH')
                        <h2 class="admin-card__title">Internal notes</h2>
                        <x-admin.textarea name="internal_notes" label="Only visible to your team" :value="$order->internal_notes" rows="6" />
                        <button class="btn btn-choc btn-sm mt-2">Save notes</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            {{-- Status --}}
            <form method="post" action="{{ route('admin.orders.status', $order) }}" class="admin-card mb-4"
                  @if ($order->status !== \App\Enums\OrderStatus::Cancelled) data-confirm-cancel @endif>
                @csrf @method('PATCH')
                <h2 class="admin-card__title">Update status</h2>
                @if ($order->status === \App\Enums\OrderStatus::Cancelled)
                    <p class="text-secondary small">This order is cancelled. Stock was returned. You can still add a note.</p>
                @endif
                <div class="mb-2"><x-admin.select name="status" label="Status" :options="\App\Enums\OrderStatus::options()" :value="$order->status" /></div>
                <div class="mb-2"><x-admin.textarea name="comment" label="Note (optional)" value="" rows="2" placeholder="e.g. Rider assigned, arriving by 6 pm" /></div>
                <x-admin.switch name="visible_to_customer" label="Show this note on the tracking page" :checked="true" />
                <button class="btn btn-choc w-100 mt-2">Update status</button>
                <p class="small text-secondary mt-2 mb-0">Choosing Cancelled returns the items to stock.</p>
            </form>

            {{-- WhatsApp --}}
            <div class="admin-card mb-4">
                <h2 class="admin-card__title">Message the customer</h2>
                <p class="small text-secondary">Opens WhatsApp with the message filled in from your templates. You press send.</p>
                <div class="wa-actions">
                    <a href="{{ $whatsapp->confirmationToCustomerLink($order) }}" target="_blank" rel="noopener" class="btn btn-wa"><i class="bi bi-whatsapp me-1"></i> Send order confirmation</a>
                    <a href="{{ $whatsapp->statusToCustomerLink($order) }}" target="_blank" rel="noopener" class="btn btn-outline-success"><i class="bi bi-whatsapp me-1"></i> Send status: {{ $order->status->label() }}</a>
                    <a href="{{ $whatsapp->link(null, $order->recipient_mobile) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary">Open chat</a>
                </div>
            </div>

            {{-- History --}}
            <div class="admin-card">
                <h2 class="admin-card__title">History</h2>
                <ul class="history">
                    @foreach ($order->statusHistories as $entry)
                        <li>
                            <strong>{{ $entry->to_status_label }}</strong>
                            <span class="text-secondary small">· {{ $entry->created_at->format('j M, g:i a') }} · {{ $entry->user?->name ?? 'Customer' }}</span>
                            @if ($entry->comment)<br>{{ $entry->comment }}@endif
                            @unless ($entry->visible_to_customer)<span class="badge text-bg-light border ms-1">internal</span>@endunless
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endsection
