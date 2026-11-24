@extends('admin.layouts.app')

@section('title', $review->exists ? 'Edit review' : 'Add review')

@section('content')
    <div class="row">
        <div class="col-xl-7">
            <form method="post" action="{{ $review->exists ? route('admin.reviews.update', $review) : route('admin.reviews.store') }}" class="admin-card" novalidate>
                @csrf
                @if ($review->exists) @method('PUT') @endif
                <div class="row g-3">
                    <div class="col-md-6"><x-admin.input name="customer_name" label="Customer name" :value="$review->customer_name" required /></div>
                    <div class="col-md-6"><x-admin.input name="location" label="Area / city" :value="$review->location" /></div>
                    <div class="col-md-6"><x-admin.select name="product_id" label="Product" :options="$products" :value="$review->product_id" placeholder="General (not about one product)" /></div>
                    <div class="col-md-6"><x-admin.select name="rating" label="Rating" :options="[5 => '★★★★★  5', 4 => '★★★★  4', 3 => '★★★  3', 2 => '★★  2', 1 => '★  1']" :value="$review->rating" required /></div>
                    <div class="col-12"><x-admin.textarea name="comment" label="Review" :value="$review->comment" rows="4" required /></div>
                    <div class="col-md-6"><x-admin.switch name="is_approved" label="Approved (visible on the product page)" :checked="$review->is_approved" /></div>
                    <div class="col-md-6"><x-admin.switch name="is_featured" label="Show on the homepage" :checked="$review->is_featured" /></div>
                </div>
                <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-choc">{{ $review->exists ? 'Save review' : 'Add review' }}</button>
                    <a href="{{ route('admin.reviews.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
