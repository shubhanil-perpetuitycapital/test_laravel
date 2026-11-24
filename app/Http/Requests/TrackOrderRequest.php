<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalisesIndianMobile;
use Illuminate\Foundation\Http\FormRequest;

class TrackOrderRequest extends FormRequest
{
    use NormalisesIndianMobile;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_number' => ['required', 'string', 'max:30'],
            'mobile' => self::mobileRules(),
        ];
    }

    public function messages(): array
    {
        return ['mobile.regex' => self::mobileMessage()];
    }

    protected function prepareForValidation(): void
    {
        $this->normaliseMobile();
        $this->merge(['order_number' => strtoupper(trim((string) $this->input('order_number')))]);
    }
}
