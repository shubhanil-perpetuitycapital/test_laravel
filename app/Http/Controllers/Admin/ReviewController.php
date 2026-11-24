<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContentRequest;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->validate(['status' => ['nullable', 'in:pending,approved,featured']])['status'] ?? null;

        $reviews = Review::query()->with('product:id,name,slug')
            ->when($status === 'pending', fn ($q) => $q->where('is_approved', false))
            ->when($status === 'approved', fn ($q) => $q->where('is_approved', true))
            ->when($status === 'featured', fn ($q) => $q->where('is_featured', true))
            ->latest()
            ->paginate(config('store.admin_per_page'))
            ->withQueryString();

        return view('admin.reviews.index', compact('reviews', 'status'));
    }

    public function create(): View
    {
        return view('admin.reviews.form', ['review' => new Review(['rating' => 5, 'is_approved' => true]), 'products' => $this->products()]);
    }

    public function store(ContentRequest $request): RedirectResponse
    {
        Review::query()->create($request->validated());

        return redirect()->route('admin.reviews.index')->with('success', 'Review added.');
    }

    public function edit(Review $review): View
    {
        return view('admin.reviews.form', ['review' => $review, 'products' => $this->products()]);
    }

    public function update(ContentRequest $request, Review $review): RedirectResponse
    {
        $review->update($request->validated());

        return redirect()->route('admin.reviews.index')->with('success', 'Review saved.');
    }

    /** Quick approve / feature switch from the list. */
    public function toggle(Request $request, Review $review): RedirectResponse
    {
        $field = $request->validate(['field' => ['required', 'in:is_approved,is_featured']])['field'];
        $review->update([$field => ! $review->{$field}] + ($field === 'is_featured' && ! $review->is_featured ? ['is_approved' => true] : []));

        return back()->with('success', 'Review updated.');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $review->delete();

        return back()->with('success', 'Review deleted.');
    }

    private function products()
    {
        return Product::query()->orderBy('name')->pluck('name', 'id');
    }
}
