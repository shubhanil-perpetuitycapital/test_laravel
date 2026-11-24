@extends('admin.layouts.app')

@section('title', 'Staff accounts')

@section('content')
    <div class="admin-toolbar">
        <p class="text-secondary mb-0">Staff can manage orders, enquiries, products and content. Only owners can change settings, coupons, delivery areas and accounts.</p>
        <span class="admin-toolbar__spacer"></span>
        <a href="{{ route('admin.users.create') }}" class="btn btn-choc"><i class="bi bi-plus-lg me-1"></i> Add account</a>
    </div>
    <div class="table-card">
        <div class="table-responsive">
            <table class="table table-stack">
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last sign-in</th><th></th></tr></thead>
                <tbody>
                    @foreach ($users as $u)
                        <tr>
                            <td data-label="Name"><a class="row-link" href="{{ route('admin.users.edit', $u) }}">{{ $u->name }}</a>@if ($u->is(auth()->user())) <span class="badge text-bg-light border">You</span>@endif</td>
                            <td data-label="Email">{{ $u->email }}</td>
                            <td data-label="Role">{{ $u->isOwner() ? 'Owner' : 'Staff' }}</td>
                            <td data-label="Status"><x-admin.status-badge :active="$u->is_active" off="Disabled" /></td>
                            <td data-label="Last sign-in">{{ $u->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                            <td data-label="" class="text-end text-nowrap">
                                <a href="{{ route('admin.users.edit', $u) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                @unless ($u->is(auth()->user()))
                                    <x-admin.delete-button :action="route('admin.users.destroy', $u)" label="" :confirm="'Delete the account for '.$u->name.'? They will no longer be able to sign in.'" />
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
