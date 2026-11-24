@extends('admin.layouts.app')

@section('title', 'Pages')

@section('content')
    <div class="admin-toolbar">
        <p class="text-secondary mb-0">About, FAQ and policy pages. The standard pages are linked from the footer and checkout.</p>
        <span class="admin-toolbar__spacer"></span>
        <a href="{{ route('admin.pages.create') }}" class="btn btn-choc"><i class="bi bi-plus-lg me-1"></i> Add page</a>
    </div>
    <div class="table-card">
        <div class="table-responsive">
            <table class="table table-stack">
                <thead><tr><th>Title</th><th>Address</th><th>Updated</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach ($pages as $page)
                        <tr>
                            <td data-label="Title"><a class="row-link" href="{{ route('admin.pages.edit', $page) }}">{{ $page->title }}</a>@if ($page->isSystem()) <span class="badge text-bg-light border">Standard</span>@endif</td>
                            <td data-label="Address"><a href="{{ route('pages.show', $page) }}" target="_blank" rel="noopener">/page/{{ $page->slug }}</a></td>
                            <td data-label="Updated">{{ $page->updated_at->format('j M Y') }}</td>
                            <td data-label="Status"><x-admin.status-badge :active="$page->is_active" on="Published" /></td>
                            <td data-label="" class="text-end text-nowrap">
                                <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                @unless ($page->isSystem())
                                    <x-admin.delete-button :action="route('admin.pages.destroy', $page)" label="" :confirm="'Delete the page '.$page->title.'?'" />
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
