<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EnquiryRequest;
use App\Models\CustomOrderEnquiry;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class EnquiryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        $enquiries = CustomOrderEnquiry::query()
            ->when($filters['q'] ?? null, function ($q, $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('mobile', 'like', $like)
                    ->orWhere('enquiry_number', 'like', $like)->orWhere('occasion', 'like', $like));
            })
            ->when(array_key_exists($filters['status'] ?? '', CustomOrderEnquiry::STATUSES) ? $filters['status'] : null, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(config('store.admin_per_page'))
            ->withQueryString();

        return view('admin.enquiries.index', compact('enquiries', 'filters'));
    }

    public function show(CustomOrderEnquiry $enquiry): View
    {
        $enquiry->load(['customer', 'convertedOrder']);

        return view('admin.enquiries.show', compact('enquiry'));
    }

    public function update(EnquiryRequest $request, CustomOrderEnquiry $enquiry): RedirectResponse
    {
        $enquiry->update($request->validated());

        return back()->with('success', 'Enquiry saved.'.($enquiry->quoted_amount ? ' Use "Send quote on WhatsApp" to share it.' : ''));
    }

    public function convert(EnquiryRequest $request, CustomOrderEnquiry $enquiry, OrderService $orders): RedirectResponse
    {
        if ($enquiry->converted_order_id) {
            return back()->with('error', 'This enquiry has already been turned into an order.');
        }

        $order = $orders->createFromEnquiry($enquiry, $request->validated(), $request->user());

        return redirect()->route('admin.orders.show', $order)->with('success', 'Order '.$order->order_number.' created from enquiry '.$enquiry->enquiry_number.'.');
    }

    public function image(CustomOrderEnquiry $enquiry)
    {
        abort_unless($enquiry->reference_image && Storage::disk('local')->exists($enquiry->reference_image), 404);

        return Storage::disk('local')->response($enquiry->reference_image);
    }

    public function destroy(CustomOrderEnquiry $enquiry): RedirectResponse
    {
        $enquiry->delete();

        return redirect()->route('admin.enquiries.index')->with('success', 'Enquiry '.$enquiry->enquiry_number.' deleted.');
    }
}
