<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalisesIndianMobile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    use NormalisesIndianMobile;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'mobile' => self::mobileRules(),
            'email' => ['nullable', 'email:rfc', 'max:150'],
            'address' => ['required', 'string', 'min:10', 'max:500'],
            'city' => ['required', 'string', 'max:80'],
            'state' => ['required', 'string', 'max:80'],
            'pincode' => ['required', 'regex:/^[1-9][0-9]{5}$/'],
            'delivery_method' => ['required', Rule::in(['local', 'outstation'])],
            'payment_method' => ['required', Rule::in(['upi', 'cod'])],
            'delivery_instructions' => ['nullable', 'string', 'max:500'],
            'order_notes' => ['nullable', 'string', 'max:1000'],
            'accept_terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.regex' => self::mobileMessage(),
            'pincode.regex' => 'Enter a valid 6-digit PIN code.',
            'accept_terms.accepted' => 'Please accept the terms and delivery policy to place your order.',
            'address.min' => 'Please enter the full address, including house/flat number and street.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normaliseMobile();

        $this->merge([
            'pincode' => preg_replace('/\D/', '', (string) $this->input('pincode')),
            'name' => trim((string) $this->input('name')),
        ]);
    }
}
