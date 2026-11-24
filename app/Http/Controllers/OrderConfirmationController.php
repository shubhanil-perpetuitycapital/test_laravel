<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Http\Requests\PaymentSubmissionRequest;
use App\Models\Order;
use App\Services\CompanyProfile;
use App\Services\SeoData;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Thank-you page reached through a signed link (only the buyer has it).
 * Shows UPI details and lets the customer send their payment reference.
 */
class OrderConfirmationController extends Controller
{
    public function show(Order $order, CompanyProfile $company, SettingService $settings): View
    {
        $order->load('items');

        $upi = [
            'id' => $settings->get('upi_id'),
            'name' => $settings->get('upi_payee_name') ?: $company->name(),
            'qr' => media_url($settings->raw('upi_qr'), null),
            'instructions' => $settings->get('payment_instructions'),
        ];

        // upi:// deep link opens GPay/PhonePe/Paytm on phones with the amount filled.
        $upi['link'] = 'upi://pay?'.http_build_query([
            'pa' => $upi['id'],
            'pn' => $upi['name'],
            'am' => number_format((float) $order->total, 2, '.', ''),
            'cu' => 'INR',
            'tn' => 'Order '.$order->order_number,
        ], '', '&', PHP_QUERY_RFC3986);

        $seo = SeoData::make('Order '.$order->order_number.' | '.$company->name(), null, noindex: true);

        return view('orders.confirmation', compact('order', 'upi', 'seo'));
    }

    public function payment(PaymentSubmissionRequest $request, Order $order): RedirectResponse
    {
        if (! $order->canSubmitPayment()) {
            return back()->withErrors(['payment_reference' => 'This order is not waiting for a UPI payment.']);
        }

        $screenshot = $order->payment_screenshot;
        if ($request->hasFile('screenshot')) {
            if ($screenshot) {
                Storage::disk('local')->delete($screenshot);
            }
            // Private disk: never publicly reachable, viewed only from the admin.
            $screenshot = $request->file('screenshot')->store('payments/'.date('Y/m'), 'local');
        }

        $order->update([
            'payment_reference' => $request->validated('payment_reference'),
            'payment_screenshot' => $screenshot,
            'payment_status' => PaymentStatus::Submitted,
            'payment_submitted_at' => now(),
        ]);

        return back()->with('success', 'Thank you! We have your payment reference and will confirm your order once we see the payment.');
    }
}
