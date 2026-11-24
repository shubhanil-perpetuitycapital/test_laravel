@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="empty-state empty-state--page">
            <i class="bi bi-stopwatch" aria-hidden="true"></i>
            <h1>Too many attempts</h1>
            <p>Please wait a minute and try again. If you need help now, message us on WhatsApp.</p>
            <a href="{{ $whatsapp->generalLink() }}" class="btn btn-wa" target="_blank" rel="noopener">Chat on WhatsApp</a>
        </div>
    </div>
@endsection
