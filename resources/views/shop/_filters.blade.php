<form id="filterForm" class="filter-form" method="get"
      action="{{ $category ? route('shop.index', $category->slug) : route('shop.index') }}" data-filter-form>
    @if (! empty($filters['type']))
        <input type="hidden" name="type" value="{{ $filters['type'] }}">
    @endif

    <div class="filter-group">
        <label for="filterQ" class="filter-group__label">Search</label>
        <div class="input-icon">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" id="filterQ" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}"
                   maxlength="80" placeholder="Name or ingredient" data-debounce>
        </div>
    </div>

    <fieldset class="filter-group">
        <legend class="filter-group__label">Category</legend>
        <a href="{{ route('shop.index', array_filter(['type' => $filters['type'] ?? null])) }}" @class(['filter-link', 'is-active' => ! $category])>
            All chocolates
        </a>
        @foreach ($categories as $cat)
            <a href="{{ route('shop.index', array_filter(['category' => $cat->slug, 'type' => $filters['type'] ?? null])) }}"
               @class(['filter-link', 'is-active' => $category?->is($cat)])>
                {{ $cat->name }} <span>{{ $cat->products_count }}</span>
            </a>
        @endforeach
    </fieldset>

    <fieldset class="filter-group">
        <legend class="filter-group__label">Price (₹)</legend>
        <div class="price-inputs">
            <label class="visually-hidden" for="minPrice">Minimum price</label>
            <input type="number" id="minPrice" name="min_price" class="form-control" inputmode="numeric" min="0" step="10"
                   value="{{ $filters['min_price'] ?? '' }}" placeholder="{{ $priceRange['min'] }}" data-debounce>
            <span aria-hidden="true">to</span>
            <label class="visually-hidden" for="maxPrice">Maximum price</label>
            <input type="number" id="maxPrice" name="max_price" class="form-control" inputmode="numeric" min="0" step="10"
                   value="{{ $filters['max_price'] ?? '' }}" placeholder="{{ $priceRange['max'] }}" data-debounce>
        </div>
    </fieldset>

    <div class="filter-group">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="inStock" name="in_stock" value="1"
                   @checked(! empty($filters['in_stock'])) data-auto-submit>
            <label class="form-check-label" for="inStock">In stock only</label>
        </div>
    </div>

    <div class="filter-actions">
        <button type="submit" class="btn btn-choc w-100">Show results</button>
        <a href="{{ $category ? route('shop.index', $category->slug) : route('shop.index') }}" class="btn btn-link w-100">Clear filters</a>
    </div>
</form>
