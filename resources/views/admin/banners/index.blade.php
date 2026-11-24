@extends('admin.layouts.app')

@section('title', 'Homepage banners')

@section('content')
    <div class="admin-toolbar">
        <p class="text-secondary mb-0">The hero at the top of the homepage. With more than one active banner it becomes a slider, in display order.</p>
        <span class="admin-toolbar__spacer"></span>
        <a href="{{ route('admin.banners.create') }}" class="btn btn-choc"><i class="bi bi-plus-lg me-1"></i> Add banner</a>
    </div>
    <div class="table-card">
        @if ($banners->isEmpty())
            <x-admin.empty icon="bi-images" title="No banners" :action="route('admin.banners.create')" action-label="Add a banner">Without one, the homepage shows a default headline.</x-admin.empty>
        @else
            <div class="table-responsive">
                <table class="table table-stack">
                    <thead><tr><th></th><th>Headline</th><th>Button</th><th>Order</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($banners as $banner)
                            <tr>
                                <td data-label=""><img src="{{ $banner->image_url }}" class="thumb thumb--wide" alt=""></td>
                                <td data-label="Headline"><a class="row-link" href="{{ route('admin.banners.edit', $banner) }}">{{ $banner->title }}</a><span class="cell-sub">{{ \Illuminate\Support\Str::limit($banner->subtitle, 80) }}</span></td>
                                <td data-label="Button">{{ $banner->button_text ?: '—' }}<span class="cell-sub">{{ $banner->button_url }}</span></td>
                                <td data-label="Order">{{ $banner->sort_order }}</td>
                                <td data-label="Status"><x-admin.status-badge :active="$banner->is_active" /></td>
                                <td data-label="" class="text-end text-nowrap">
                                    <a href="{{ route('admin.banners.edit', $banner) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <x-admin.delete-button :action="route('admin.banners.destroy', $banner)" label="" confirm="Delete this banner?" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
