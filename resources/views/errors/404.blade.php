@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="empty-state empty-state--page">
            <i class="bi bi-box2-heart" aria-hidden="true"></i>
            <h1>This page has been eaten</h1>
            <p>The link may be old, or the chocolate is no longer on the menu.</p>
            <div class="d-flex flex-wrap gap-2 justify-content-center">
                <a href="{{ route('shop.index') }}" class="btn btn-choc">Browse chocolates</a>
                <a href="{{ route('home') }}" class="btn btn-outline-choc">Go to homepage</a>
            </div>
        </div>
    </div>
@endsection
