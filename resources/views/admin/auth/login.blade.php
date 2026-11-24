<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Sign in · {{ $company->name() }} admin</title>
    <link rel="icon" href="{{ $company->faviconUrl() }}">
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:opsz,wght@6..96,500&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('assets/css/admin.css') }}?v={{ @filemtime(public_path('assets/css/admin.css')) }}" rel="stylesheet">
</head>
<body class="admin-login">
    <main class="admin-login__card">
        <div class="admin-login__brand">
            @if ($company->logoUrl())
                <img src="{{ $company->logoUrl() }}" alt="{{ $company->name() }}">
            @else
                <span>{{ $company->name() }}</span>
            @endif
        </div>
        <h1 class="h4 mb-1">Sign in to the admin</h1>
        <p class="text-secondary mb-4">Orders, products and settings for your store.</p>

        @if (session('status'))
            <div class="alert alert-success py-2">{{ session('status') }}</div>
        @endif

        <form method="post" action="{{ route('admin.login.store') }}" novalidate>
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus
                       @class(['form-control form-control-lg', 'is-invalid' => $errors->has('email')])>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group has-validation">
                    <input type="password" id="password" name="password" autocomplete="current-password" required
                           @class(['form-control form-control-lg', 'is-invalid' => $errors->has('password')])>
                    <button type="button" class="btn btn-outline-secondary" data-toggle-password="#password" aria-label="Show password">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
                <label class="form-check-label" for="remember">Keep me signed in on this device</label>
            </div>
            <button type="submit" class="btn btn-choc btn-lg w-100">Sign in</button>
        </form>

        <a href="{{ route('home') }}" class="admin-login__back"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to website</a>
    </main>
    <script src="{{ asset('assets/js/admin.js') }}?v={{ @filemtime(public_path('assets/js/admin.js')) }}" defer></script>
</body>
</html>
