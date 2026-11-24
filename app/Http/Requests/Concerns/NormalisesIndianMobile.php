<?php

namespace App\Http\Requests\Concerns;

/**
 * Accepts "+91 98300 12345", "098300-12345" etc. and stores 9830012345.
 */
trait NormalisesIndianMobile
{
    protected function normaliseMobile(string $field = 'mobile'): void
    {
        if (! $this->filled($field)) {
            return;
        }

        $digits = preg_replace('/\D+/', '', (string) $this->input($field));

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        } elseif (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        $this->merge([$field => $digits]);
    }

    protected static function mobileRules(bool $required = true): array
    {
        return [$required ? 'required' : 'nullable', 'regex:/^[6-9][0-9]{9}$/'];
    }

    protected static function mobileMessage(): string
    {
        return 'Enter a 10-digit Indian mobile number.';
    }
}
