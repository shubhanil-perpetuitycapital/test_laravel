@extends('admin.layouts.app')

@section('title', 'Coupons')

@section('content')
    <div class="admin-toolbar">
        <p class="text-secondary mb-0">Customers enter these codes in their cart. Each code is checked again when the order is placed.</p>
        <span class="admin-toolbar__spacer"></span>
        <a href="{{ route('admin.coupons.create') }}" class="btn btn-choc"><i class="bi bi-plus-lg me-1"></i> Add coupon</a>
    </div>
    <div class="table-card">
        @if ($coupons->isEmpty())
            <x-admin.empty icon="bi-ticket-perforated" title="No coupons yet" :action="route('admin.coupons.create')" action-label="Create a coupon" />
        @else
            <div class="table-responsive">
                <table class="table table-stack">
                    <thead><tr><th>Code</th><th>Discount</th><th>Used</th><th>Valid</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($coupons as $coupon)
                            @php $expired = $coupon->expires_at && $coupon->expires_at->isPast(); @endphp
                            <tr>
                                <td data-label="Code"><a class="row-link font-monospace" href="{{ route('admin.coupons.edit', $coupon) }}">{{ $coupon->code }}</a><span class="cell-sub">{{ $coupon->description }}</span></td>
                                <td data-label="Discount">{{ $coupon->summary }}@if ($coupon->max_discount)<span class="cell-sub">max {{ money($coupon->max_discount) }}</span>@endif</td>
                                <td data-label="Used">{{ $coupon->used_count }}{{ $coupon->usage_limit ? ' / '.$coupon->usage_limit : '' }}</td>
                                <td data-label="Valid">
                                    {{ $coupon->starts_at?->format('j M') ?? 'Now' }} – {{ $coupon->expires_at?->format('j M Y') ?? 'no end' }}
                                </td>
                                <td data-label="Status">
                                    @if ($expired)<span class="badge text-bg-secondary">Expired</span>@else<x-admin.status-badge :active="$coupon->is_active" off="Off" />@endif
                                </td>
                                <td data-label="" class="text-end text-nowrap">
                                    <a href="{{ route('admin.coupons.edit', $coupon) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <x-admin.delete-button :action="route('admin.coupons.destroy', $coupon)" label="" :confirm="'Delete coupon '.$coupon->code.'?'" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($coupons->hasPages())<div class="table-card__foot">{{ $coupons->links() }}</div>@endif
        @endif
    </div>
@endsection
