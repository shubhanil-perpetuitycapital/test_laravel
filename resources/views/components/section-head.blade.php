@props(['title', 'intro' => null, 'link' => null, 'linkText' => 'See all', 'light' => false])
<div @class(['section-head', 'section-head--light' => $light])>
    <div>
        <h2 class="section-head__title">{{ $title }}</h2>
        @if ($intro)
            <p class="section-head__intro">{{ $intro }}</p>
        @endif
    </div>
    @if ($link)
        <a href="{{ $link }}" class="section-head__link">{{ $linkText }} <i class="bi bi-arrow-right-short" aria-hidden="true"></i></a>
    @endif
</div>
