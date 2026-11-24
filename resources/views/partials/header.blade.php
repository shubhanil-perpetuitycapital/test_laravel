<header class="site-header" data-header>
    <div class="container site-header__inner">
        <x-brand-logo />

        <nav class="site-nav d-none d-lg-flex" aria-label="Main">
            <a href="{{ route('home') }}" @class(['site-nav__link', 'is-active' => request()->routeIs('home')])>Home</a>
            <div class="dropdown">
                <a href="{{ route('shop.index') }}" @class(['site-nav__link dropdown-toggle', 'is-active' => request()->routeIs('shop.*', 'products.*') && request('type') !== 'gift'])
                   data-bs-toggle="dropdown" aria-expanded="false" role="button">Shop</a>
                <ul class="dropdown-menu site-nav__menu">
                    <li><a class="dropdown-item" href="{{ route('shop.index') }}">All chocolates</a></li>
                    @foreach ($navCategories as $navCategory)
                        <li><a class="dropdown-item" href="{{ route('shop.index', $navCategory->slug) }}">{{ $navCategory->name }}</a></li>
                    @endforeach
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="{{ route('shop.index', ['type' => 'bestseller']) }}">Bestsellers</a></li>
                </ul>
            </div>
            <a href="{{ route('shop.index', ['type' => 'gift']) }}" @class(['site-nav__link', 'is-active' => request('type') === 'gift'])>Gift boxes</a>
            <a href="{{ route('custom-orders.create') }}" @class(['site-nav__link', 'is-active' => request()->routeIs('custom-orders.*')])>Custom orders</a>
            <a href="{{ route('pages.show', 'about-us') }}" @class(['site-nav__link', 'is-active' => request()->is('page/about-us')])>Our story</a>
            <a href="{{ route('contact') }}" @class(['site-nav__link', 'is-active' => request()->routeIs('contact')])>Contact</a>
        </nav>

        <div class="site-header__actions">
            <button class="icon-btn" type="button" data-bs-toggle="collapse" data-bs-target="#headerSearch"
                    aria-controls="headerSearch" aria-expanded="false" aria-label="Search chocolates">
                <i class="bi bi-search" aria-hidden="true"></i>
            </button>
            <a class="icon-btn d-none d-md-inline-flex" href="{{ route('track.index') }}" aria-label="Track your order" title="Track your order">
                <i class="bi bi-box-seam" aria-hidden="true"></i>
            </a>
            <a class="icon-btn icon-btn--cart" href="{{ route('cart.index') }}" aria-label="Cart, {{ $cartCount }} {{ \Illuminate\Support\Str::plural('item', $cartCount) }}">
                <i class="bi bi-bag" aria-hidden="true"></i>
                <span class="cart-badge" data-cart-count @if (! $cartCount) hidden @endif>{{ $cartCount }}</span>
            </a>
            <a class="btn btn-choc btn-sm d-none d-xl-inline-flex ms-1" href="{{ $whatsapp->generalLink() }}" target="_blank" rel="noopener">
                <i class="bi bi-whatsapp me-1" aria-hidden="true"></i> WhatsApp us
            </a>
        </div>
    </div>

    <div class="collapse header-search" id="headerSearch">
        <div class="container">
            <form action="{{ route('shop.index') }}" method="get" role="search" class="header-search__form">
                <i class="bi bi-search" aria-hidden="true"></i>
                <label for="headerSearchInput" class="visually-hidden">Search chocolates</label>
                <input id="headerSearchInput" type="search" name="q" value="{{ request('q') }}" maxlength="80"
                       placeholder="Search truffles, dark chocolate, gift box…" autocomplete="off">
                <button type="submit" class="btn btn-choc btn-sm">Search</button>
            </form>
        </div>
    </div>
</header>
