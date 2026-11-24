@extends('admin.layouts.app')

@section('title', $banner->exists ? 'Edit banner' : 'Add banner')

@section('content')
    <form method="post" action="{{ $banner->exists ? route('admin.banners.update', $banner) : route('admin.banners.store') }}" enctype="multipart/form-data" novalidate>
        @csrf
        @if ($banner->exists) @method('PUT') @endif
        <div class="row g-4">
            <div class="col-xl-7">
                <div class="admin-card">
                    <div class="row g-3">
                        <div class="col-12"><x-admin.input name="title" label="Headline" :value="$banner->title" required maxlength="160" /></div>
                        <div class="col-12"><x-admin.textarea name="subtitle" label="Supporting text" :value="$banner->subtitle" rows="2" maxlength="300" /></div>
                        <div class="col-md-6"><x-admin.input name="button_text" label="Button text" :value="$banner->button_text" placeholder="Shop chocolates" /></div>
                        <div class="col-md-6"><x-admin.input name="button_url" label="Button link" :value="$banner->button_url" placeholder="/shop/gift-boxes" help="A page on this site starting with /, or a full https:// link." /></div>
                        <div class="col-md-4"><x-admin.input name="sort_order" label="Display order" type="number" min="0" :value="$banner->sort_order" /></div>
                        <div class="col-md-8 d-flex align-items-end"><x-admin.switch name="is_active" label="Show on the homepage" :checked="$banner->is_active" /></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="admin-card mb-3">
                    <x-admin.image name="image" label="Image" :current="$banner->image ? $banner->image_url : null" :removable="false" :required="! $banner->exists" help="Shown in a circle beside the headline. Square, at least 900 × 900 px." />
                    <hr>
                    <x-admin.image name="mobile_image" label="Phone image (optional)" :current="$banner->mobile_image_url" help="A different crop for small screens." />
                </div>
                <div class="d-grid gap-2">
                    <button class="btn btn-choc btn-lg">{{ $banner->exists ? 'Save banner' : 'Create banner' }}</button>
                    <a href="{{ route('admin.banners.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </div>
        </div>
    </form>
@endsection
