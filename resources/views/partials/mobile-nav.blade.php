{{-- App-style bottom navigation, phones and small tablets only. --}}
<nav class="mobile-nav d-lg-none" aria-label="Quick navigation">
    <a href="{{ route('home') }}" @class(['mobile-nav__item', 'is-active' => request()->routeIs('home')])>
        <i class="bi {{ request()->routeIs('home') ? 'bi-house-heart-fill' : 'bi-house-heart' }}" aria-hidden="true"></i>
        <span>Home</span>
    </a>
    <a href="{{ route('shop.index') }}" @class(['mobile-nav__item', 'is-active' => request()->routeIs('shop.*', 'products.*')])>
        <i class="bi bi-grid" aria-hidden="true"></i>
        <span>Shop</span>
    </a>
    <a href="{{ route('custom-orders.create') }}" @class(['mobile-nav__item', 'is-active' => request()->routeIs('custom-orders.*')])>
        <i class="bi bi-gift" aria-hidden="true"></i>
        <span>Custom</span>
    </a>
    <a href="{{ route('cart.index') }}" @class(['mobile-nav__item', 'is-active' => request()->routeIs('cart.*', 'checkout.*')])>
        <span class="mobile-nav__icon">
            <i class="bi {{ request()->routeIs('cart.*', 'checkout.*') ? 'bi-bag-fill' : 'bi-bag' }}" aria-hidden="true"></i>
            <span class="cart-badge" data-cart-count @if (! $cartCount) hidden @endif>{{ $cartCount }}</span>
        </span>
        <span>Cart</span>
    </a>
    <a href="{{ $whatsapp->generalLink() }}" class="mobile-nav__item mobile-nav__item--wa" target="_blank" rel="noopener">
        <i class="bi bi-whatsapp" aria-hidden="true"></i>
        <span>WhatsApp</span>
    </a>
</nav>
