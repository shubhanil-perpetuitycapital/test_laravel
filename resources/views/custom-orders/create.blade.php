@extends('layouts.app')

@section('body_class', 'page-custom')

@php $field = fn (string $name) => ['form-control', 'is-invalid' => $errors->has($name)]; @endphp

@section('content')
    <section class="custom-hero">
        <div class="container">
            <h1 class="custom-hero__title">Custom chocolates, made for your occasion</h1>
            <p class="custom-hero__lead">Personalised wrappers, names on bars, wedding favours and corporate hampers. Tell us what you have in mind and we'll reply with ideas and a quote, usually within a day.</p>
        </div>
    </section>

    <div class="container pb-5">
        <div class="row g-4 gy-lg-5">
            <div class="col-lg-8">
                <form method="post" action="{{ route('custom-orders.store') }}" enctype="multipart/form-data" class="form-card" novalidate>
                    @csrf
                    @if ($errors->any())
                        <div class="alert alert-danger">Please check the highlighted fields.</div>
                    @endif

                    <h2 class="h5 mb-3">About the order</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="cOcc" class="form-label">Occasion</label>
                            <select id="cOcc" name="occasion" @class(['form-select', 'is-invalid' => $errors->has('occasion')])>
                                <option value="">Choose…</option>
                                @foreach ($occasions as $o)
                                    <option @selected(old('occasion') === $o)>{{ $o }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="cType" class="form-label">Chocolate type</label>
                            <select id="cType" name="chocolate_type" @class(['form-select', 'is-invalid' => $errors->has('chocolate_type')])>
                                <option value="">Choose…</option>
                                @foreach ($types as $t)
                                    <option @selected(old('chocolate_type') === $t)>{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="cQty" class="form-label">Quantity</label>
                            <input id="cQty" name="quantity" value="{{ old('quantity') }}" required maxlength="100" placeholder="e.g. 50 bars, 20 boxes of 4" @class($field('quantity'))>
                            <x-field-error name="quantity" />
                        </div>
                        <div class="col-md-6">
                            <label for="cBudget" class="form-label">Budget <span class="text-muted-choc">(optional)</span></label>
                            <input id="cBudget" name="budget" value="{{ old('budget') }}" maxlength="100" placeholder="e.g. ₹150 per piece, ₹10,000 total" @class($field('budget'))>
                        </div>
                        <div class="col-md-6">
                            <label for="cDate" class="form-label">Needed by</label>
                            <input id="cDate" name="required_date" type="date" value="{{ old('required_date') }}" min="{{ now()->toDateString() }}" @class($field('required_date'))>
                            <x-field-error name="required_date" />
                        </div>
                        <div class="col-md-6">
                            <label for="cPin" class="form-label">Delivery PIN code</label>
                            <input id="cPin" name="delivery_pincode" value="{{ old('delivery_pincode') }}" inputmode="numeric" maxlength="6" @class($field('delivery_pincode'))>
                            <x-field-error name="delivery_pincode" />
                        </div>
                        <div class="col-12">
                            <label for="cMsg" class="form-label">Personalisation and details</label>
                            <textarea id="cMsg" name="message" rows="4" maxlength="2000" placeholder="Names, message on the wrapper, colours, flavours, packaging…" @class($field('message'))>{{ old('message') }}</textarea>
                            <x-field-error name="message" />
                        </div>
                        <div class="col-12">
                            <label for="cImg" class="form-label">Reference image <span class="text-muted-choc">(optional)</span></label>
                            <input id="cImg" name="reference_image" type="file" accept="image/jpeg,image/png,image/webp" @class($field('reference_image'))>
                            <div class="form-text">A photo, invitation or logo you'd like us to match. JPG, PNG or WebP up to 4 MB.</div>
                            <x-field-error name="reference_image" />
                        </div>
                    </div>

                    <h2 class="h5 mt-4 mb-3">Your contact details</h2>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="cName" class="form-label">Name</label>
                            <input id="cName" name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name" @class($field('name'))>
                            <x-field-error name="name" />
                        </div>
                        <div class="col-md-4">
                            <label for="cMobile" class="form-label">Mobile (WhatsApp)</label>
                            <input id="cMobile" name="mobile" type="tel" value="{{ old('mobile') }}" required maxlength="14" inputmode="numeric" autocomplete="tel-national" @class($field('mobile'))>
                            <x-field-error name="mobile" />
                        </div>
                        <div class="col-md-4">
                            <label for="cEmail" class="form-label">Email <span class="text-muted-choc">(optional)</span></label>
                            <input id="cEmail" name="email" type="email" value="{{ old('email') }}" maxlength="150" autocomplete="email" @class($field('email'))>
                            <x-field-error name="email" />
                        </div>
                    </div>
                    <input type="text" name="website" class="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">

                    <button type="submit" class="btn btn-choc btn-lg mt-4">Send enquiry</button>
                </form>
            </div>

            <div class="col-lg-4">
                <aside class="custom-aside">
                    <h2 class="h5">How it works</h2>
                    <ol class="steps-list">
                        <li><strong>Tell us the idea.</strong> Occasion, quantity, date and any designs.</li>
                        <li><strong>Get a quote.</strong> We reply on WhatsApp with options and a price.</li>
                        <li><strong>Confirm and pay.</strong> We reserve your date once the order is confirmed.</li>
                        <li><strong>We make it.</strong> Delivered fresh, on the date you need.</li>
                    </ol>
                    <p class="small text-muted-choc">Most custom orders need 5–7 days. Large orders need more notice.</p>
                    <a href="{{ $whatsapp->customOrderLink() }}" class="btn btn-wa w-100" target="_blank" rel="noopener">
                        <i class="bi bi-whatsapp me-1" aria-hidden="true"></i> Prefer to chat? WhatsApp us
                    </a>
                </aside>
            </div>
        </div>
    </div>
@endsection
