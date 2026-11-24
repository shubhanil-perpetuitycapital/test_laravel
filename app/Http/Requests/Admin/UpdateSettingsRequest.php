<?php

namespace App\Http\Requests\Admin;

use App\Support\SettingsSchema;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isOwner();
    }

    private function screen(): string
    {
        return (string) $this->route('screen');
    }

    /** Rules come straight from SettingsSchema, plus a remove_<image> box per image. */
    public function rules(): array
    {
        $rules = [];
        foreach (SettingsSchema::fields($this->screen()) as $key => $field) {
            $rules[$key] = $field['rules'];
            if ($field['type'] === 'image') {
                $rules['remove_'.$key] = ['nullable', 'boolean'];
            }
        }

        return $rules;
    }

    public function attributes(): array
    {
        return array_map(fn ($f) => strtolower($f['label']), SettingsSchema::fields($this->screen()));
    }

    public function messages(): array
    {
        return [
            'gstin.regex' => 'Enter a valid 15-character GSTIN, e.g. 19ABCDE1234F1Z5.',
            'fssai_number.digits' => 'The FSSAI number has exactly 14 digits.',
            'whatsapp_number.regex' => 'Enter the WhatsApp number with digits only, e.g. 91 98300 12345.',
            'google_analytics_id.regex' => 'The Analytics ID looks like G-XXXXXXXXXX.',
            'upi_id.regex' => 'A UPI ID looks like name@bank, e.g. shopname@okhdfcbank.',
            'upi_id.required_if_accepted' => 'Enter your UPI ID, or turn off UPI payments.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        foreach (SettingsSchema::fields($this->screen()) as $key => $field) {
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
        if ($this->filled('upi_id')) {
            $merge['upi_id'] = strtolower(trim((string) $this->input('upi_id')));
        }
        $this->merge($merge);
    }
}
