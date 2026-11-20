@props(['product', 'size' => 'md'])
<p {{ $attributes->merge(['class' => 'price price--'.$size]) }}>
    <span class="price__now">{{ money($product->effective_price) }}</span>
    @if ($product->has_discount)
        <span class="price__was"><span class="visually-hidden">Was </span>{{ money($product->price) }}</span>
    @endif
</p>
