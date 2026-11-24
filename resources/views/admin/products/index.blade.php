@extends('admin.layouts.app')

@section('title', 'Products')

@section('content')
    <form method="get" class="filter-bar">
        <div>
            <label class="form-label" for="pq">Search</label>
            <input id="pq" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Name or SKU">
        </div>
        <div>
            <label class="form-label" for="pc">Category</label>
            <select id="pc" name="category" class="form-select" data-auto-submit>
                <option value="">All categories</option>
                @foreach ($categories as $id => $name)
                    <option value="{{ $id }}" @selected((int) ($filters['category'] ?? 0) === $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label" for="ps">Show</label>
            <select id="ps" name="status" class="form-select" data-auto-submit>
                <option value="">All products</option>
                @foreach (['active' => 'Visible on site', 'hidden' => 'Hidden', 'low_stock' => 'Low stock', 'out_of_stock' => 'Out of stock'] as $v => $l)
                    <option value="{{ $v }}" @selected(($filters['status'] ?? '') === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary flex-fill">Filter</button>
            <a href="{{ route('admin.products.create') }}" class="btn btn-choc flex-fill"><i class="bi bi-plus-lg"></i> Add</a>
        </div>
    </form>

    <div class="table-card">
        @if ($products->isEmpty())
            <x-admin.empty icon="bi-box2-heart" title="No products found" :action="route('admin.products.create')" action-label="Add a product">
                Try clearing the filters, or add your first product.
            </x-admin.empty>
        @else
            <div class="table-responsive">
                <table class="table table-stack">
                    <thead><tr><th></th><th>Product</th><th class="hide-sm">Category</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($products as $product)
                            <tr>
                                <td data-label=""><img src="{{ $product->image_url }}" alt="" class="thumb" loading="lazy"></td>
                                <td data-label="Product">
                                    <a class="row-link" href="{{ route('admin.products.edit', $product) }}">{{ $product->name }}</a>
                                    <span class="cell-sub">{{ $product->sku }}@if ($product->weight) · {{ $product->weight }}@endif</span>
                                </td>
                                <td data-label="Category" class="hide-sm">{{ $product->category?->name }}</td>
                                <td data-label="Price">
                                    {{ money($product->effective_price) }}
                                    @if ($product->has_discount)<span class="cell-sub text-decoration-line-through">{{ money($product->price) }}</span>@endif
                                </td>
                                <td data-label="Stock">
                                    <span @class(['fw-semibold', 'text-danger' => ! $product->stock_quantity, 'text-warning' => $product->is_low_stock])>{{ $product->stock_quantity }}</span>
                                </td>
                                <td data-label="Status">
                                    <x-admin.status-badge :active="$product->is_active" on="Visible" />
                                    @if ($product->is_featured)<span class="badge text-bg-light border">Featured</span>@endif
                                </td>
                                <td data-label="" class="text-end text-nowrap">
                                    <a href="{{ $product->url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary" aria-label="View on site"><i class="bi bi-box-arrow-up-right"></i></a>
                                    <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <x-admin.delete-button :action="route('admin.products.destroy', $product)" label="" :confirm="'Delete '.$product->name.'? It disappears from the website. Past orders are not affected.'" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($products->hasPages())<div class="table-card__foot">{{ $products->links() }}</div>@endif
        @endif
    </div>
@endsection
