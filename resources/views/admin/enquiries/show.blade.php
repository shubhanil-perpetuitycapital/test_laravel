@extends('admin.layouts.app')

@section('title', 'Enquiry '.$enquiry->enquiry_number)

@section('content')
    <div class="admin-toolbar">
        <a href="{{ route('admin.enquiries.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i> Enquiries</a>
        <span class="badge fs-6 {{ $enquiry->status_badge }}">{{ $enquiry->status_label }}</span>
        <span class="text-secondary small">Received {{ $enquiry->created_at->format('j M Y, g:i a') }}</span>
        <span class="admin-toolbar__spacer"></span>
        <x-admin.delete-button :action="route('admin.enquiries.destroy', $enquiry)" label="Delete" confirm="Delete this enquiry?" />
    </div>

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="admin-card mb-4">
                <h2 class="admin-card__title">Request</h2>
                <dl class="dl-grid">
                    <dt>Customer</dt><dd>@if ($enquiry->customer_id)<a href="{{ route('admin.customers.show', $enquiry->customer_id) }}">{{ $enquiry->name }}</a>@else{{ $enquiry->name }}@endif</dd>
                    <dt>Mobile</dt><dd><a href="tel:+91{{ $enquiry->mobile }}">+91 {{ $enquiry->mobile }}</a></dd>
                    @if ($enquiry->email)<dt>Email</dt><dd><a href="mailto:{{ $enquiry->email }}">{{ $enquiry->email }}</a></dd>@endif
                    <dt>Occasion</dt><dd>{{ $enquiry->occasion ?: '—' }}</dd>
                    <dt>Chocolate</dt><dd>{{ $enquiry->chocolate_type ?: '—' }}</dd>
                    <dt>Quantity</dt><dd>{{ $enquiry->quantity }}</dd>
                    <dt>Budget</dt><dd>{{ $enquiry->budget ?: '—' }}</dd>
                    <dt>Needed by</dt><dd>{{ $enquiry->required_date?->format('l, j M Y') ?? '—' }}@if ($enquiry->required_date) <span class="text-secondary">({{ $enquiry->required_date->diffForHumans() }})</span>@endif</dd>
                    <dt>Delivery PIN</dt><dd>{{ $enquiry->delivery_pincode ?: '—' }}</dd>
                    <dt>Details</dt><dd>{!! $enquiry->message ? nl2br(e($enquiry->message)) : '—' !!}</dd>
                </dl>
                @if ($enquiry->reference_image)
                    <a href="{{ route('admin.enquiries.image', $enquiry) }}" target="_blank" class="d-inline-block mt-3">
                        <img src="{{ route('admin.enquiries.image', $enquiry) }}" alt="Reference image" style="max-width:220px;border-radius:10px">
                    </a>
                @endif
            </div>

            @if ($enquiry->convertedOrder)
                <div class="alert alert-success">Converted to order <a href="{{ route('admin.orders.show', $enquiry->convertedOrder) }}" class="alert-link">{{ $enquiry->convertedOrder->order_number }}</a>.</div>
            @else
                <form method="post" action="{{ route('admin.enquiries.convert', $enquiry) }}" class="admin-card" novalidate>
                    @csrf
                    <h2 class="admin-card__title">Turn into an order</h2>
                    <p class="small text-secondary">Once the customer accepts the quote, create a confirmed order. It appears in Orders and on their tracking page. Stock is not changed.</p>
                    <div class="row g-3">
                        <div class="col-md-8"><x-admin.input name="description" label="Item description" :value="trim(($enquiry->occasion ? $enquiry->occasion.' – ' : '').'custom chocolates'.($enquiry->chocolate_type ? ' ('.$enquiry->chocolate_type.')' : ''))" required /></div>
                        <div class="col-md-4"><x-admin.input name="quantity" label="Quantity" type="number" min="1" value="1" required help="Use 1 for a single package price." /></div>
                        <div class="col-md-4"><x-admin.input name="unit_price" label="Price each" type="number" step="0.01" min="0" :value="$enquiry->quoted_amount" prefix="₹" required /></div>
                        <div class="col-md-4"><x-admin.input name="delivery_charge" label="Delivery charge" type="number" step="0.01" min="0" value="0" prefix="₹" /></div>
                        <div class="col-md-4"><x-admin.input name="expected_delivery_date" label="Delivery date" type="date" :value="$enquiry->required_date?->toDateString()" /></div>
                        <div class="col-md-4"><x-admin.select name="delivery_method" label="Delivery" :options="\App\Models\Order::DELIVERY_METHODS" value="local" /></div>
                        <div class="col-md-4"><x-admin.select name="payment_method" label="Payment method" :options="\App\Models\Order::PAYMENT_METHODS" value="upi" /></div>
                        <div class="col-md-4"><x-admin.select name="payment_status" label="Payment" :options="['unpaid' => 'Not paid yet', 'paid' => 'Paid']" value="unpaid" /></div>
                        <div class="col-12"><x-admin.input name="address" label="Delivery address" value="" required /></div>
                        <div class="col-md-4"><x-admin.input name="city" label="City" :value="$company->city()" required /></div>
                        <div class="col-md-4"><x-admin.input name="state" label="State" :value="$company->state()" required /></div>
                        <div class="col-md-4"><x-admin.input name="pincode" label="PIN code" :value="$enquiry->delivery_pincode" required inputmode="numeric" maxlength="6" /></div>
                    </div>
                    <button class="btn btn-choc mt-3">Create order</button>
                </form>
            @endif
        </div>

        <div class="col-xl-5">
            <form method="post" action="{{ route('admin.enquiries.update', $enquiry) }}" class="admin-card mb-4">
                @csrf @method('PATCH')
                <h2 class="admin-card__title">Quote & follow-up</h2>
                <div class="mb-3"><x-admin.select name="status" label="Status" :options="\App\Models\CustomOrderEnquiry::STATUSES" :value="$enquiry->status" /></div>
                <div class="mb-3"><x-admin.input name="quoted_amount" label="Quoted amount (total)" type="number" step="0.01" min="0" :value="$enquiry->quoted_amount" prefix="₹" /></div>
                <div class="mb-3"><x-admin.textarea name="quotation_notes" label="Quote details for the customer" :value="$enquiry->quotation_notes" rows="3" help="Included in the WhatsApp quote message." /></div>
                <div class="mb-3"><x-admin.textarea name="internal_notes" label="Internal notes" :value="$enquiry->internal_notes" rows="3" /></div>
                <button class="btn btn-choc">Save</button>
            </form>

            <div class="admin-card">
                <h2 class="admin-card__title">Contact on WhatsApp</h2>
                <div class="wa-actions">
                    <a href="{{ $whatsapp->enquiryReplyLink($enquiry) }}" target="_blank" rel="noopener" class="btn btn-wa">
                        <i class="bi bi-whatsapp me-1"></i> {{ $enquiry->quoted_amount ? 'Send quote' : 'Reply to enquiry' }}
                    </a>
                    <a href="{{ $whatsapp->link(null, $enquiry->mobile) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary">Open chat</a>
                </div>
                <p class="small text-secondary mt-2 mb-0">Save the quote first so the message includes it.</p>
            </div>
        </div>
    </div>
@endsection
