@extends('admin.layouts.app')

@section('title', $coupon->exists ? 'Edit coupon' : 'Add coupon')

@section('content')
    <div class="row">
        <div class="col-xl-8">
            <form method="post" action="{{ $coupon->exists ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}" class="admin-card" novalidate>
                @csrf
                @if ($coupon->exists) @method('PUT') @endif
                <div class="row g-3">
                    <div class="col-md-6"><x-admin.input name="code" label="Code" :value="$coupon->code" required maxlength="40" class="text-uppercase" placeholder="DIWALI10" /></div>
                    <div class="col-md-6"><x-admin.input name="description" label="Internal description" :value="$coupon->description" placeholder="Diwali 2026 Instagram offer" /></div>
                    <div class="col-md-4"><x-admin.select name="type" label="Type" :options="['percent' => 'Percentage off', 'fixed' => 'Fixed amount off']" :value="$coupon->type" required /></div>
                    <div class="col-md-4"><x-admin.input name="value" label="Discount value" type="number" step="0.01" min="1" :value="$coupon->value" required help="e.g. 10 for 10% or ₹10." /></div>
                    <div class="col-md-4"><x-admin.input name="max_discount" label="Maximum discount" type="number" step="0.01" min="1" :value="$coupon->max_discount" prefix="₹" help="Caps percentage coupons." /></div>
                    <div class="col-md-4"><x-admin.input name="min_order_amount" label="Minimum order" type="number" step="0.01" min="0" :value="$coupon->min_order_amount" prefix="₹" /></div>
                    <div class="col-md-4"><x-admin.input name="usage_limit" label="Total uses allowed" type="number" min="1" :value="$coupon->usage_limit" help="Empty for unlimited." /></div>
                    <div class="col-md-4 d-flex align-items-end"><x-admin.switch name="is_active" label="Active" :checked="$coupon->is_active" /></div>
                    <div class="col-md-6"><x-admin.input name="starts_at" label="Starts" type="datetime-local" :value="$coupon->starts_at?->format('Y-m-d\TH:i')" /></div>
                    <div class="col-md-6"><x-admin.input name="expires_at" label="Ends" type="datetime-local" :value="$coupon->expires_at?->format('Y-m-d\TH:i')" /></div>
                </div>
                @if ($coupon->exists)<p class="small text-secondary mt-3 mb-0">Used {{ $coupon->used_count }} {{ \Illuminate\Support\Str::plural('time', $coupon->used_count) }}.</p>@endif
                <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-choc">{{ $coupon->exists ? 'Save coupon' : 'Create coupon' }}</button>
                    <a href="{{ route('admin.coupons.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
