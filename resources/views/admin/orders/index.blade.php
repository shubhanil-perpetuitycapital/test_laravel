@extends('admin.layouts.app')

@section('title', 'Orders')

@section('content')
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('admin.orders.index') }}" @class(['btn btn-sm', 'btn-choc' => empty($filters['status']), 'btn-outline-secondary' => ! empty($filters['status'])])>All <span class="opacity-75">{{ $counts->sum() }}</span></a>
        @foreach (\App\Enums\OrderStatus::cases() as $s)
            <a href="{{ route('admin.orders.index', array_merge(request()->except('page'), ['status' => $s->value])) }}"
               @class(['btn btn-sm', 'btn-choc' => ($filters['status'] ?? '') === $s->value, 'btn-outline-secondary' => ($filters['status'] ?? '') !== $s->value])>
                {{ $s->label() }} <span class="opacity-75">{{ $counts[$s->value] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <form method="get" class="filter-bar">
        <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
        <div>
            <label class="form-label" for="oq">Search</label>
            <input id="oq" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Order no., name or mobile">
        </div>
        <div>
            <label class="form-label" for="op">Payment</label>
            <select id="op" name="payment" class="form-select" data-auto-submit>
                <option value="">Any payment</option>
                @foreach (\App\Enums\PaymentStatus::options() as $v => $l)
                    <option value="{{ $v }}" @selected(($filters['payment'] ?? '') === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label" for="ofrom">From</label>
            <input id="ofrom" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control">
        </div>
        <div>
            <label class="form-label" for="oto">To</label>
            <input id="oto" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control">
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary flex-fill">Filter</button>
            <a href="{{ route('admin.orders.export', request()->query()) }}" class="btn btn-outline-secondary flex-fill" title="Download these orders as a spreadsheet"><i class="bi bi-download"></i> CSV</a>
        </div>
    </form>

    <div class="table-card">
        @if ($orders->isEmpty())
            <x-admin.empty icon="bi-receipt" title="No orders match">Try a different status, date range or search.</x-admin.empty>
        @else
            <div class="table-responsive">
                <table class="table table-stack">
                    <thead><tr><th>Order</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Delivery</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td data-label="Order">
                                    <a class="row-link" href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a>
                                    <span class="cell-sub">{{ $order->created_at->format('j M, g:i a') }}</span>
                                </td>
                                <td data-label="Customer">{{ $order->recipient_name }}<span class="cell-sub">{{ $order->recipient_mobile }}</span></td>
                                <td data-label="Items">{{ $order->items_count }}</td>
                                <td data-label="Total">{{ money($order->total) }}@if ($order->delivery_charge_pending)<span class="cell-sub">+ courier TBC</span>@endif</td>
                                <td data-label="Payment">
                                    <span class="badge {{ $order->payment_status->badgeClass() }}">{{ $order->payment_status->label() }}</span>
                                    <span class="cell-sub">{{ strtoupper($order->payment_method) }}</span>
                                </td>
                                <td data-label="Delivery">{{ $order->delivery_method === 'local' ? 'Kolkata' : 'Courier' }}<span class="cell-sub">{{ $order->pincode }}@if ($order->expected_delivery_date) · {{ $order->expected_delivery_date->format('j M') }}@endif</span></td>
                                <td data-label="Status"><span class="badge {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($orders->hasPages())<div class="table-card__foot">{{ $orders->links() }}</div>@endif
        @endif
    </div>
@endsection
