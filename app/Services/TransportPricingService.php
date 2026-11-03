<?php

namespace App\Services;

use App\Models\SiteSetting;
use App\Models\TransportFare;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

final class TransportPricingService
{
    public function quote(array $d): array
    {
        $fare = TransportFare::with(['route', 'vehicleType'])->find($d['fare_id']);
        if (! $fare || ! $fare->active || ! $fare->vehicleType->active || ! $fare->route->published || $fare->transport_route_id != (int) $d['route_id']) {
            $this->fail('This route and vehicle choice is unavailable.');
        }
        $pax = (int) $d['adults'] + (int) ($d['children'] ?? 0);
        $route = $fare->route;
        if ($pax < $fare->minimum_passengers || $pax > $fare->maximum_passengers) {
            $this->fail('Passenger count is outside the allowed range.');
        }
        $vehicles = $fare->pricing_basis === 'per_vehicle' ? (int) ceil($pax / $fare->capacity) : 1;
        if (($pax > $fare->capacity && ! $fare->multiple_vehicles) || $vehicles > $fare->maximum_vehicles || ($fare->pricing_basis === 'per_person' && $pax > $fare->capacity)) {
            $this->fail('This option cannot carry your party. Please choose a larger vehicle.');
        }
        if ($fare->luggage_allowance !== null && (int) $d['luggage'] > $fare->luggage_allowance * $vehicles) {
            $this->fail('Luggage exceeds the configured allowance. Please choose another type.');
        }
        $pickup = CarbonImmutable::parse($d['pickup_at'], config('tourism.timezone'));
        if ($pickup->lessThan(now(config('tourism.timezone'))->addHours($route->cutoff_hours))) {
            $this->fail('The pickup time is too soon or in the past.');
        }
        if (($route->active_from && $pickup->toDateString() < $route->active_from->toDateString()) || ($route->active_until && $pickup->toDateString() > $route->active_until->toDateString())) {
            $this->fail('This route is not active for the chosen date.');
        }
        $quantity = $fare->pricing_basis === 'per_person' ? $pax : $vehicles;
        $currency = $d['currency'];
        $rate = SiteSetting::valueFor('usd_tzs_rate');
        $pending = $fare->currency !== $currency && ! $rate;
        if ($pending) {
            $price = null;
        } else {
            try {
                $price = Money::convert(Money::minor($fare->price), $fare->currency, $currency, $rate);
            } catch (\InvalidArgumentException $e) {
                $this->fail($e->getMessage());
            }
        }
        if ($price !== null && $price * $quantity > Money::MAX) {
            $this->fail('Requested total exceeds the supported limit.');
        }
        $snapshot = ['route_id' => $route->id, 'route' => $route->name, 'origin' => $route->origin, 'destination' => $route->destination, 'vehicle' => $fare->vehicleType->name, 'capacity' => $fare->capacity, 'inclusions' => $route->inclusions, 'exclusions' => $route->exclusions, 'rate' => $rate];
        $lines = [['description' => $route->origin.' to '.$route->destination.' - '.$fare->vehicleType->name, 'quantity' => $quantity, 'unit' => $fare->pricing_basis, 'unit_price' => $price === null ? null : Money::decimal($price), 'amount' => $price === null ? null : Money::decimal($price * $quantity), 'snapshot' => $snapshot]];
        if (! empty($d['return_at'])) {
            if ($fare->return_price === null) {
                $this->fail('A return service is not configured for this option.');
            }
            $return = CarbonImmutable::parse($d['return_at'], config('tourism.timezone'));
            if (! $return->greaterThan($pickup)) {
                $this->fail('Return time must be after pickup.');
            }
            if ($route->active_until && $return->toDateString() > $route->active_until->toDateString()) {
                $this->fail('Return date is outside the active period.');
            }
            if ($fare->currency !== $currency && ! $rate) {
                $returnPrice = null;
                $pending = true;
            } else {
                try {
                    $returnPrice = Money::convert(Money::minor($fare->return_price), $fare->currency, $currency, $rate);
                } catch (\InvalidArgumentException $e) {
                    $this->fail($e->getMessage());
                }
            }
            if ($returnPrice !== null && $returnPrice * $quantity > Money::MAX) {
                $this->fail('Return total exceeds the supported limit.');
            }
            $lines[] = ['description' => 'Return: '.$route->destination.' to '.$route->origin, 'quantity' => $quantity, 'unit' => $fare->pricing_basis, 'unit_price' => $returnPrice === null ? null : Money::decimal($returnPrice), 'amount' => $returnPrice === null ? null : Money::decimal($returnPrice * $quantity), 'snapshot' => $snapshot];
        }
        $total = array_sum(array_map(fn ($x) => $x['amount'] === null ? 0 : Money::minor($x['amount']), $lines));
        if ($total > Money::MAX) {
            $this->fail('Requested total exceeds the supported limit.');
        }

        return ['currency' => $currency, 'lines' => $lines, 'total_minor' => $total, 'pending' => $pending, 'rate' => $rate, 'vehicles' => $vehicles, 'region_id' => $route->region_id, 'snapshot' => $snapshot,
            'hash' => hash_hmac('sha256', json_encode([$lines, $currency, $d['pickup_at'], $d['return_at'] ?? null, $pax, $d['luggage'], $fare->updated_at->toISOString()]), config('app.key'))];
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['transport' => $message]);
    }
}
