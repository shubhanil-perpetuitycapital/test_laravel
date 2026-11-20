{{-- App-style bottom navigation, phones and small tablets only. --}}
<nav class="mobile-nav d-lg-none" aria-label="Quick navigation">
    <a href="{{ route('home') }}" @class(['mobile-nav__item', 'is-active' => request()->routeIs('home')])>
        <i class="bi {{ request()->routeIs('home') ? 'bi-house-heart-fill' : 'bi-house-heart' }}" aria-hidden="true"></i>
        <span>Home</span>
    </a>
    <a href="{{ route('shop.index') }}" @class(['mobile-nav__item', 'is-active' => request()->routeIs('shop.*', 'products.*') && request('type') !== 'gift'])>
        <i class="bi bi-grid" aria-hidden="true"></i>
        <span>Shop</span>
    </a>
    <a href="{{ route('shop.index', ['type' => 'gift']) }}" @class(['mobile-nav__item', 'is-active' => request('type') === 'gift'])>
        <i class="bi bi-gift" aria-hidden="true"></i>
        <span>Gifts</span>
    </a>
    <a href="{{ route('contact') }}" @class(['mobile-nav__item', 'is-active' => request()->routeIs('contact')])>
        <i class="bi bi-chat-heart" aria-hidden="true"></i>
        <span>Contact</span>
    </a>
    <a href="{{ $whatsapp->generalLink() }}" class="mobile-nav__item mobile-nav__item--wa" target="_blank" rel="noopener">
        <i class="bi bi-whatsapp" aria-hidden="true"></i>
        <span>WhatsApp</span>
    </a>
</nav>
