@extends('layouts.app')

@section('content')
    <section class="shop-hero">
        <div class="container">
            <nav aria-label="Breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $page->title }}</li>
                </ol>
            </nav>
            <h1 class="shop-hero__title">{{ $page->title }}</h1>
            <p class="shop-hero__intro">Last updated {{ $page->updated_at->format('j F Y') }}</p>
        </div>
    </section>

    <div class="container">
        {{-- Content is written by the store owner in the admin panel (trusted HTML). --}}
        <article class="prose">
            {!! $page->content !!}
        </article>

        <aside class="prose-help">
            <p>Questions about this page?</p>
            <a href="{{ $whatsapp->generalLink() }}" class="btn btn-outline-choc" target="_blank" rel="noopener">
                <i class="bi bi-whatsapp me-1" aria-hidden="true"></i> Ask us on WhatsApp
            </a>
        </aside>
    </div>
@endsection
