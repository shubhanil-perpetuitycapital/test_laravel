@extends('admin.layouts.app')

@section('title', 'My account')

@section('content')
    <div class="row">
        <div class="col-lg-7 col-xl-6">
            <form method="post" action="{{ route('admin.profile.update') }}" class="admin-card">
                @csrf
                @method('PUT')

                <h2 class="h6 mb-3">Your details</h2>
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="120"
                           @class(['form-control', 'is-invalid' => $errors->has('name')])>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-4">
                    <label for="email" class="form-label">Sign-in email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="username"
                           @class(['form-control', 'is-invalid' => $errors->has('email')])>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <h2 class="h6 mb-1">Change password</h2>
                <p class="text-secondary small mb-3">Leave blank to keep your current password. At least 10 characters with letters and numbers.</p>
                <div class="mb-3">
                    <label for="current_password" class="form-label">Current password</label>
                    <input type="password" id="current_password" name="current_password" autocomplete="current-password"
                           @class(['form-control', 'is-invalid' => $errors->has('current_password')])>
                    @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <label for="password" class="form-label">New password</label>
                        <input type="password" id="password" name="password" autocomplete="new-password"
                               @class(['form-control', 'is-invalid' => $errors->has('password')])>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-6">
                        <label for="password_confirmation" class="form-label">Repeat new password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" class="form-control">
                    </div>
                </div>

                <p class="small text-secondary">Last sign-in: {{ $user->last_login_at?->timezone(config('app.timezone'))->format('j M Y, g:i a') ?? 'never' }}</p>
                <button type="submit" class="btn btn-choc">Save account</button>
            </form>
        </div>
    </div>
@endsection
