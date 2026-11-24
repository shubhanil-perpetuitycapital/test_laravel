<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Validation for the simple content modules: reviews, banners, gallery
 * and pages. The route name decides which rule set applies.
 */
class ContentRequest extends FormRequest
{
    private const IMAGE = ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    private function module(): string
    {
        return explode('.', (string) $this->route()?->getName())[1] ?? '';
    }

    private function creating(): bool
    {
        return str_ends_with((string) $this->route()?->getName(), '.store');
    }

    public function rules(): array
    {
        return match ($this->module()) {
            'reviews' => [
                'product_id' => ['nullable', 'integer', 'exists:products,id'],
                'customer_name' => ['required', 'string', 'max:120'],
                'location' => ['nullable', 'string', 'max:120'],
                'rating' => ['required', 'integer', 'between:1,5'],
                'comment' => ['required', 'string', 'max:1000'],
                'is_approved' => ['boolean'],
                'is_featured' => ['boolean'],
            ],
            'banners' => [
                'title' => ['required', 'string', 'max:160'],
                'subtitle' => ['nullable', 'string', 'max:300'],
                'image' => [$this->creating() ? 'required' : 'nullable', ...self::IMAGE],
                'mobile_image' => ['nullable', ...self::IMAGE],
                'remove_mobile_image' => ['nullable', 'boolean'],
                'button_text' => ['nullable', 'string', 'max:60'],
                'button_url' => ['nullable', 'string', 'max:255', 'regex:#^(/|https?://)#'],
                'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
                'is_active' => ['boolean'],
            ],
            'gallery' => [
                'image' => [$this->creating() ? 'required' : 'nullable', ...self::IMAGE],
                'caption' => ['nullable', 'string', 'max:255'],
                'link_url' => ['nullable', 'url:https,http', 'max:255'],
                'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
                'is_active' => ['boolean'],
            ],
            'pages' => [
                'title' => ['required', 'string', 'max:160'],
                'slug' => ['nullable', 'string', 'max:160', Rule::unique('pages', 'slug')->ignore($this->route('page')?->id)],
                'content' => ['nullable', 'string', 'max:100000'],
                'meta_title' => ['nullable', 'string', 'max:70'],
                'meta_description' => ['nullable', 'string', 'max:170'],
                'is_active' => ['boolean'],
            ],
            default => [],
        };
    }

    public function messages(): array
    {
        return [
            'button_url.regex' => 'Use a page on this site (starting with /) or a full https:// link.',
            'image.required' => 'Choose an image to upload.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        foreach (['is_active', 'is_approved', 'is_featured'] as $flag) {
            if ($this->has($flag)) {
                $merge[$flag] = $this->boolean($flag);
            }
        }
        if ($this->module() === 'pages' && $this->filled('slug')) {
            $merge['slug'] = Str::slug((string) $this->input('slug'));
        }
        $this->merge($merge);
    }
}
