@extends('admin.layouts.app')

@section('title', $item->exists ? 'Edit photo' : 'Add photo')

@section('content')
    <div class="row">
        <div class="col-xl-7">
            <form method="post" action="{{ $item->exists ? route('admin.gallery.update', $item) : route('admin.gallery.store') }}" enctype="multipart/form-data" class="admin-card" novalidate>
                @csrf
                @if ($item->exists) @method('PUT') @endif
                <div class="row g-3">
                    <div class="col-md-6"><x-admin.image name="image" label="Photo" :current="$item->image ? $item->image_url : null" :removable="false" :required="! $item->exists" help="Square works best." /></div>
                    <div class="col-md-6">
                        <div class="mb-3"><x-admin.input name="caption" label="Caption / description" :value="$item->caption" help="Read aloud by screen readers." /></div>
                        <div class="mb-3"><x-admin.input name="link_url" label="Instagram post link" type="url" :value="$item->link_url" placeholder="https://www.instagram.com/p/…" help="Empty links to your Instagram profile." /></div>
                        <div class="mb-3"><x-admin.input name="sort_order" label="Display order" type="number" min="0" :value="$item->sort_order" /></div>
                        <x-admin.switch name="is_active" label="Show on the homepage" :checked="$item->is_active" />
                    </div>
                </div>
                <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-choc">{{ $item->exists ? 'Save photo' : 'Add photo' }}</button>
                    <a href="{{ route('admin.gallery.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
