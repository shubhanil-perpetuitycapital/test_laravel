<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalisesIndianMobile;
use App\Models\CustomOrderEnquiry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomOrderRequest extends FormRequest
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
            'occasion' => ['nullable', Rule::in(CustomOrderEnquiry::OCCASIONS)],
            'chocolate_type' => ['nullable', Rule::in(CustomOrderEnquiry::CHOCOLATE_TYPES)],
            'quantity' => ['required', 'string', 'max:100'],
            'budget' => ['nullable', 'string', 'max:100'],
            'required_date' => ['nullable', 'date', 'after_or_equal:today', 'before:+1 year'],
            'delivery_pincode' => ['nullable', 'regex:/^[1-9][0-9]{5}$/'],
            'message' => ['nullable', 'string', 'max:2000'],
            'reference_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'website' => ['prohibited'], // honeypot: hidden field bots fill in
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.regex' => self::mobileMessage(),
            'delivery_pincode.regex' => 'Enter a valid 6-digit PIN code.',
            'required_date.after_or_equal' => 'Choose today or a later date.',
            'reference_image.max' => 'The image must be smaller than 4 MB.',
            'website.prohibited' => 'Something went wrong. Please try again.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normaliseMobile();
        if ($this->filled('delivery_pincode')) {
            $this->merge(['delivery_pincode' => preg_replace('/\D/', '', (string) $this->input('delivery_pincode'))]);
        }
    }
}
