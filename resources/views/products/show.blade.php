@extends('layouts.app')

@section('body_class', 'page-product')

@section('content')
    <div class="container product-page">
        <nav aria-label="Breadcrumb" class="d-none d-md-block">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('shop.index', $product->category->slug) }}">{{ $product->category->name }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
            </ol>
        </nav>

        <div class="row g-4 gy-lg-5">
            {{-- Gallery --}}
            <div class="col-lg-6">
                @php $images = $product->images; @endphp
                <div class="pd-gallery">
                    <div id="pdCarousel" class="carousel slide pd-gallery__main" data-bs-touch="true" data-bs-ride="false">
                        <div class="carousel-inner">
                            @forelse ($images as $image)
                                <div @class(['carousel-item', 'active' => $loop->first])>
                                    <img src="{{ $image->url }}" alt="{{ $image->alt_text ?: $product->name }}" width="800" height="800"
                                         @if (! $loop->first) loading="lazy" @endif>
                                </div>
                            @empty
                                <div class="carousel-item active">
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" width="800" height="800">
                                </div>
                            @endforelse
                        </div>
                        @if ($images->count() > 1)
                            <button class="carousel-control-prev" type="button" data-bs-target="#pdCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous photo</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#pdCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Next photo</span>
                            </button>
                        @endif
                    </div>
                    @if ($images->count() > 1)
                        <div class="pd-gallery__thumbs" role="tablist" aria-label="Product photos">
                            @foreach ($images as $image)
                                <button type="button" data-bs-target="#pdCarousel" data-bs-slide-to="{{ $loop->index }}"
                                        @class(['active' => $loop->first]) aria-label="Photo {{ $loop->iteration }}"
                                        @if ($loop->first) aria-current="true" @endif>
                                    <img src="{{ $image->url }}" alt="" width="96" height="96" loading="lazy">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Summary --}}
            <div class="col-lg-6">
                <div class="pd-summary">
                    <a href="{{ route('shop.index', $product->category->slug) }}" class="pd-summary__category">{{ $product->category->name }}</a>
                    <h1 class="pd-summary__title">{{ $product->name }}</h1>

                    @if ($averageRating)
                        <a href="#reviews" class="pd-summary__rating">
                            <i class="bi bi-star-fill" aria-hidden="true"></i> {{ $averageRating }}
                            <span>({{ $reviews->count() }} {{ \Illuminate\Support\Str::plural('review', $reviews->count()) }})</span>
                        </a>
                    @endif

                    <x-price :product="$product" size="lg" />
                    @if ($product->has_discount)
                        <p class="pd-summary__save">You save {{ money((float) $product->price - $product->effective_price) }} ({{ $product->discount_percent }}%)</p>
                    @endif

                    @if ($product->short_description)
                        <p class="pd-summary__lead">{{ $product->short_description }}</p>
                    @endif

                    <dl class="pd-facts">
                        @if ($product->weight)
                            <div><dt>Weight / pack</dt><dd>{{ $product->weight }}</dd></div>
                        @endif
                        <div>
                            <dt>Availability</dt>
                            <dd>
                                @if (! $product->in_stock)
                                    <span class="stock stock--out">Sold out</span>
                                @elseif ($product->is_low_stock)
                                    <span class="stock stock--low">Only {{ $product->stock_quantity }} left</span>
                                @else
                                    <span class="stock stock--in">In stock</span>
                                @endif
                            </dd>
                        </div>
                        <div><dt>SKU</dt><dd>{{ $product->sku }}</dd></div>
                    </dl>

                    <div class="pd-buy" data-product-buy
                         data-wa-number="{{ $whatsapp->businessNumber() }}"
                         data-wa-template="{{ $whatsapp->productEnquiryMessage($product, '{qty}') }}"
                         data-max="{{ max(1, $product->stock_quantity) }}">
                        @if ($product->in_stock)
                            <div class="qty" role="group" aria-label="Quantity">
                                <button type="button" class="qty__btn" data-qty-step="-1" aria-label="Decrease quantity"><i class="bi bi-dash" aria-hidden="true"></i></button>
                                <label for="qty" class="visually-hidden">Quantity</label>
                                <input type="number" id="qty" class="qty__input" value="1" min="1" max="{{ $product->stock_quantity }}" inputmode="numeric" data-qty-input>
                                <button type="button" class="qty__btn" data-qty-step="1" aria-label="Increase quantity"><i class="bi bi-plus" aria-hidden="true"></i></button>
                            </div>
                            <a href="{{ $whatsapp->productEnquiryLink($product, 1) }}" class="btn btn-choc btn-lg pd-buy__main" target="_blank" rel="noopener" data-wa-order>
                                <i class="bi bi-whatsapp me-1" aria-hidden="true"></i> Order on WhatsApp
                            </a>
                        @else
                            <a href="{{ $whatsapp->link('Hi! Please let me know when "'.$product->name.'" is back in stock. '.$product->url) }}"
                               class="btn btn-outline-choc btn-lg pd-buy__main" target="_blank" rel="noopener">
                                <i class="bi bi-bell me-1" aria-hidden="true"></i> Ask when it's back
                            </a>
                        @endif
                    </div>
                    <p class="pd-buy__note">Opens WhatsApp with this chocolate and quantity filled in. You send the message; we confirm the total and delivery.</p>

                    <div class="accordion pd-details" id="pdDetails">
                        @if ($product->description)
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#pdDesc" aria-expanded="true" aria-controls="pdDesc">Description</button>
                                </h2>
                                <div id="pdDesc" class="accordion-collapse collapse show">
                                    <div class="accordion-body">{!! nl2br(e($product->description)) !!}</div>
                                </div>
                            </div>
                        @endif
                        @if ($product->ingredients)
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#pdIngr" aria-expanded="false" aria-controls="pdIngr">Ingredients</button>
                                </h2>
                                <div id="pdIngr" class="accordion-collapse collapse">
                                    <div class="accordion-body">{!! nl2br(e($product->ingredients)) !!}</div>
                                </div>
                            </div>
                        @endif
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#pdAllergen" aria-expanded="false" aria-controls="pdAllergen">Allergen information</button>
                            </h2>
                            <div id="pdAllergen" class="accordion-collapse collapse">
                                <div class="accordion-body">
                                    {!! nl2br(e($product->allergens ?: 'Made in a kitchen that handles milk, nuts, gluten and soy.')) !!}
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#pdStore" aria-expanded="false" aria-controls="pdStore">Storage &amp; delivery</button>
                            </h2>
                            <div id="pdStore" class="accordion-collapse collapse">
                                <div class="accordion-body">
                                    Keep in a cool, dry place between 16–20 °C, away from sunlight. Refrigerate in hot weather in an airtight box and bring to room temperature before eating.
                                    <a href="{{ route('pages.show', 'shipping-delivery-policy') }}">Read our delivery policy</a>.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reviews --}}
        @if ($reviews->isNotEmpty())
            <section class="section pb-0" id="reviews">
                <x-section-head title="Reviews" />
                <div class="review-list">
                    @foreach ($reviews as $review)
                        <figure class="review-card review-card--light">
                            <div class="review-card__stars" aria-label="{{ $review->rating }} out of 5 stars">
                                @for ($i = 1; $i <= 5; $i++)
                                    <i class="bi {{ $i <= $review->rating ? 'bi-star-fill' : 'bi-star' }}" aria-hidden="true"></i>
                                @endfor
                            </div>
                            <blockquote>{{ $review->comment }}</blockquote>
                            <figcaption>{{ $review->customer_name }} · {{ $review->created_at->format('M Y') }}</figcaption>
                        </figure>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Related --}}
        @if ($related->isNotEmpty())
            <section class="section">
                <x-section-head title="You may also like" :link="route('shop.index', $product->category->slug)" />
                <div class="product-grid product-grid--rail">
                    @foreach ($related as $item)
                        <x-product-card :product="$item" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    {{-- Sticky buy bar: phones only --}}
    <div class="buy-bar d-lg-none" data-buy-bar>
        <div class="buy-bar__price">
            <span>{{ $product->name }}</span>
            <strong>{{ money($product->effective_price) }}</strong>
        </div>
        @if ($product->in_stock)
            <a href="{{ $whatsapp->productEnquiryLink($product, 1) }}" class="btn btn-choc" target="_blank" rel="noopener" data-wa-order>
                <i class="bi bi-whatsapp me-1" aria-hidden="true"></i> Order
            </a>
        @else
            <span class="stock stock--out">Sold out</span>
        @endif
    </div>
@endsection
