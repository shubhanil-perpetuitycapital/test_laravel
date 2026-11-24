<?php

namespace App\Http\Requests\Admin;

use App\Models\DeliveryPincode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DeliveryZoneRequest extends FormRequest
{
    /** @var array<string, ?string> parsed pincode => area */
    private array $parsed = [];

    public function authorize(): bool
    {
        return (bool) $this->user()?->isOwner();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'delivery_charge' => ['required', 'numeric', 'min:0', 'max:100000'],
            'free_delivery_threshold' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'same_day_available' => ['boolean'],
            'same_day_cutoff' => ['nullable', 'required_if_accepted:same_day_available', 'date_format:H:i'],
            'lead_days' => ['required', 'integer', 'min:1', 'max:30'],
            'estimated_text' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'pincodes' => ['required', 'string', 'max:20000'],
        ];
    }

    public function messages(): array
    {
        return [
            'same_day_cutoff.required_if_accepted' => 'Set a cut-off time for same-day delivery.',
            'pincodes.required' => 'Add at least one PIN code this zone covers.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'same_day_available' => $this->boolean('same_day_available'),
            'is_active' => $this->boolean('is_active'),
            'same_day_cutoff' => $this->filled('same_day_cutoff') ? substr((string) $this->input('same_day_cutoff'), 0, 5) : null,
        ]);
    }

    /**
     * Each line: "700016 Park Street" or "700016, Park Street". Also accepts
     * PINs separated by commas or spaces with no area name.
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            $errors = [];
            foreach (preg_split('/\r\n|\r|\n/', (string) $this->input('pincodes')) as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                if (preg_match('/^(\d{6})\s*[,\-–:]?\s*(.*)$/u', $line, $m) && ! preg_match('/^\d{6}([\s,]+\d{6})+$/', $line)) {
                    $this->parsed[$m[1]] = trim($m[2]) ?: null;

                    continue;
                }
                foreach (preg_split('/[\s,]+/', $line) as $pin) {
                    preg_match('/^[1-9]\d{5}$/', $pin) ? $this->parsed[$pin] = null : $errors[] = $pin;
                }
            }

            if ($errors) {
                $validator->errors()->add('pincodes', 'These are not valid PIN codes: '.implode(', ', array_slice($errors, 0, 10)));
            }

            $taken = DeliveryPincode::query()->with('zone:id,name')
                ->whereIn('pincode', array_keys($this->parsed))
                ->when($this->route('zone'), fn ($q, $zone) => $q->where('delivery_zone_id', '!=', $zone->id))
                ->get();
            foreach ($taken as $row) {
                $validator->errors()->add('pincodes', "{$row->pincode} is already in the zone \"{$row->zone->name}\". Remove it there first.");
            }
        }];
    }

    /** @return array<string, ?string> */
    public function pincodes(): array
    {
        return $this->parsed;
    }
}
