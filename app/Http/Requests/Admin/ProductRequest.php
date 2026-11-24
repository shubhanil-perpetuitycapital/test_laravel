<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $id = $this->route('product')?->id;

        return [
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('products', 'slug')->ignore($id)],
            'sku' => ['required', 'string', 'max:60', 'regex:/^[A-Za-z0-9\-_]+$/', Rule::unique('products', 'sku')->ignore($id)],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'short_description' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:10000'],
            'ingredients' => ['nullable', 'string', 'max:2000'],
            'allergens' => ['nullable', 'string', 'max:1000'],
            'weight' => ['nullable', 'string', 'max:60'],
            'price' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:100000'],
            'is_featured' => ['boolean'],
            'is_bestseller' => ['boolean'],
            'is_gift_box' => ['boolean'],
            'is_active' => ['boolean'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:170'],

            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'existing' => ['nullable', 'array'],
            'existing.*.alt_text' => ['nullable', 'string', 'max:255'],
            'existing.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'existing.*.remove' => ['nullable', 'boolean'],
            'primary_image' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'discount_price.lt' => 'The sale price must be lower than the regular price.',
            'slug.regex' => 'Use lowercase letters, numbers and hyphens only.',
            'sku.regex' => 'Use letters, numbers, hyphens and underscores only.',
            'images.*.max' => 'Each photo must be smaller than 4 MB.',
            'images.max' => 'Upload up to 10 photos at a time.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        foreach (['is_featured', 'is_bestseller', 'is_gift_box', 'is_active'] as $flag) {
            $merge[$flag] = $this->boolean($flag);
        }
        $merge['sku'] = strtoupper(trim((string) $this->input('sku')));
        if ($this->filled('slug')) {
            $merge['slug'] = \Illuminate\Support\Str::slug((string) $this->input('slug'));
        }
        $this->merge($merge);
    }
}
