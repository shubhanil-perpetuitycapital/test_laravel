<?php

namespace App\Services;

use App\Models\DeliveryZone;
use Carbon\CarbonInterface;

/**
 * Result of a delivery calculation for one PIN code and method.
 */
final class DeliveryQuote
{
    public function __construct(
        public readonly bool $ok,
        public readonly string $method,
        public readonly ?string $error = null,
        public readonly float $charge = 0.0,
        public readonly bool $pending = false,   // courier charge to be confirmed by the owner
        public readonly bool $free = false,
        public readonly bool $sameDay = false,
        public readonly ?CarbonInterface $date = null,
        public readonly ?string $estimate = null,
        public readonly ?string $note = null,
        public readonly ?DeliveryZone $zone = null,
        public readonly ?string $area = null,
    ) {}

    public static function fail(string $method, string $error): self
    {
        return new self(ok: false, method: $method, error: $error);
    }

    public function chargeLabel(): string
    {
        if (! $this->ok) {
            return '—';
        }
        if ($this->pending) {
            return 'To be confirmed';
        }

        return $this->charge > 0 ? money($this->charge) : 'Free';
    }

    public function toArray(): array
    {
        return [
            'ok' => $this->ok,
            'method' => $this->method,
            'error' => $this->error,
            'charge' => $this->charge,
            'charge_label' => $this->chargeLabel(),
            'pending' => $this->pending,
            'free' => $this->free,
            'same_day' => $this->sameDay,
            'date' => $this->date?->toDateString(),
            'estimate' => $this->estimate,
            'note' => $this->note,
            'area' => $this->area,
        ];
    }
}
