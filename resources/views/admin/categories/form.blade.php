@extends('admin.layouts.app')

@section('title', $category->exists ? 'Edit category' : 'Add category')

@section('content')
    <form method="post" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" enctype="multipart/form-data" novalidate>
        @csrf
        @if ($category->exists) @method('PUT') @endif
        <div class="row g-4">
            <div class="col-xl-8">
                <div class="admin-card">
                    <div class="row g-3">
                        <div class="col-md-7"><x-admin.input name="name" label="Name" :value="$category->name" required maxlength="120" data-slug-source="#f_slug" /></div>
                        <div class="col-md-5"><x-admin.input name="slug" label="Web address" :value="$category->slug" prefix="/shop/" help="Leave empty to create it from the name." /></div>
                        <div class="col-12"><x-admin.textarea name="description" label="Description" :value="$category->description" help="Shown at the top of the category page." /></div>
                        <div class="col-md-4"><x-admin.input name="display_order" label="Display order" type="number" min="0" :value="$category->display_order" help="Lower numbers come first." /></div>
                        <div class="col-md-8 d-flex align-items-end"><x-admin.switch name="is_active" label="Show on the website" :checked="$category->is_active" help="Hiding a category also hides its products." /></div>
                        <div class="col-12"><x-admin.input name="meta_title" label="SEO title" :value="$category->meta_title" maxlength="70" /></div>
                        <div class="col-12"><x-admin.textarea name="meta_description" label="SEO description" :value="$category->meta_description" rows="2" maxlength="170" /></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="admin-card mb-3">
                    <x-admin.image name="image" label="Category image" :current="$category->image ? $category->image_url : null" help="Landscape, about 1200 × 900 px." />
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-choc btn-lg">{{ $category->exists ? 'Save category' : 'Create category' }}</button>
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </div>
        </div>
    </form>
@endsection
