@if ($products->isEmpty())
    <div class="empty-state">
        <i class="bi bi-search-heart" aria-hidden="true"></i>
        <h2>No chocolates match these filters</h2>
        <p>Try a wider price range or a different search, or ask us on WhatsApp — we may be able to make it for you.</p>
        <div class="d-flex flex-wrap gap-2 justify-content-center">
            <a href="{{ route('shop.index') }}" class="btn btn-choc">See all chocolates</a>
            <a href="{{ $whatsapp->generalLink() }}" class="btn btn-outline-choc" target="_blank" rel="noopener">Ask on WhatsApp</a>
        </div>
    </div>
@else
    <div class="product-grid product-grid--shop">
        @foreach ($products as $product)
            <x-product-card :product="$product" :eager="$loop->index < 2" />
        @endforeach
    </div>

    @if ($products->hasPages())
        <nav class="shop-pagination" aria-label="Pages">
            {{ $products->onEachSide(1)->links() }}
        </nav>
    @endif
@endif
