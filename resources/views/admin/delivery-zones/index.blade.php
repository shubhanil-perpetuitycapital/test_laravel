@extends('admin.layouts.app')

@section('title', 'Delivery areas')

@section('content')
    <div class="admin-toolbar">
        <p class="text-secondary mb-0">Kolkata local delivery works only for PIN codes listed in an active area. Courier charges and store-wide switches are in
            <a href="{{ route('admin.settings.edit', ['store', 'tab' => 'delivery']) }}">Store settings → Delivery</a>.</p>
        <span class="admin-toolbar__spacer"></span>
        <a href="{{ route('admin.delivery-zones.create') }}" class="btn btn-choc"><i class="bi bi-plus-lg me-1"></i> Add area</a>
    </div>
    <div class="table-card">
        @if ($zones->isEmpty())
            <x-admin.empty icon="bi-geo-alt" title="No delivery areas" :action="route('admin.delivery-zones.create')" action-label="Add an area">Without an area, customers can only choose courier delivery.</x-admin.empty>
        @else
            <div class="table-responsive">
                <table class="table table-stack">
                    <thead><tr><th>Area</th><th>PIN codes</th><th>Charge</th><th>Free above</th><th>Same day</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($zones as $zone)
                            <tr>
                                <td data-label="Area"><a class="row-link" href="{{ route('admin.delivery-zones.edit', $zone) }}">{{ $zone->name }}</a><span class="cell-sub">{{ \Illuminate\Support\Str::limit($zone->description, 60) }}</span></td>
                                <td data-label="PIN codes">{{ $zone->pincodes_count }}</td>
                                <td data-label="Charge">{{ money($zone->delivery_charge) }}</td>
                                <td data-label="Free above">{{ $zone->free_delivery_threshold ? money($zone->free_delivery_threshold) : 'Store default' }}</td>
                                <td data-label="Same day">{{ $zone->same_day_available ? 'Before '.\Illuminate\Support\Carbon::parse($zone->same_day_cutoff)->format('g:i a') : 'No · '.$zone->lead_days.' day(s)' }}</td>
                                <td data-label="Status"><x-admin.status-badge :active="$zone->is_active" off="Paused" /></td>
                                <td data-label="" class="text-end text-nowrap">
                                    <a href="{{ route('admin.delivery-zones.edit', $zone) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <x-admin.delete-button :action="route('admin.delivery-zones.destroy', $zone)" label="" :confirm="'Delete '.$zone->name.' and its PIN codes? Customers there will no longer get local delivery.'" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
