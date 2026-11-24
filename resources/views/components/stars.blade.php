@props(['rating'])
<span {{ $attributes->merge(['class' => 'stars']) }} role="img" aria-label="{{ $rating }} out of 5 stars">
    @for ($i = 1; $i <= 5; $i++)
        <i class="bi {{ $i <= $rating ? 'bi-star-fill' : 'bi-star' }}" aria-hidden="true"></i>
    @endfor
</span>
