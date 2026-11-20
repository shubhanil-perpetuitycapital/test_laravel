@php
    $title = $seo?->title ?: $company->metaTitle();
    $description = $seo?->description ?: $company->metaDescription();
    $image = $seo?->image ?: $company->ogImageUrl();
    $canonical = $seo?->canonical ?: url()->current();
@endphp
<title>{{ $title }}</title>
<meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($description), 170) }}">
<link rel="canonical" href="{{ $canonical }}">
@if ($seo?->noindex || ! app()->environment('production'))
    <meta name="robots" content="noindex, follow">
@endif
@if ($company->googleSiteVerification())
    <meta name="google-site-verification" content="{{ $company->googleSiteVerification() }}">
@endif

<meta property="og:site_name" content="{{ $company->name() }}">
<meta property="og:type" content="{{ $seo?->type === 'product' ? 'product' : 'website' }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags($description), 200) }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $image }}">
<meta property="og:locale" content="en_IN">
<meta name="twitter:card" content="summary_large_image">

@foreach ($seo?->schema ?? [] as $block)
    <script type="application/ld+json">{!! json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endforeach
