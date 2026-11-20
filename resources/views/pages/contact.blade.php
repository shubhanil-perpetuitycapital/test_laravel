@extends('layouts.app')

@section('content')
    <section class="shop-hero">
        <div class="container">
            <h1 class="shop-hero__title">Get in touch</h1>
            <p class="shop-hero__intro">Orders, gifting, bulk enquiries or a question about an ingredient — WhatsApp is the quickest way to reach us.</p>
        </div>
    </section>

    <div class="container">
        <div class="contact-grid">
            <a href="{{ $whatsapp->generalLink() }}" class="contact-card contact-card--wa" target="_blank" rel="noopener">
                <i class="bi bi-whatsapp" aria-hidden="true"></i>
                <h2>WhatsApp</h2>
                <p>Send a message, photo or voice note.</p>
                <span class="contact-card__action">Start chat</span>
            </a>

            @if ($company->phone())
                <a href="{{ $company->phoneHref() }}" class="contact-card">
                    <i class="bi bi-telephone" aria-hidden="true"></i>
                    <h2>Call</h2>
                    <p>{{ $company->phone() }}@if ($company->phoneAlt())<br>{{ $company->phoneAlt() }}@endif</p>
                    <span class="contact-card__action">Call now</span>
                </a>
            @endif

            @if ($company->email())
                <a href="mailto:{{ $company->email() }}" class="contact-card">
                    <i class="bi bi-envelope" aria-hidden="true"></i>
                    <h2>Email</h2>
                    <p>{{ $company->email() }}</p>
                    <span class="contact-card__action">Write to us</span>
                </a>
            @endif
        </div>

        <div class="row g-4 contact-details">
            <div class="col-md-6">
                <h2 class="h4">{{ $company->showFullAddress() ? 'Address' : 'Where we are' }}</h2>
                <address>
                    <strong>{{ $company->name() }}</strong><br>
                    @foreach ($company->publicAddressLines() as $line)
                        {{ $line }}<br>
                    @endforeach
                </address>
                @if ($company->mapsUrl())
                    <a href="{{ $company->mapsUrl() }}" class="btn btn-outline-choc" target="_blank" rel="noopener">
                        <i class="bi bi-geo-alt me-1" aria-hidden="true"></i> Open in Google Maps
                    </a>
                @endif
            </div>
            @if (count($company->businessHours()))
                <div class="col-md-6">
                    <h2 class="h4">Hours</h2>
                    <ul class="list-unstyled hours-list">
                        @foreach ($company->businessHours() as $line)
                            <li>{{ $line }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        @if (count($company->socialLinks()))
            <div class="contact-social">
                <h2 class="h4">Follow along</h2>
                <ul class="social-list social-list--dark">
                    @foreach ($company->socialLinks() as $link)
                        <li><a href="{{ $link['url'] }}" target="_blank" rel="noopener" aria-label="{{ $link['label'] }}"><i class="bi {{ $link['icon'] }}" aria-hidden="true"></i></a></li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endsection
