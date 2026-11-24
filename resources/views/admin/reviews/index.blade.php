@extends('admin.layouts.app')

@section('title', 'Reviews')

@section('content')
    <div class="admin-toolbar">
        @foreach (['' => 'All', 'pending' => 'Waiting for approval', 'approved' => 'Approved', 'featured' => 'On homepage'] as $v => $l)
            <a href="{{ route('admin.reviews.index', array_filter(['status' => $v])) }}" @class(['btn btn-sm', 'btn-choc' => ($status ?? '') === $v, 'btn-outline-secondary' => ($status ?? '') !== $v])>{{ $l }}</a>
        @endforeach
        <span class="admin-toolbar__spacer"></span>
        <a href="{{ route('admin.reviews.create') }}" class="btn btn-choc"><i class="bi bi-plus-lg me-1"></i> Add review</a>
    </div>
    <p class="small text-secondary">Customer reviews from product pages wait here for approval. You can also add feedback customers sent you on WhatsApp — only publish real reviews, with the customer's permission.</p>

    <div class="table-card">
        @if ($reviews->isEmpty())
            <x-admin.empty icon="bi-chat-quote" title="No reviews here" />
        @else
            <div class="table-responsive">
                <table class="table table-stack">
                    <thead><tr><th>Review</th><th>Product</th><th>Rating</th><th>Approved</th><th>Homepage</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($reviews as $review)
                            <tr>
                                <td data-label="Review" style="max-width:380px">
                                    <strong>{{ $review->customer_name }}</strong>@if ($review->location) <span class="text-secondary">· {{ $review->location }}</span>@endif
                                    <span class="cell-sub">{{ \Illuminate\Support\Str::limit($review->comment, 140) }}</span>
                                    <span class="cell-sub">{{ $review->created_at->format('j M Y') }}</span>
                                </td>
                                <td data-label="Product">{{ $review->product?->name ?? 'General' }}</td>
                                <td data-label="Rating" class="text-nowrap" style="color:#C9A45C">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</td>
                                <td data-label="Approved">
                                    <form method="post" action="{{ route('admin.reviews.toggle', $review) }}">@csrf @method('PATCH')
                                        <input type="hidden" name="field" value="is_approved">
                                        <button @class(['btn btn-sm', 'btn-success' => $review->is_approved, 'btn-outline-secondary' => ! $review->is_approved])>{{ $review->is_approved ? 'Approved' : 'Approve' }}</button>
                                    </form>
                                </td>
                                <td data-label="Homepage">
                                    <form method="post" action="{{ route('admin.reviews.toggle', $review) }}">@csrf @method('PATCH')
                                        <input type="hidden" name="field" value="is_featured">
                                        <button @class(['btn btn-sm', 'btn-choc' => $review->is_featured, 'btn-outline-secondary' => ! $review->is_featured]) aria-pressed="{{ $review->is_featured ? 'true' : 'false' }}">
                                            <i class="bi {{ $review->is_featured ? 'bi-star-fill' : 'bi-star' }}"></i> {{ $review->is_featured ? 'Shown' : 'Show' }}
                                        </button>
                                    </form>
                                </td>
                                <td data-label="" class="text-end text-nowrap">
                                    <a href="{{ route('admin.reviews.edit', $review) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <x-admin.delete-button :action="route('admin.reviews.destroy', $review)" label="" confirm="Delete this review?" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($reviews->hasPages())<div class="table-card__foot">{{ $reviews->links() }}</div>@endif
        @endif
    </div>
@endsection
