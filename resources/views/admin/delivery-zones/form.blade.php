@extends('admin.layouts.app')

@section('title', $zone->exists ? 'Edit delivery area' : 'Add delivery area')

@section('content')
    <form method="post" action="{{ $zone->exists ? route('admin.delivery-zones.update', $zone) : route('admin.delivery-zones.store') }}" novalidate>
        @csrf
        @if ($zone->exists) @method('PUT') @endif
        <div class="row g-4">
            <div class="col-xl-7">
                <div class="admin-card">
                    <div class="row g-3">
                        <div class="col-12"><x-admin.input name="name" label="Area name" :value="$zone->name" required placeholder="South Kolkata" /></div>
                        <div class="col-12"><x-admin.input name="description" label="Neighbourhoods (for your reference)" :value="$zone->description" /></div>
                        <div class="col-md-6"><x-admin.input name="delivery_charge" label="Delivery charge" type="number" step="0.01" min="0" :value="$zone->delivery_charge" prefix="₹" required /></div>
                        <div class="col-md-6"><x-admin.input name="free_delivery_threshold" label="Free delivery above" type="number" step="0.01" min="0" :value="$zone->free_delivery_threshold" prefix="₹" help="Empty uses the store-wide amount." /></div>
                        <div class="col-md-6"><x-admin.switch name="same_day_available" label="Same-day delivery in this area" :checked="$zone->same_day_available" /></div>
                        <div class="col-md-6"><x-admin.input name="same_day_cutoff" label="Same-day cut-off time" type="time" :value="$zone->same_day_cutoff ? substr($zone->same_day_cutoff, 0, 5) : null" help="Orders after this time get the next available day (IST)." /></div>
                        <div class="col-md-6"><x-admin.input name="lead_days" label="Days to deliver otherwise" type="number" min="1" max="30" :value="$zone->lead_days" required help="1 = next day." /></div>
                        <div class="col-md-6"><x-admin.input name="estimated_text" label="Note shown to customers" :value="$zone->estimated_text" placeholder="Delivered between 4 and 8 pm" /></div>
                        <div class="col-12"><x-admin.switch name="is_active" label="Deliver to this area" :checked="$zone->is_active" /></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="admin-card mb-3">
                    <x-admin.textarea name="pincodes" label="PIN codes in this area" :value="$pincodeText" rows="12" required class="font-monospace"
                        help="One per line, optionally followed by the area name: 700019 Ballygunge. You can also paste PINs separated by commas." />
                </div>
                <div class="d-grid gap-2">
                    <button class="btn btn-choc btn-lg">{{ $zone->exists ? 'Save area' : 'Create area' }}</button>
                    <a href="{{ route('admin.delivery-zones.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </div>
        </div>
    </form>
@endsection
