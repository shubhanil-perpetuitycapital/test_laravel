@extends('layouts.app')

@section('body_class', 'page-home')

@section('content')
    {{-- 1. Hero: banners managed in Admin › Banners. Several active banners become a slider. --}}
    <section class="hero" aria-label="Featured">
        @if ($banners->count() > 1)
            <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="6500">
                <div class="carousel-indicators hero__dots">
                    @foreach ($banners as $banner)
                        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="{{ $loop->index }}" @class(['active' => $loop->first])
                                @if ($loop->first) aria-current="true" @endif aria-label="Slide {{ $loop->iteration }}"></button>
                    @endforeach
                </div>
                <div class="carousel-inner">
                    @foreach ($banners as $banner)
                        <div @class(['carousel-item', 'active' => $loop->first])>
                            @include('partials.hero-slide', ['banner' => $banner, 'first' => $loop->first])
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            @include('partials.hero-slide', ['banner' => $banners->first(), 'first' => true])
        @endif
        <svg class="hero__drip" viewBox="0 0 1440 90" preserveAspectRatio="none" aria-hidden="true">
            <path d="M0 0h1440v34c-38 0-44 40-74 40s-30-28-62-28-40 52-78 52-40-46-80-46-52 22-86 22-38-36-74-36-46 56-90 56-46-50-86-50-42 30-80 30-40-38-78-38-50 44-92 44-44-40-84-40-40 26-76 26-44-46-84-46-46 38-88 38-40-30-76-30-34 14-62 14V0z"/>
        </svg>
    </section>

    {{-- 2. Brand introduction --}}
    <section class="section intro">
        <div class="container">
            <div class="row align-items-center g-4 gy-lg-5">
                <div class="col-lg-5">
                    <h2 class="intro__title">Made in a small kitchen, not a factory</h2>
                </div>
                <div class="col-lg-7">
                    <p class="intro__text">{{ $company->about() }}</p>
                    @if ($company->establishedYear())
                        <p class="intro__meta">Making chocolate since {{ $company->establishedYear() }}</p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- 3. Shop by category --}}
    @if ($categories->isNotEmpty())
        <section class="section pt-0">
            <div class="container">
                <x-section-head title="Shop by category" :link="route('shop.index')" link-text="Browse all" />
                <div class="category-rail" role="list">
                    @foreach ($categories as $category)
                        <a href="{{ route('shop.index', $category->slug) }}" class="category-tile" role="listitem">
                            <img src="{{ $category->image_url }}" alt="" width="360" height="270" loading="lazy">
                            <span class="category-tile__name">{{ $category->name }}</span>
                            <span class="category-tile__count">{{ $category->products_count }} {{ \Illuminate\Support\Str::plural('item', $category->products_count) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- 4. Bestsellers --}}
    @if ($bestsellers->isNotEmpty())
        <section class="section pt-0">
            <div class="container">
                <x-section-head title="Bestselling chocolates" intro="What people order again and again."
                                :link="route('shop.index', ['type' => 'bestseller'])" />
                <div class="product-grid product-grid--rail">
                    @foreach ($bestsellers as $product)
                        <x-product-card :product="$product" :eager="$loop->index < 2" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- 5. Featured gift boxes --}}
    @if ($giftBoxes->isNotEmpty())
        <section class="section section--blush">
            <div class="container">
                <x-section-head title="Gift boxes" intro="Packed in a keepsake box with a handwritten card, if you'd like one."
                                :link="route('shop.index', ['type' => 'gift'])" link-text="All gift boxes" />
                <div class="product-grid">
                    @foreach ($giftBoxes as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- 6. Customised chocolates --}}
    <section class="section custom-cta">
        <div class="container">
            <div class="custom-cta__panel">
                <div class="custom-cta__copy">
                    <h2>Chocolates with your words on them</h2>
                    <p>Wedding favours, birthday hampers, corporate gifting, a name piped on every bar. Tell us the occasion, quantity and date, and we'll send you a quote.</p>
                    <ul class="custom-cta__list">
                        <li><i class="bi bi-check2" aria-hidden="true"></i> From 10 pieces upward</li>
                        <li><i class="bi bi-check2" aria-hidden="true"></i> Custom wrappers and printed tags</li>
                        <li><i class="bi bi-check2" aria-hidden="true"></i> Allow 5–7 days for most orders</li>
                    </ul>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('custom-orders.create') }}" class="btn btn-gold btn-lg">Request a quote</a>
                        <a href="{{ $whatsapp->customOrderLink() }}" class="btn btn-ghost-light btn-lg" target="_blank" rel="noopener">
                            <i class="bi bi-whatsapp me-1" aria-hidden="true"></i> Ask on WhatsApp
                        </a>
                    </div>
                </div>
                <img src="{{ asset('images/placeholders/custom-order.svg') }}" alt="" class="custom-cta__art" width="420" height="420" loading="lazy">
            </div>
        </div>
    </section>

    {{-- 7. Why choose us --}}
    <section class="section pt-0">
        <div class="container">
            <x-section-head title="What goes into every box" />
            <div class="promise-grid">
                <div class="promise">
                    <i class="bi bi-hand-index-thumb" aria-hidden="true"></i>
                    <h3>Made by hand</h3>
                    <p>Each piece is tempered, moulded and finished by hand, a few trays at a time.</p>
                </div>
                <div class="promise">
                    <i class="bi bi-flower1" aria-hidden="true"></i>
                    <h3>Real ingredients</h3>
                    <p>Couverture chocolate, fresh cream and fillings we make ourselves. No compound chocolate.</p>
                </div>
                <div class="promise">
                    <i class="bi bi-thermometer-snow" aria-hidden="true"></i>
                    <h3>Packed for the heat</h3>
                    <p>Insulated packing with ice gel for courier orders, so chocolates arrive the way they left.</p>
                </div>
                <div class="promise">
                    <i class="bi bi-truck" aria-hidden="true"></i>
                    <h3>Delivered fresh</h3>
                    <p>Same-day delivery in {{ $company->city() }} on orders placed before the daily cut-off.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- 8. Reviews --}}
    @if ($reviews->isNotEmpty())
        <section class="section section--dark">
            <div class="container">
                <x-section-head title="Kind words" :light="true" />
                <div class="review-rail">
                    @foreach ($reviews as $review)
                        <figure class="review-card">
                            <x-stars :rating="$review->rating" class="review-card__stars" />
                            <blockquote>{{ $review->comment }}</blockquote>
                            <figcaption>{{ $review->customer_name }}@if ($review->location)<span>, {{ $review->location }}</span>@endif</figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- 9. Instagram gallery --}}
    @if ($gallery->isNotEmpty())
        <section class="section">
            <div class="container">
                <x-section-head :title="$company->instagramHandle() ? 'Follow '.$company->instagramHandle() : 'From our kitchen'"
                                :link="$company->instagramUrl()" link-text="Open Instagram" />
                <div class="gallery-grid">
                    @foreach ($gallery as $item)
                        @php $href = $item->link_url ?: $company->instagramUrl(); @endphp
                        @if ($href)
                            <a href="{{ $href }}" class="gallery-grid__item" target="_blank" rel="noopener" aria-label="{{ $item->caption ?: 'View on Instagram' }}">
                                <img src="{{ $item->image_url }}" alt="{{ $item->caption }}" width="400" height="400" loading="lazy">
                            </a>
                        @else
                            <div class="gallery-grid__item">
                                <img src="{{ $item->image_url }}" alt="{{ $item->caption }}" width="400" height="400" loading="lazy">
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- 10. WhatsApp contact --}}
    <section class="section pt-0">
        <div class="container">
            <div class="wa-band">
                <i class="bi bi-whatsapp wa-band__icon" aria-hidden="true"></i>
                <div class="wa-band__copy">
                    <h2>Rather just message us?</h2>
                    <p>Send a photo, a question or your whole order on WhatsApp. We usually reply within an hour during business hours.</p>
                </div>
                <div class="wa-band__actions">
                    <a href="{{ $whatsapp->generalLink() }}" class="btn btn-wa btn-lg" target="_blank" rel="noopener">Chat on WhatsApp</a>
                    @if ($company->phone())
                        <a href="{{ $company->phoneHref() }}" class="btn btn-outline-choc btn-lg">Call {{ $company->phone() }}</a>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
