@extends('admin.layouts.app')

@section('title', $customer->name)

@section('content')
    <div class="admin-toolbar">
        <a href="{{ route('admin.customers.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i> Customers</a>
        <span class="text-secondary small">Customer since {{ $customer->created_at->format('M Y') }}</span>
        <span class="admin-toolbar__spacer"></span>
        <a href="{{ $whatsapp->link(null, $customer->mobile) }}" target="_blank" rel="noopener" class="btn btn-wa btn-sm"><i class="bi bi-whatsapp me-1"></i> WhatsApp</a>
        <a href="tel:+91{{ $customer->mobile }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-telephone me-1"></i> Call</a>
    </div>

    <div class="stat-grid mb-4">
        <div class="stat"><span class="stat__label">Orders</span><span class="stat__value">{{ $orders->count() }}</span></div>
        <div class="stat"><span class="stat__label">Total spent</span><span class="stat__value">{{ money($totalSpent) }}</span><span class="stat__sub">excludes cancelled</span></div>
        <div class="stat"><span class="stat__label">Average order</span><span class="stat__value">{{ money($orders->where('status', '!=', \App\Enums\OrderStatus::Cancelled)->avg('total') ?? 0) }}</span></div>
        <div class="stat"><span class="stat__label">Custom enquiries</span><span class="stat__value">{{ $enquiries->count() }}</span></div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="table-card mb-4">
                <div class="p-3"><h2 class="admin-card__title mb-0">Order history</h2></div>
                @if ($orders->isEmpty())
                    <x-admin.empty icon="bi-receipt" title="No orders yet" />
                @else
                    <div class="table-responsive">
                        <table class="table table-stack">
                            <thead><tr><th>Order</th><th>Date</th><th>Total</th><th>Payment</th><th>Status</th></tr></thead>
                            <tbody>
                                @foreach ($orders as $order)
                                    <tr>
                                        <td data-label="Order"><a class="row-link" href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a></td>
                                        <td data-label="Date">{{ $order->created_at->format('j M Y') }}</td>
                                        <td data-label="Total">{{ money($order->total) }}</td>
                                        <td data-label="Payment"><span class="badge {{ $order->payment_status->badgeClass() }}">{{ $order->payment_status->label() }}</span></td>
                                        <td data-label="Status"><span class="badge {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            @if ($enquiries->isNotEmpty())
                <div class="admin-card">
                    <h2 class="admin-card__title">Custom enquiries</h2>
                    <ul class="mini-list">
                        @foreach ($enquiries as $e)
                            <li><a href="{{ route('admin.enquiries.show', $e) }}">{{ $e->enquiry_number }} · {{ $e->occasion ?: 'Custom order' }}</a><span class="badge {{ $e->status_badge }}">{{ $e->status_label }}</span></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="col-xl-4">
            <form method="post" action="{{ route('admin.customers.update', $customer) }}" class="admin-card">
                @csrf @method('PATCH')
                <h2 class="admin-card__title">Contact details</h2>
                <p class="mb-3"><strong>Mobile:</strong> +91 {{ $customer->mobile }}</p>
                <div class="mb-3"><x-admin.input name="name" label="Name" :value="$customer->name" required /></div>
                <div class="mb-3"><x-admin.input name="email" label="Email" type="email" :value="$customer->email" /></div>
                <div class="mb-3"><x-admin.textarea name="notes" label="Customer notes" :value="$customer->notes" rows="5" help="Preferences, allergies, birthdays — only your team sees this." /></div>
                <button class="btn btn-choc">Save</button>
            </form>
        </div>
    </div>
@endsection
