@extends('layouts.app')

@section('content')
    <div class="container confirm-page">
        <div class="confirm-hero">
            <i class="bi bi-envelope-heart" aria-hidden="true"></i>
            <h1>Enquiry received</h1>
            <p>Thank you, {{ \Illuminate\Support\Str::before($enquiry->name, ' ') }}. Your reference is <strong>{{ $enquiry->enquiry_number }}</strong>. We'll reply on WhatsApp at +91 {{ $enquiry->mobile }}, usually within a day.</p>
        </div>
        <div class="narrow-card text-center">
            <p>Want a faster reply? Send us the reference on WhatsApp now.</p>
            <a href="{{ $whatsapp->enquiryToBusinessLink($enquiry) }}" class="btn btn-wa btn-lg" target="_blank" rel="noopener">
                <i class="bi bi-whatsapp me-1" aria-hidden="true"></i> Open WhatsApp
            </a>
            <p class="mt-3 mb-0"><a href="{{ route('shop.index') }}">Browse ready-made chocolates</a></p>
        </div>
    </div>
@endsection
