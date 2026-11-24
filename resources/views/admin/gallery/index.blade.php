@extends('admin.layouts.app')

@section('title', 'Instagram gallery')

@section('content')
    <div class="admin-toolbar">
        <p class="text-secondary mb-0">The photo grid near the bottom of the homepage. Upload your best Instagram photos and link each to its post. The first 8 active photos are shown.</p>
        <span class="admin-toolbar__spacer"></span>
        <a href="{{ route('admin.gallery.create') }}" class="btn btn-choc"><i class="bi bi-plus-lg me-1"></i> Add photo</a>
    </div>
    @if ($items->isEmpty())
        <div class="admin-card"><x-admin.empty icon="bi-grid-3x3-gap" title="No photos yet" :action="route('admin.gallery.create')" action-label="Add a photo" /></div>
    @else
        <div class="image-manager">
            @foreach ($items as $item)
                <div @class(['image-tile', 'opacity-50' => ! $item->is_active])>
                    <a href="{{ route('admin.gallery.edit', $item) }}"><img src="{{ $item->image_url }}" alt="{{ $item->caption }}"></a>
                    <div class="small mt-1 text-truncate">{{ $item->caption ?: 'No caption' }}</div>
                    <div class="small text-secondary">Order {{ $item->sort_order }} · {{ $item->is_active ? 'Shown' : 'Hidden' }}</div>
                    <div class="d-flex gap-1 mt-2">
                        <a href="{{ route('admin.gallery.edit', $item) }}" class="btn btn-sm btn-outline-secondary flex-fill">Edit</a>
                        <x-admin.delete-button :action="route('admin.gallery.destroy', $item)" label="" confirm="Remove this photo?" />
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
