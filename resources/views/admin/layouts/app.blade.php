<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Dashboard') · {{ $company->name() }} admin</title>
    <link rel="icon" href="{{ $company->faviconUrl() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:opsz,wght@6..96,500&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('assets/css/admin.css') }}?v={{ @filemtime(public_path('assets/css/admin.css')) }}" rel="stylesheet">
    @stack('head')
</head>
<body class="admin-body">
@php
    $user = auth()->user();
    $nav = [
        ['Dashboard', 'bi-speedometer2', 'admin.dashboard', 'admin.dashboard', null, true],
        'Sales',
        ['Orders', 'bi-receipt', 'admin.orders.index', 'admin.orders.*', $adminBadges['orders'] ?: null, true],
        ['Custom enquiries', 'bi-stars', 'admin.enquiries.index', 'admin.enquiries.*', $adminBadges['enquiries'] ?: null, true],
        ['Customers', 'bi-people', 'admin.customers.index', 'admin.customers.*', null, true],
        'Catalogue',
        ['Products', 'bi-box2-heart', 'admin.products.index', 'admin.products.*', null, true],
        ['Categories', 'bi-tags', 'admin.categories.index', 'admin.categories.*', null, true],
        'Marketing',
        ['Coupons', 'bi-ticket-perforated', 'admin.coupons.index', 'admin.coupons.*', null, $user->isOwner()],
        ['Reviews', 'bi-chat-quote', 'admin.reviews.index', 'admin.reviews.*', $adminBadges['reviews'] ?: null, true],
        ['Homepage banners', 'bi-images', 'admin.banners.index', 'admin.banners.*', null, true],
        ['Instagram gallery', 'bi-grid-3x3-gap', 'admin.gallery.index', 'admin.gallery.*', null, true],
        ['Pages', 'bi-file-earmark-text', 'admin.pages.index', 'admin.pages.*', null, true],
        'Settings',
        ['Company setup', 'bi-building', ['admin.settings.edit', 'company'], 'admin.settings.*:company', null, $user->isOwner()],
        ['Store settings', 'bi-sliders', ['admin.settings.edit', 'store'], 'admin.settings.*:store', null, $user->isOwner()],
        ['Delivery areas', 'bi-geo-alt', 'admin.delivery-zones.index', 'admin.delivery-zones.*', null, $user->isOwner()],
        ['Staff accounts', 'bi-person-badge', 'admin.users.index', 'admin.users.*', null, $user->isOwner()],
        ['My account', 'bi-person-gear', 'admin.profile.edit', 'admin.profile.*', null, true],
    ];
@endphp
<div class="admin-shell">
    <aside class="offcanvas-lg offcanvas-start admin-sidebar" tabindex="-1" id="adminSidebar" aria-label="Admin navigation">
        <div class="admin-sidebar__brand">
            <a href="{{ route('admin.dashboard') }}" class="admin-sidebar__logo">
                @if ($company->logoLightUrl())
                    <img src="{{ $company->logoLightUrl() }}" alt="{{ $company->name() }}">
                @else
                    <span class="admin-sidebar__monogram">{{ $company->initials() }}</span>
                    <span class="admin-sidebar__name">{{ $company->name() }}</span>
                @endif
            </a>
            <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Close menu"></button>
        </div>

        <nav class="admin-nav">
            @foreach ($nav as $item)
                @if (is_string($item))
                    <p class="admin-nav__group">{{ $item }}</p>
                @elseif ($item[5])
                    @php
                        [$label, $icon, $route, $pattern, $badge] = $item;
                        $url = is_array($route) ? route($route[0], $route[1]) : route($route);
                        if (str_contains($pattern, ':')) {
                            [$p, $screen] = explode(':', $pattern);
                            $active = request()->routeIs($p) && request()->route('screen') === $screen;
                        } else {
                            $active = request()->routeIs($pattern);
                        }
                    @endphp
                    <a href="{{ $url }}" @class(['admin-nav__link', 'is-active' => $active]) @if ($active) aria-current="page" @endif>
                        <i class="bi {{ $icon }}" aria-hidden="true"></i> <span>{{ $label }}</span>
                        @if ($badge)<span class="admin-nav__badge">{{ $badge }}</span>@endif
                    </a>
                @endif
            @endforeach
        </nav>

        <div class="admin-sidebar__foot">
            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="admin-nav__link">
                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> View website
            </a>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <button class="btn admin-topbar__toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar" aria-label="Open menu">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>
            <h1 class="admin-topbar__title">@yield('title', 'Dashboard')</h1>

            <div class="dropdown ms-auto">
                <button class="btn admin-user dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="admin-user__avatar">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                    <span class="d-none d-sm-inline">{{ $user->name }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><span class="dropdown-item-text small text-secondary">{{ $user->isOwner() ? 'Owner' : 'Staff' }}</span></li>
                    <li><a class="dropdown-item" href="{{ route('admin.profile.edit') }}"><i class="bi bi-person me-2"></i>My account</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="post" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Sign out</button>
                        </form>
                    </li>
                </ul>
            </div>
        </header>

        <main class="admin-content">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="status">
                    <i class="bi bi-check-circle me-2" aria-hidden="true"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <strong>Please fix {{ $errors->count() === 1 ? 'this' : 'these '.$errors->count() }} {{ \Illuminate\Support\Str::plural('problem', $errors->count()) }}:</strong>
                    <ul class="mb-0 mt-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

{{-- Shared confirmation modal: add data-confirm="Message" to any form. --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h2 class="modal-title h5" id="confirmModalLabel">Are you sure?</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" data-confirm-message></div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" data-confirm-ok>Yes, continue</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="{{ asset('assets/js/admin.js') }}?v={{ @filemtime(public_path('assets/js/admin.js')) }}" defer></script>
@stack('scripts')
</body>
</html>
