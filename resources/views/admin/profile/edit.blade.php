@extends('admin.layouts.app')

@section('title', 'My account')

@section('content')
    <div class="row">
        <div class="col-lg-7 col-xl-6">
            <form method="post" action="{{ route('admin.profile.update') }}" class="admin-card">
                @csrf
                @method('PUT')

                <h2 class="form-section-title">Your details</h2>
                <div class="mb-3"><x-admin.input name="name" label="Name" :value="$user->name" required maxlength="120" /></div>
                <div class="mb-4"><x-admin.input name="email" label="Sign-in email" type="email" :value="$user->email" required autocomplete="username" /></div>

                <h2 class="form-section-title mb-1">Change password</h2>
                <p class="text-secondary small mb-3">Leave blank to keep your current password. At least 10 characters with letters and numbers.</p>
                <div class="mb-3"><x-admin.input name="current_password" label="Current password" type="password" value="" autocomplete="current-password" /></div>
                <div class="row g-3 mb-4">
                    <div class="col-sm-6"><x-admin.input name="password" label="New password" type="password" value="" autocomplete="new-password" /></div>
                    <div class="col-sm-6"><x-admin.input name="password_confirmation" label="Repeat new password" type="password" value="" autocomplete="new-password" /></div>
                </div>

                <p class="small text-secondary">Last sign-in: {{ $user->last_login_at?->format('j M Y, g:i a') ?? 'never' }}</p>
                <button type="submit" class="btn btn-choc">Save account</button>
            </form>
        </div>
    </div>
@endsection
