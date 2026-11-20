@props(['variant' => 'dark', 'class' => ''])
{{-- variant "dark" = logo for light backgrounds, "light" = logo for dark backgrounds --}}
@php
    $company = app(\App\Services\CompanyProfile::class);
    $src = $variant === 'light' ? $company->logoLightUrl() : $company->logoUrl();
@endphp
<a href="{{ route('home') }}" class="brand-mark brand-mark--{{ $variant }} {{ $class }}" aria-label="{{ $company->name() }} — home">
    @if ($src)
        <img src="{{ $src }}" alt="{{ $company->name() }}" class="brand-mark__img" width="180" height="54">
    @else
        <span class="brand-mark__text">{{ $company->name() }}</span>
    @endif
</a>
