@extends('admin.layouts.app')

@section('title', 'Company setup')

@section('content')
    @php
        // Open the tab that contains the first validation error, if any.
        if ($errors->any()) {
            foreach ($sections as $sectionKey => $section) {
                if (collect(array_keys($section['fields']))->contains(fn ($k) => $errors->has($k))) {
                    $activeTab = $sectionKey;
                    break;
                }
            }
        }
    @endphp

    <form method="post" action="{{ route('admin.company.update') }}" enctype="multipart/form-data" class="company-form" data-unsaved-guard novalidate>
        @csrf
        @method('PUT')
        <input type="hidden" name="active_tab" value="{{ $activeTab }}" data-active-tab>

        <div class="row g-4">
            <div class="col-xl-8">
                <div class="admin-card p-0">
                    <ul class="nav nav-underline admin-tabs" role="tablist">
                        @foreach ($sections as $key => $section)
                            @php $hasError = collect(array_keys($section['fields']))->contains(fn ($k) => $errors->has($k)); @endphp
                            <li class="nav-item" role="presentation">
                                <button type="button" role="tab" id="tab-{{ $key }}" data-tab-key="{{ $key }}"
                                        data-bs-toggle="tab" data-bs-target="#pane-{{ $key }}" aria-controls="pane-{{ $key }}"
                                        aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}"
                                        @class(['nav-link', 'active' => $activeTab === $key, 'text-danger' => $hasError])>
                                    <i class="bi {{ $section['icon'] }}" aria-hidden="true"></i> {{ $section['title'] }}
                                    @if ($hasError)<span class="visually-hidden">(has errors)</span>@endif
                                </button>
                            </li>
                        @endforeach
                    </ul>

                    <div class="tab-content p-3 p-md-4">
                        @foreach ($sections as $key => $section)
                            <div @class(['tab-pane fade', 'show active' => $activeTab === $key]) id="pane-{{ $key }}" role="tabpanel" aria-labelledby="tab-{{ $key }}" tabindex="0">
                                <p class="text-secondary mb-4">{{ $section['intro'] }}</p>
                                <div class="row g-3">
                                    @foreach ($section['fields'] as $name => $field)
                                        <div class="{{ $field['col'] ?? 'col-12' }}">
                                            @include('admin.company._field', ['name' => $name, 'field' => $field, 'value' => $values[$name] ?? null])
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Live preview of how the details appear to customers --}}
            <div class="col-xl-4">
                <div class="admin-card preview-card">
                    <h2 class="h6 text-secondary mb-3">Preview: website footer</h2>
                    <div class="preview-footer">
                        <div class="preview-footer__logo" data-preview-logo>
                            @if ($company->logoLightUrl())
                                <img src="{{ $company->logoLightUrl() }}" alt="">
                            @else
                                <span data-preview="company_name">{{ $values['company_name'] }}</span>
                            @endif
                        </div>
                        <p class="preview-footer__tagline" data-preview="tagline">{{ $values['tagline'] }}</p>
                        <p class="mb-1"><i class="bi bi-telephone me-2"></i><span data-preview="phone">{{ $values['phone'] }}</span></p>
                        <p class="mb-1"><i class="bi bi-envelope me-2"></i><span data-preview="email">{{ $values['email'] }}</span></p>
                        <p class="mb-3"><i class="bi bi-geo-alt me-2"></i><span data-preview="city">{{ $values['city'] }}</span>, <span data-preview="state">{{ $values['state'] }}</span></p>
                        <p class="preview-footer__ids mb-0">
                            FSSAI <span data-preview="fssai_number">{{ $values['fssai_number'] ?: '— not set' }}</span>
                        </p>
                    </div>
                    <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary mt-3 w-100">
                        Open the live website
                    </a>
                </div>
            </div>
        </div>

        <div class="save-bar">
            <span class="save-bar__hint" data-unsaved-hint hidden><i class="bi bi-dot"></i> Unsaved changes</span>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-light">Cancel</a>
            <button type="submit" class="btn btn-choc px-4">Save company details</button>
        </div>
    </form>
@endsection
