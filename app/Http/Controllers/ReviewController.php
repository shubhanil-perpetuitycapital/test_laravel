<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;

class ReviewController extends Controller
{
    /** Customer review: saved unapproved, shown after the owner approves it. */
    public function store(ReviewRequest $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $product->reviews()->create($request->safe()->only(['customer_name', 'location', 'rating', 'comment']) + [
            'is_approved' => false,
            'is_featured' => false,
        ]);

        return redirect()->to($product->url.'#reviews')
            ->with('success', 'Thank you! Your review will appear once we have checked it.');
    }
}
