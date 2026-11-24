@extends('layouts.app')

@section('content')
    <section class="shop-hero">
        <div class="container">
            <h1 class="shop-hero__title">Track your order</h1>
            <p class="shop-hero__intro">Enter the order number from your confirmation and the mobile number you ordered with.</p>
        </div>
    </section>

    <div class="container pb-5">
        <form method="post" action="{{ route('track.lookup') }}" class="narrow-card" novalidate>
            @csrf
            <div class="mb-3">
                <label for="tOrder" class="form-label">Order number</label>
                <input id="tOrder" name="order_number" value="{{ old('order_number', $prefill) }}" required maxlength="30" placeholder="ORD241123-K7QX"
                       autocapitalize="characters" @class(['form-control form-control-lg', 'is-invalid' => $errors->has('order_number')])>
                <x-field-error name="order_number" />
            </div>
            <div class="mb-4">
                <label for="tMobile" class="form-label">Mobile number</label>
                <input id="tMobile" name="mobile" value="{{ old('mobile') }}" type="tel" inputmode="numeric" required maxlength="14"
                       @class(['form-control form-control-lg', 'is-invalid' => $errors->has('mobile')])>
                <x-field-error name="mobile" />
            </div>
            <button type="submit" class="btn btn-choc btn-lg w-100">Track order</button>
            <p class="small text-muted-choc mt-3 mb-0">Lost your order number? <a href="{{ $whatsapp->generalLink() }}" target="_blank" rel="noopener">Ask us on WhatsApp</a>.</p>
        </form>
    </div>
@endsection
