@extends('layouts.app')

@section('body_class', 'page-shop')

@section('content')
    <section class="shop-hero">
        <div class="container">
            <nav aria-label="Breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    @if ($category)
                        <li class="breadcrumb-item"><a href="{{ route('shop.index') }}">Shop</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $category->name }}</li>
                    @else
                        <li class="breadcrumb-item active" aria-current="page">Shop</li>
                    @endif
                </ol>
            </nav>
            <h1 class="shop-hero__title" data-shop-title>{{ $heading }}</h1>
            @if ($category?->description)
                <p class="shop-hero__intro">{{ $category->description }}</p>
            @endif
        </div>
    </section>

    {{-- Category chips: horizontal scroll on phones --}}
    <div class="container">
        <div class="chip-row" role="navigation" aria-label="Categories">
            <a href="{{ route('shop.index') }}" @class(['chip', 'is-active' => ! $category && empty($filters['type'])])>All</a>
            @foreach ($categories as $cat)
                <a href="{{ route('shop.index', $cat->slug) }}" @class(['chip', 'is-active' => $category?->is($cat)])>{{ $cat->name }}</a>
            @endforeach
            <a href="{{ route('shop.index', ['type' => 'gift']) }}" @class(['chip', 'is-active' => ($filters['type'] ?? null) === 'gift'])>Gift boxes</a>
        </div>
    </div>

    <div class="container shop-layout">
        {{-- Desktop sidebar / mobile off-canvas filters (same markup) --}}
        <aside class="offcanvas-lg offcanvas-start shop-filters" tabindex="-1" id="shopFilters" aria-labelledby="shopFiltersLabel">
            <div class="offcanvas-header">
                <h2 class="offcanvas-title h5" id="shopFiltersLabel">Filter</h2>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#shopFilters" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                @include('shop._filters')
            </div>
        </aside>

        <div class="shop-results">
            <div class="shop-toolbar">
                <p class="shop-toolbar__count" aria-live="polite"><span data-result-count>{{ $products->total() }}</span> {{ \Illuminate\Support\Str::plural('chocolate', $products->total()) }}</p>

                <button class="btn btn-outline-choc btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#shopFilters" aria-controls="shopFilters">
                    <i class="bi bi-sliders me-1" aria-hidden="true"></i> Filter
                </button>

                <label class="shop-toolbar__sort">
                    <span class="visually-hidden">Sort by</span>
                    <select class="form-select form-select-sm" name="sort" form="filterForm" data-auto-submit>
                        @foreach ($sorts as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['sort'] ?? 'featured') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div data-shop-results>
                @include('shop._results')
            </div>
        </div>
    </div>
@endsection
