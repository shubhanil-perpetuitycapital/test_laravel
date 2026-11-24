<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates each of the order panel's forms; the route name says which one.
 */
class OrderUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return match ($this->route()?->getName()) {
            'admin.orders.status' => [
                'status' => ['required', Rule::enum(OrderStatus::class)],
                'comment' => ['nullable', 'string', 'max:500'],
                'visible_to_customer' => ['boolean'],
            ],
            'admin.orders.payment' => [
                'payment_status' => ['required', Rule::enum(PaymentStatus::class)],
                'payment_reference' => ['nullable', 'string', 'max:100'],
                'delivery_charge' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            ],
            'admin.orders.shipping' => [
                'courier_name' => ['nullable', 'string', 'max:100'],
                'tracking_number' => ['nullable', 'string', 'max:100'],
                'tracking_url' => ['nullable', 'url:https,http', 'max:255'],
                'expected_delivery_date' => ['nullable', 'date'],
            ],
            'admin.orders.notes' => [
                'internal_notes' => ['nullable', 'string', 'max:5000'],
            ],
            default => [],
        };
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('visible_to_customer') || $this->route()?->getName() === 'admin.orders.status') {
            $this->merge(['visible_to_customer' => $this->boolean('visible_to_customer')]);
        }
    }
}
