<?php

namespace App\Http\Requests\Admin;

use App\Support\SettingsSchema;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isOwner();
    }

    /**
     * Rules come straight from SettingsSchema, plus a "remove_<image>"
     * checkbox for each image field.
     */
    public function rules(): array
    {
        $rules = [];

        foreach (SettingsSchema::companyFields() as $key => $field) {
            $rules[$key] = $field['rules'];

            if ($field['type'] === 'image') {
                $rules['remove_'.$key] = ['nullable', 'boolean'];
            }
        }

        return $rules;
    }

    public function attributes(): array
    {
        $attributes = [];
        foreach (SettingsSchema::companyFields() as $key => $field) {
            $attributes[$key] = strtolower($field['label']);
        }

        return $attributes;
    }

    public function messages(): array
    {
        return [
            'gstin.regex' => 'Enter a valid 15-character GSTIN, e.g. 19ABCDE1234F1Z5.',
            'fssai_number.digits' => 'The FSSAI number has exactly 14 digits.',
            'whatsapp_number.regex' => 'Enter the WhatsApp number with digits only, e.g. 91 98300 12345.',
            'google_analytics_id.regex' => 'The Analytics ID looks like G-XXXXXXXXXX.',
        ];
    }

    /**
     * Normalise input before validation: unchecked checkboxes become "0",
     * text is trimmed, GSTIN is upper-cased.
     */
    protected function prepareForValidation(): void
    {
        $merge = [];

        foreach (SettingsSchema::companyFields() as $key => $field) {
            if ($field['type'] === 'boolean') {
                $merge[$key] = $this->boolean($key) ? '1' : '0';
            }
        }

        if ($this->filled('gstin')) {
            $merge['gstin'] = strtoupper(preg_replace('/\s+/', '', (string) $this->input('gstin')));
        }

        if ($this->filled('fssai_number')) {
            $merge['fssai_number'] = preg_replace('/\D+/', '', (string) $this->input('fssai_number'));
        }

        $this->merge($merge);
    }
}
