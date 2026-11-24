@extends('admin.layouts.app')

@section('title', $page->exists ? 'Edit page' : 'Add page')

@section('content')
    <form method="post" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}" novalidate>
        @csrf
        @if ($page->exists) @method('PUT') @endif
        <div class="row g-4">
            <div class="col-xl-8">
                <div class="admin-card">
                    <div class="mb-3"><x-admin.input name="title" label="Title" :value="$page->title" required data-slug-source="#f_slug" /></div>
                    <x-admin.textarea name="content" label="Content" :value="$page->content" rows="20" class="font-monospace small"
                        help="Basic HTML: <h2>, <h3>, <p>, <strong>, <em>, <a href>, <ul>/<ol>/<li>, <table>, and <details><summary>Question</summary><p>Answer</p></details> for FAQs. Scripts and styles are removed when you save." />
                </div>
            </div>
            <div class="col-xl-4">
                <div class="admin-card mb-3">
                    @if ($page->exists && $page->isSystem())
                        <p class="small text-secondary">Address: <strong>/page/{{ $page->slug }}</strong> (fixed — linked from the footer).</p>
                    @else
                        <div class="mb-3"><x-admin.input name="slug" label="Web address" :value="$page->slug" prefix="/page/" help="Leave empty to create it from the title." /></div>
                    @endif
                    <div class="mb-3"><x-admin.switch name="is_active" label="Published" :checked="$page->is_active" /></div>
                    <div class="mb-3"><x-admin.input name="meta_title" label="SEO title" :value="$page->meta_title" maxlength="70" /></div>
                    <x-admin.textarea name="meta_description" label="SEO description" :value="$page->meta_description" rows="2" maxlength="170" />
                </div>
                <div class="d-grid gap-2">
                    <button class="btn btn-choc btn-lg">{{ $page->exists ? 'Save page' : 'Create page' }}</button>
                    @if ($page->exists)<a href="{{ route('pages.show', $page) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary">View page</a>@endif
                    <a href="{{ route('admin.pages.index') }}" class="btn btn-light">Back to pages</a>
                </div>
            </div>
        </div>
    </form>
@endsection
