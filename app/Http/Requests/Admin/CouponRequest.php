<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isOwner();
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('coupons', 'code')->ignore($this->route('coupon')?->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(['fixed', 'percent'])],
            'value' => ['required', 'numeric', 'min:1', $this->input('type') === 'percent' ? 'max:90' : 'max:100000'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'max_discount' => ['nullable', 'numeric', 'min:1', 'max:100000'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'Use capital letters, numbers, hyphens and underscores only.',
            'value.max' => $this->input('type') === 'percent' ? 'A percentage discount can be at most 90%.' : 'The discount is too large.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(preg_replace('/\s+/', '', (string) $this->input('code'))),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
