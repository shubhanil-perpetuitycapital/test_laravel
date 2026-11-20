<?php

namespace App\Http\Requests;

use App\Services\CatalogService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShopFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:80'],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'sort' => ['nullable', Rule::in(array_keys(CatalogService::SORTS))],
            'type' => ['nullable', Rule::in(['gift', 'bestseller', 'featured'])],
            'in_stock' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Invalid filter values are dropped rather than shown as errors:
     * a bad query string should still render the shop.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $this->replace(collect($this->query())->except(array_keys($validator->errors()->messages()))->all());
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        $filters = $this->only(['q', 'min_price', 'max_price', 'sort', 'type']);
        $filters['in_stock'] = $this->boolean('in_stock');
        $filters['q'] = isset($filters['q']) ? trim((string) $filters['q']) : null;

        // Swap reversed price bounds instead of returning nothing.
        if (filled($filters['min_price'] ?? null) && filled($filters['max_price'] ?? null)
            && (float) $filters['min_price'] > (float) $filters['max_price']) {
            [$filters['min_price'], $filters['max_price']] = [$filters['max_price'], $filters['min_price']];
        }

        return $filters;
    }
}
