@php
    $title = $banner?->title ?? 'Chocolate, tempered by hand in small batches';
    $subtitle = $banner?->subtitle ?? 'Bars, truffles and gift boxes made to order in '.$company->city().'. Same-day delivery across the city and courier to the rest of India.';
@endphp
<div class="container hero__grid">
    <div class="hero__copy">
        <p class="hero__kicker">{{ $company->tagline() }}</p>
        @if ($first)
            <h1 class="hero__title">{{ $title }}</h1>
        @else
            <h2 class="hero__title">{{ $title }}</h2>
        @endif
        <p class="hero__lead">{{ $subtitle }}</p>
        <div class="hero__actions">
            <a href="{{ $banner?->button_url ?: route('shop.index') }}" class="btn btn-gold btn-lg">{{ $banner?->button_text ?: 'Shop chocolates' }}</a>
            <a href="{{ route('custom-orders.create') }}" class="btn btn-ghost-light btn-lg">Plan a custom order</a>
        </div>
    </div>
    <div class="hero__art">
        <picture>
            @if ($banner?->mobile_image_url)
                <source media="(max-width: 767px)" srcset="{{ $banner->mobile_image_url }}">
            @endif
            <img src="{{ $banner?->image_url ?? asset('images/placeholders/hero.svg') }}" alt=""
                 width="720" height="720" @if ($first) fetchpriority="high" @else loading="lazy" @endif>
        </picture>
    </div>
</div>
