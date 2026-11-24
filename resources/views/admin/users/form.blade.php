@extends('admin.layouts.app')

@section('title', $user->exists ? 'Edit account' : 'Add account')

@section('content')
    <div class="row">
        <div class="col-lg-7 col-xl-6">
            <form method="post" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="admin-card">
                @csrf
                @if ($user->exists) @method('PUT') @endif
                <div class="mb-3"><x-admin.input name="name" label="Name" :value="$user->name" required /></div>
                <div class="mb-3"><x-admin.input name="email" label="Sign-in email" type="email" :value="$user->email" required /></div>
                <div class="mb-3"><x-admin.select name="role" label="Role" :options="['staff' => 'Staff', 'admin' => 'Owner']" :value="$user->role" required /></div>
                <div class="mb-3"><x-admin.switch name="is_active" label="Can sign in" :checked="$user->is_active" /></div>
                <div class="row g-3 mb-4">
                    <div class="col-sm-6"><x-admin.input name="password" label="{{ $user->exists ? 'New password' : 'Password' }}" type="password" value="" autocomplete="new-password" :required="! $user->exists"
                        help="At least 10 characters with letters and numbers." /></div>
                    <div class="col-sm-6"><x-admin.input name="password_confirmation" label="Repeat password" type="password" value="" autocomplete="new-password" /></div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-choc">{{ $user->exists ? 'Save account' : 'Create account' }}</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
