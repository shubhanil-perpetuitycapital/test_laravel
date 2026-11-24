@extends('admin.layouts.app')

@section('title', $product->exists ? 'Edit product' : 'Add product')

@section('content')
    <form method="post" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" enctype="multipart/form-data" novalidate>
        @csrf
        @if ($product->exists) @method('PUT') @endif

        <div class="row g-4">
            <div class="col-xl-8">
                <div class="admin-card mb-4">
                    <h2 class="form-section-title">Basics</h2>
                    <div class="row g-3">
                        <div class="col-md-8"><x-admin.input name="name" label="Product name" :value="$product->name" required maxlength="180" data-slug-source="#f_slug" /></div>
                        <div class="col-md-4"><x-admin.input name="sku" label="SKU" :value="$product->sku" required maxlength="60" help="Your stock code, e.g. BAR-DK70." /></div>
                        <div class="col-md-6"><x-admin.select name="category_id" label="Category" :options="$categories" :value="$product->category_id" placeholder="Choose…" required /></div>
                        <div class="col-md-6"><x-admin.input name="slug" label="Web address" :value="$product->slug" prefix="/product/" help="Leave empty to create it from the name." /></div>
                        <div class="col-12"><x-admin.input name="short_description" label="Short description" :value="$product->short_description" maxlength="300" help="One or two sentences shown under the name." /></div>
                        <div class="col-12"><x-admin.textarea name="description" label="Full description" :value="$product->description" rows="5" /></div>
                        <div class="col-md-6"><x-admin.textarea name="ingredients" label="Ingredients" :value="$product->ingredients" /></div>
                        <div class="col-md-6"><x-admin.textarea name="allergens" label="Allergen information" :value="$product->allergens" help="e.g. Contains milk and soy. May contain nuts." /></div>
                    </div>
                </div>

                <div class="admin-card mb-4">
                    <h2 class="form-section-title">Photos</h2>
                    @if ($product->exists && $product->images->isNotEmpty())
                        <div class="image-manager mb-3">
                            @foreach ($product->images as $image)
                                <div class="image-tile">
                                    <img src="{{ $image->url }}" alt="">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="primary_image" value="{{ $image->id }}" id="prim{{ $image->id }}" @checked($image->is_primary)>
                                        <label class="form-check-label" for="prim{{ $image->id }}">Main photo</label>
                                    </div>
                                    <label class="visually-hidden" for="alt{{ $image->id }}">Description</label>
                                    <input id="alt{{ $image->id }}" name="existing[{{ $image->id }}][alt_text]" value="{{ $image->alt_text }}" class="form-control form-control-sm mt-1" placeholder="Description">
                                    <div class="d-flex align-items-center gap-1 mt-1">
                                        <label class="small text-secondary" for="ord{{ $image->id }}">Order</label>
                                        <input id="ord{{ $image->id }}" type="number" min="0" name="existing[{{ $image->id }}][sort_order]" value="{{ $image->sort_order }}" class="form-control form-control-sm">
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" name="existing[{{ $image->id }}][remove]" id="rm{{ $image->id }}" data-remove-image>
                                        <label class="form-check-label text-danger" for="rm{{ $image->id }}">Remove</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <label for="f_images" class="form-label">Add photos</label>
                    <input type="file" id="f_images" name="images[]" multiple accept="image/jpeg,image/png,image/webp"
                           @class(['form-control', 'is-invalid' => $errors->has('images') || $errors->has('images.*')]) data-multi-upload="#uploadPreviews">
                    <div class="form-text">Up to 10 at a time. JPG, PNG or WebP, max 4 MB each. Square photos look best; large photos are resized automatically.</div>
                    <div class="upload-previews" id="uploadPreviews"></div>
                </div>

                <div class="admin-card">
                    <h2 class="form-section-title">Search engines</h2>
                    <div class="row g-3">
                        <div class="col-12"><x-admin.input name="meta_title" label="SEO title" :value="$product->meta_title" maxlength="70" help="Leave empty to use the product name." /></div>
                        <div class="col-12"><x-admin.textarea name="meta_description" label="SEO description" :value="$product->meta_description" rows="2" maxlength="170" help="Leave empty to use the short description." /></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="admin-card mb-4">
                    <h2 class="form-section-title">Price & stock</h2>
                    <div class="mb-3"><x-admin.input name="price" label="Regular price" type="number" step="0.01" min="1" :value="$product->price" prefix="₹" required /></div>
                    <div class="mb-3"><x-admin.input name="discount_price" label="Sale price" type="number" step="0.01" min="0" :value="$product->discount_price" prefix="₹" help="Optional. Shown with the regular price crossed out." /></div>
                    <div class="mb-3"><x-admin.input name="weight" label="Weight / pack size" :value="$product->weight" placeholder="80 g, Box of 9" /></div>
                    <div><x-admin.input name="stock_quantity" label="Stock quantity" type="number" min="0" :value="$product->stock_quantity" required help="Goes down automatically when orders are placed, and back up if an order is cancelled." /></div>
                </div>

                <div class="admin-card mb-4">
                    <h2 class="form-section-title">Visibility</h2>
                    <x-admin.switch name="is_active" label="Show on the website" :checked="$product->is_active" />
                    <x-admin.switch name="is_featured" label="Featured (shown first in the shop)" :checked="$product->is_featured" />
                    <x-admin.switch name="is_bestseller" label="Bestseller (homepage)" :checked="$product->is_bestseller" />
                    <x-admin.switch name="is_gift_box" label="Gift box (homepage and gift filter)" :checked="$product->is_gift_box" />
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-choc btn-lg">{{ $product->exists ? 'Save product' : 'Create product' }}</button>
                    @if ($product->exists)
                        <a href="{{ $product->url }}" target="_blank" rel="noopener" class="btn btn-outline-secondary">View on website</a>
                    @endif
                    <a href="{{ route('admin.products.index') }}" class="btn btn-light">Back to products</a>
                </div>
            </div>
        </div>
    </form>
@endsection
