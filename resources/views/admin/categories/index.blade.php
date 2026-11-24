@extends('admin.layouts.app')

@section('title', 'Categories')

@section('content')
    <div class="admin-toolbar">
        <p class="text-secondary mb-0">Categories appear in the shop menu, homepage and filters in display order.</p>
        <span class="admin-toolbar__spacer"></span>
        <a href="{{ route('admin.categories.create') }}" class="btn btn-choc"><i class="bi bi-plus-lg me-1"></i> Add category</a>
    </div>
    <div class="table-card">
        @if ($categories->isEmpty())
            <x-admin.empty icon="bi-tags" title="No categories yet" :action="route('admin.categories.create')" action-label="Add a category" />
        @else
            <div class="table-responsive">
                <table class="table table-stack">
                    <thead><tr><th></th><th>Name</th><th>Products</th><th>Order</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td data-label=""><img src="{{ $category->image_url }}" alt="" class="thumb"></td>
                                <td data-label="Name"><a class="row-link" href="{{ route('admin.categories.edit', $category) }}">{{ $category->name }}</a><span class="cell-sub">/shop/{{ $category->slug }}</span></td>
                                <td data-label="Products"><a href="{{ route('admin.products.index', ['category' => $category->id]) }}">{{ $category->products_count }}</a></td>
                                <td data-label="Order">{{ $category->display_order }}</td>
                                <td data-label="Status"><x-admin.status-badge :active="$category->is_active" on="Visible" /></td>
                                <td data-label="" class="text-end text-nowrap">
                                    <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <x-admin.delete-button :action="route('admin.categories.destroy', $category)" label="" :confirm="'Delete the category '.$category->name.'?'" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
