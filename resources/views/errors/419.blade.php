@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="empty-state empty-state--page">
            <i class="bi bi-hourglass-split" aria-hidden="true"></i>
            <h1>This page expired</h1>
            <p>The form was open for a long time, so we couldn't accept it. Go back, refresh the page and try again — your cart is still saved.</p>
            <a href="{{ url()->previous() }}" class="btn btn-choc">Go back</a>
        </div>
    </div>
@endsection
