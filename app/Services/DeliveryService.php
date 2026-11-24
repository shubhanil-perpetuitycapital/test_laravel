<?php

namespace App\Services;

use App\Models\DeliveryPincode;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Works out delivery availability, charge and date.
 *
 * Kolkata local: PIN must belong to an active zone. Charge comes from the
 * zone, free above the zone's (or the store-wide) threshold. Same day only
 * when the store switch AND the zone allow it AND the order is placed
 * before the zone's cut-off (IST); otherwise the next available day.
 *
 * Outside Kolkata: flat courier charge from settings, or "to be confirmed"
 * when no charge is set. Never promises a date, only an estimate text.
 */
class DeliveryService
{
    public function __construct(private SettingService $settings) {}

    public function methodsEnabled(): array
    {
        return array_keys(array_filter([
            'local' => $this->settings->bool('local_delivery_enabled'),
            'outstation' => $this->settings->bool('outstation_enabled'),
        ]));
    }

    public static function isValidPincode(?string $pincode): bool
    {
        return (bool) preg_match('/^[1-9][0-9]{5}$/', (string) $pincode);
    }

    public function quote(string $method, ?string $pincode, float $orderAmount, ?CarbonInterface $now = null): DeliveryQuote
    {
        $pincode = preg_replace('/\D/', '', (string) $pincode);

        if (! self::isValidPincode($pincode)) {
            return DeliveryQuote::fail($method, 'Enter a valid 6-digit PIN code.');
        }

        return $method === 'outstation'
            ? $this->outstation($orderAmount)
            : $this->local($pincode, $orderAmount, CarbonImmutable::instance($now ?? now()));
    }

    /** True when the PIN is in an active Kolkata delivery zone. */
    public function isLocalPincode(string $pincode): bool
    {
        return $this->findPincode($pincode) !== null;
    }

    private function local(string $pincode, float $amount, CarbonImmutable $now): DeliveryQuote
    {
        if (! $this->settings->bool('local_delivery_enabled')) {
            return DeliveryQuote::fail('local', 'Kolkata local delivery is paused right now.');
        }

        $row = $this->findPincode($pincode);

        if (! $row) {
            $hint = $this->settings->bool('outstation_enabled') ? ' Choose courier delivery instead.' : '';

            return DeliveryQuote::fail('local', "We don't deliver to {$pincode} ourselves yet.".$hint);
        }

        $zone = $row->zone;
        $threshold = $zone->free_delivery_threshold !== null
            ? (float) $zone->free_delivery_threshold
            : $this->settings->float('free_delivery_threshold');

        $free = $threshold !== null && $threshold > 0 && $amount >= $threshold;
        $charge = $free ? 0.0 : (float) $zone->delivery_charge;

        [$date, $sameDay] = $this->localDate($zone, $now);

        $estimate = $sameDay
            ? 'Today, '.$date->format('D j M')
            : $date->format('l, j M');

        $note = $zone->estimated_text;
        if (! $sameDay && $zone->same_day_available && $this->settings->bool('same_day_enabled') && $zone->same_day_cutoff) {
            $note = 'Same-day cut-off ('.$this->formatTime($zone->same_day_cutoff).') has passed, so this is the next available day.';
        }
        if (! $free && $threshold) {
            $note = trim(($note ? $note.' ' : '').'Free delivery on orders over '.money($threshold).'.');
        }

        return new DeliveryQuote(
            ok: true, method: 'local', charge: $charge, free: $charge == 0.0, sameDay: $sameDay,
            date: $date, estimate: $estimate, note: $note, zone: $zone, area: $row->area_name,
        );
    }

    private function outstation(float $amount): DeliveryQuote
    {
        if (! $this->settings->bool('outstation_enabled')) {
            return DeliveryQuote::fail('outstation', 'We are not shipping outside Kolkata right now.');
        }

        $threshold = $this->settings->float('outstation_free_threshold');
        $flat = $this->settings->float('outstation_charge');
        $estimate = (string) $this->settings->get('outstation_estimated_text');

        if ($threshold !== null && $threshold > 0 && $amount >= $threshold) {
            return new DeliveryQuote(ok: true, method: 'outstation', charge: 0.0, free: true, estimate: $estimate);
        }

        if ($flat === null) {
            return new DeliveryQuote(
                ok: true, method: 'outstation', pending: true, estimate: $estimate,
                note: 'We will confirm the courier charge on WhatsApp before dispatch, based on weight and destination.',
            );
        }

        return new DeliveryQuote(ok: true, method: 'outstation', charge: $flat, free: $flat == 0.0, estimate: $estimate);
    }

    /** @return array{0: CarbonImmutable, 1: bool} */
    private function localDate($zone, CarbonImmutable $now): array
    {
        $closedSundays = $this->settings->bool('closed_on_sundays');
        $today = $now->startOfDay();

        $sameDayPossible = $this->settings->bool('same_day_enabled')
            && $zone->same_day_available
            && $zone->same_day_cutoff
            && $now->format('H:i:s') < $this->normaliseTime($zone->same_day_cutoff)
            && ! ($closedSundays && $today->isSunday());

        if ($sameDayPossible) {
            return [$today, true];
        }

        $date = $today->addDays(max(1, (int) $zone->lead_days));
        while ($closedSundays && $date->isSunday()) {
            $date = $date->addDay();
        }

        return [$date, false];
    }

    private function findPincode(string $pincode): ?DeliveryPincode
    {
        return DeliveryPincode::query()
            ->with('zone')
            ->where('pincode', $pincode)
            ->where('is_active', true)
            ->whereHas('zone', fn ($q) => $q->where('is_active', true))
            ->first();
    }

    private function normaliseTime(string $time): string
    {
        return strlen($time) === 5 ? $time.':00' : $time;
    }

    private function formatTime(string $time): string
    {
        return CarbonImmutable::createFromFormat('H:i:s', $this->normaliseTime($time))->format('g:i a');
    }
}
