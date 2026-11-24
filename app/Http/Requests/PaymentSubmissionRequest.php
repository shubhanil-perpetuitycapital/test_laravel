<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_reference' => ['required', 'string', 'min:6', 'max:40', 'regex:/^[A-Za-z0-9\-]+$/'],
            'screenshot' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_reference.required' => 'Enter the UPI transaction / UTR number from your payment app.',
            'payment_reference.regex' => 'Use only letters and numbers from the transaction ID.',
            'screenshot.max' => 'The screenshot must be smaller than 4 MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['payment_reference' => preg_replace('/\s+/', '', (string) $this->input('payment_reference'))]);
    }
}
