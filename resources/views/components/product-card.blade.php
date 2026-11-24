@props(['product', 'eager' => false])
<article class="product-card">
    <a href="{{ $product->url }}" class="product-card__media" tabindex="-1" aria-hidden="true">
        <img src="{{ $product->image_url }}" alt="" width="480" height="480"
             loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async">
        @if (! $product->in_stock)
            <span class="product-card__flag product-card__flag--muted">Sold out</span>
        @elseif ($product->has_discount)
            <span class="product-card__flag">{{ $product->discount_percent }}% off</span>
        @elseif ($product->is_bestseller)
            <span class="product-card__flag product-card__flag--gold">Bestseller</span>
        @endif
    </a>
    <div class="product-card__body">
        @if ($product->relationLoaded('category') && $product->category)
            <p class="product-card__category">{{ $product->category->name }}</p>
        @endif
        <h3 class="product-card__title">
            <a href="{{ $product->url }}">{{ $product->name }}</a>
        </h3>
        @if ($product->weight)
            <p class="product-card__weight">{{ $product->weight }}</p>
        @endif
        <div class="product-card__foot">
            <x-price :product="$product" />
            @if ($product->in_stock)
                <form method="post" action="{{ route('cart.store') }}" class="product-card__add" data-add-to-cart>
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="product-card__go" aria-label="Add {{ $product->name }} to cart">
                        <i class="bi bi-bag-plus" aria-hidden="true"></i>
                    </button>
                </form>
            @else
                <a href="{{ $product->url }}" class="product-card__go" aria-label="View {{ $product->name }}">
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
            @endif
        </div>
    </div>
</article>
