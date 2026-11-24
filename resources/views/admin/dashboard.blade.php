@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php $pendingSetup = collect($checklist)->where('done', false); @endphp

    @if ($pendingSetup->isNotEmpty() && auth()->user()->isOwner())
        <div class="admin-card setup-card mb-4">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                    <h2 class="h5 mb-1">Finish setting up your store</h2>
                    <p class="text-secondary mb-0">{{ count($checklist) - $pendingSetup->count() }} of {{ count($checklist) }} done. Customers see these details on every page and at checkout.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.settings.edit', 'company') }}" class="btn btn-choc">Company setup</a>
                    <a href="{{ route('admin.settings.edit', ['store', 'tab' => 'payments']) }}" class="btn btn-outline-secondary">Payment settings</a>
                </div>
            </div>
            <ul class="setup-list">
                @foreach ($checklist as $item)
                    <li @class(['is-done' => $item['done']])>
                        <i class="bi {{ $item['done'] ? 'bi-check-circle-fill' : 'bi-circle' }}" aria-hidden="true"></i>
                        {{ $item['label'] }}<span class="visually-hidden">{{ $item['done'] ? ' (done)' : ' (to do)' }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="stat-grid">
        <div class="stat stat--accent">
            <span class="stat__label">Revenue this month</span>
            <span class="stat__value">{{ money($stats['revenue_month']) }}</span>
            <span class="stat__sub">{{ $stats['orders_month'] }} {{ \Illuminate\Support\Str::plural('order', $stats['orders_month']) }} · excludes cancelled</span>
        </div>
        <a class="stat" href="{{ route('admin.orders.index', ['from' => today()->toDateString()]) }}">
            <span class="stat__label">Today's orders</span>
            <span class="stat__value">{{ number_format($stats['orders_today']) }}</span>
        </a>
        <a class="stat" href="{{ route('admin.orders.index', ['status' => 'pending']) }}">
            <span class="stat__label">Pending orders</span>
            <span @class(['stat__value', 'text-warning' => $stats['orders_pending'] > 0])>{{ number_format($stats['orders_pending']) }}</span>
        </a>
        <a class="stat" href="{{ route('admin.orders.index', ['payment' => 'submitted']) }}">
            <span class="stat__label">Payments to verify</span>
            <span @class(['stat__value', 'text-warning' => $stats['payments_to_verify'] > 0])>{{ number_format($stats['payments_to_verify']) }}</span>
        </a>
        <a class="stat" href="{{ route('admin.orders.index') }}">
            <span class="stat__label">Total orders</span>
            <span class="stat__value">{{ number_format($stats['orders_total']) }}</span>
        </a>
        <a class="stat" href="{{ route('admin.orders.index', ['status' => 'delivered']) }}">
            <span class="stat__label">Completed orders</span>
            <span class="stat__value">{{ number_format($stats['orders_completed']) }}</span>
        </a>
        <a class="stat" href="{{ route('admin.customers.index') }}">
            <span class="stat__label">Customers</span>
            <span class="stat__value">{{ number_format($stats['customers']) }}</span>
        </a>
        <a class="stat" href="{{ route('admin.products.index') }}">
            <span class="stat__label">Products</span>
            <span class="stat__value">{{ number_format($stats['products']) }}</span>
            @if ($stats['out_of_stock'])<span class="stat__sub text-danger">{{ $stats['out_of_stock'] }} out of stock</span>@endif
        </a>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-xl-8">
            <div class="admin-card h-100">
                <h2 class="admin-card__title">Orders and revenue, last 12 months</h2>
                <div class="chart-box"><canvas id="ordersChart" aria-label="Monthly orders and revenue chart" role="img"></canvas></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="admin-card h-100">
                <h2 class="admin-card__title d-flex justify-content-between">Needs attention</h2>
                <ul class="mini-list">
                    <li><a href="{{ route('admin.enquiries.index', ['status' => 'new']) }}">New custom enquiries</a><span class="badge text-bg-warning">{{ $stats['enquiries_new'] }}</span></li>
                    <li><a href="{{ route('admin.orders.index', ['payment' => 'submitted']) }}">UPI payments to verify</a><span class="badge text-bg-warning">{{ $stats['payments_to_verify'] }}</span></li>
                    <li><a href="{{ route('admin.reviews.index', ['status' => 'pending']) }}">Reviews to approve</a><span class="badge text-bg-light border">{{ $stats['reviews_pending'] }}</span></li>
                </ul>
                <h3 class="h6 mt-4 mb-2">Low stock</h3>
                @if ($lowStock->isEmpty())
                    <p class="text-secondary small mb-0">All products have more than {{ \App\Models\Product::LOW_STOCK_THRESHOLD }} in stock.</p>
                @else
                    <ul class="mini-list">
                        @foreach ($lowStock as $p)
                            <li><a href="{{ route('admin.products.edit', $p) }}">{{ $p->name }}</a><span @class(['badge', 'text-bg-danger' => ! $p->stock_quantity, 'text-bg-warning' => $p->stock_quantity])>{{ $p->stock_quantity ?: 'Sold out' }}</span></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>

    <div class="table-card mt-4">
        <div class="d-flex justify-content-between align-items-center p-3">
            <h2 class="admin-card__title mb-0">Recent orders</h2>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-secondary">All orders</a>
        </div>
        @if ($recentOrders->isEmpty())
            <x-admin.empty icon="bi-receipt" title="No orders yet">Orders placed on the website appear here.</x-admin.empty>
        @else
            <div class="table-responsive">
                <table class="table table-stack">
                    <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Placed</th></tr></thead>
                    <tbody>
                        @foreach ($recentOrders as $order)
                            <tr>
                                <td data-label="Order"><a class="row-link" href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a></td>
                                <td data-label="Customer">{{ $order->recipient_name }}</td>
                                <td data-label="Total">{{ money($order->total) }}</td>
                                <td data-label="Payment"><span class="badge {{ $order->payment_status->badgeClass() }}">{{ $order->payment_status->label() }}</span></td>
                                <td data-label="Status"><span class="badge {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span></td>
                                <td data-label="Placed">{{ $order->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var el = document.getElementById('ordersChart');
            if (!el || !window.Chart) return;
            var data = @json($chart);
            new Chart(el, {
                data: {
                    labels: data.labels,
                    datasets: [
                        { type: 'bar', label: 'Revenue (₹)', data: data.revenue, backgroundColor: '#C9A45C', borderRadius: 6, yAxisID: 'y' },
                        { type: 'line', label: 'Orders', data: data.counts, borderColor: '#4A2922', backgroundColor: '#4A2922', tension: .3, yAxisID: 'y1' }
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: { callbacks: { label: function (c) {
                            return c.dataset.label + ': ' + (c.dataset.yAxisID === 'y' ? '₹' + Number(c.raw).toLocaleString('en-IN') : c.raw);
                        } } }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: function (v) { return '₹' + Number(v).toLocaleString('en-IN'); } }, grid: { color: '#eee6dd' } },
                        y1: { beginAtZero: true, position: 'right', grid: { display: false }, ticks: { precision: 0 } },
                        x: { grid: { display: false } }
                    }
                }
            });
        });
    </script>
@endpush
