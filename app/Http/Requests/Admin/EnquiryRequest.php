<?php

namespace App\Http\Requests\Admin;

use App\Models\CustomOrderEnquiry;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update an enquiry (status, quote, notes) or convert it to an order.
 */
class EnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        if ($this->route()?->getName() === 'admin.enquiries.convert') {
            return [
                'description' => ['required', 'string', 'max:180'],
                'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
                'unit_price' => ['required', 'numeric', 'min:0', 'max:1000000'],
                'delivery_charge' => ['nullable', 'numeric', 'min:0', 'max:100000'],
                'delivery_method' => ['required', Rule::in(array_keys(Order::DELIVERY_METHODS))],
                'payment_method' => ['required', Rule::in(array_keys(Order::PAYMENT_METHODS))],
                'payment_status' => ['required', Rule::in(['unpaid', 'paid'])],
                'address' => ['required', 'string', 'max:500'],
                'city' => ['required', 'string', 'max:80'],
                'state' => ['required', 'string', 'max:80'],
                'pincode' => ['required', 'regex:/^[1-9][0-9]{5}$/'],
                'expected_delivery_date' => ['nullable', 'date'],
            ];
        }

        return [
            'status' => ['required', Rule::in(array_keys(CustomOrderEnquiry::STATUSES))],
            'quoted_amount' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'quotation_notes' => ['nullable', 'string', 'max:2000'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return ['pincode.regex' => 'Enter a valid 6-digit PIN code.'];
    }
}
