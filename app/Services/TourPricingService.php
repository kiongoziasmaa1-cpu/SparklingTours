<?php

namespace App\Services;

use App\Models\CatalogItem;
use App\Models\SiteSetting;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

final class TourPricingService
{
    public function quote(array $d): array
    {
        $ids = array_values(array_unique(array_map('intval', $d['service_ids'] ?? [])));
        sort($ids);
        $tour = null;
        if (! empty($d['tour_id'])) {
            $tour = CatalogItem::published()->where('kind', 'tour')->where('region_id', $d['region_id'])->find($d['tour_id']);
            if (! $tour) {
                $this->fail('That tour is no longer available.');
            }
            $included = $tour->included_item_ids ?? [];
            $ids = array_values(array_diff($ids, $included));
        }
        $items = CatalogItem::published()->where('region_id', $d['region_id'])->whereIn('id', $ids)->whereIn('kind', ['destination', 'food', 'activity', 'guide'])->orderBy('id')->get();
        if ($items->count() !== count($ids)) {
            $this->fail('A selected service is unavailable or belongs to another region.');
        }
        if (! $tour && ! $items->contains('kind', 'destination')) {
            $this->fail('Choose at least one destination.');
        }
        if ($tour) {
            $items->prepend($tour);
        }
        $currency = $d['currency'];
        $rate = SiteSetting::valueFor('usd_tzs_rate');
        $lines = [];
        $total = 0;
        $pending = false;
        $days = (int) CarbonImmutable::parse($d['start_date'])->diffInDays(CarbonImmutable::parse($d['end_date'])) + 1;
        if ($days < 1 || $days > 365) {
            $this->fail('Choose a trip duration between 1 and 365 days.');
        }
        if ($tour && $days !== (int) $tour->duration_days) {
            $this->fail('The dates must match the published tour duration of '.$tour->duration_days.' day(s).');
        }
        foreach ($items as $item) {
            $pax = (int) $d['adults'] + (int) ($d['children'] ?? 0);
            $quantity = match ($item->pricing_basis) {
                'per_person' => $pax, 'per_day' => $days, 'per_person_day' => $pax * $days, 'per_person_meal' => $pax, default => 1,
            };
            $price = $item->is_free ? 0 : ($item->price === null ? null : Money::minor($item->price));
            if ($price !== null && $item->currency !== $currency && ! $rate) {
                $price = null;
            } elseif ($price !== null) {
                try {
                    $price = Money::convert($price, $item->currency, $currency, $rate);
                } catch (\InvalidArgumentException $e) {
                    $this->fail($e->getMessage());
                }
            }
            $amount = $price === null ? null : $price * $quantity;
            if ($amount === null) {
                $pending = true;
            } else {
                $total += $amount;
            }
            if ($total > Money::MAX) {
                $this->fail('The requested total is above the supported limit. Please contact us.');
            }
            $lines[] = ['catalog_item_id' => $item->id, 'description' => $item->name, 'quantity' => $quantity, 'unit' => $item->pricing_basis, 'unit_price' => $price === null ? null : Money::decimal($price), 'amount' => $amount === null ? null : Money::decimal($amount),
                'snapshot' => ['kind' => $item->kind, 'original_currency' => $item->currency, 'original_price' => $item->price, 'inclusions' => $item->inclusions, 'exclusions' => $item->exclusions, 'included_item_ids' => $item->included_item_ids, 'rate' => $rate]];
        }

        return ['currency' => $currency, 'lines' => $lines, 'total_minor' => $total, 'pending' => $pending, 'rate' => $rate, 'hash' => hash_hmac('sha256', json_encode([$lines, $currency, $d['start_date'], $d['end_date'], $d['adults'], $d['children'] ?? 0, $rate]), config('app.key'))];
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['services' => $message]);
    }
}
