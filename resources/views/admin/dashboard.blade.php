@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php $pendingSetup = collect($checklist)->where('done', false); @endphp

    @if ($pendingSetup->isNotEmpty() && auth()->user()->isOwner())
        <div class="admin-card setup-card mb-4">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                    <h2 class="h5 mb-1">Finish setting up your store</h2>
                    <p class="text-secondary mb-0">{{ count($checklist) - $pendingSetup->count() }} of {{ count($checklist) }} done. Customers see these details on every page.</p>
                </div>
                <a href="{{ route('admin.company.edit') }}" class="btn btn-choc">Open company setup</a>
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
        <div class="stat"><span class="stat__label">Orders today</span><span class="stat__value">{{ number_format($stats['orders_today']) }}</span></div>
        <div class="stat"><span class="stat__label">Pending orders</span><span class="stat__value">{{ number_format($stats['orders_pending']) }}</span></div>
        <div class="stat"><span class="stat__label">All orders</span><span class="stat__value">{{ number_format($stats['orders_total']) }}</span></div>
        <div class="stat"><span class="stat__label">Customers</span><span class="stat__value">{{ number_format($stats['customers']) }}</span></div>
        <div class="stat"><span class="stat__label">Products</span><span class="stat__value">{{ number_format($stats['products']) }}</span></div>
        <div class="stat">
            <span class="stat__label">Out of stock</span>
            <span @class(['stat__value', 'text-danger' => $stats['products_out_of_stock'] > 0])>{{ number_format($stats['products_out_of_stock']) }}</span>
        </div>
    </div>

    <div class="admin-card mt-4">
        <h2 class="h6 mb-2">Your storefront</h2>
        <p class="text-secondary mb-3">{{ $stats['categories'] }} categories and {{ $stats['products'] }} products are live. Orders placed on the website will appear here once checkout is switched on.</p>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn btn-outline-secondary"><i class="bi bi-shop me-1"></i> Open storefront</a>
            <a href="{{ route('shop.index') }}" target="_blank" rel="noopener" class="btn btn-outline-secondary"><i class="bi bi-grid me-1"></i> Product listing</a>
        </div>
    </div>
@endsection
