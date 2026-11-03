<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CatalogItem;
use App\Models\PreferenceOption;
use App\Models\SiteSetting;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class BookingSubmissionService
{
    public function submitTourInquiry(CatalogItem $tour, array $d, string $key, string $guestHash): Booking
    {
        $payloadHash = hash('sha256', json_encode(['tour_id' => $tour->id, ...$d]));
        try {
            $booking = DB::transaction(function () use ($tour, $d, $key, $guestHash, $payloadHash) {
                $existing = Booking::where('idempotency_key', $key)->lockForUpdate()->first();
                if ($existing) {
                    abort_unless(hash_equals($existing->guest_hash, $guestHash), 403);
                    abort_unless(hash_equals($existing->payload_hash, $payloadHash), 409);

                    return $existing;
                }

                $booking = Booking::create([
                    'public_id' => (string) Str::uuid(),
                    'reference' => app(SequenceService::class)->next('TOUR'),
                    'idempotency_key' => $key,
                    'guest_hash' => $guestHash,
                    'payload_hash' => $payloadHash,
                    'kind' => 'tour',
                    'source' => 'tour_request',
                    'region_id' => $tour->region_id,
                    'name' => $d['name'],
                    'email' => $d['email'],
                    'phone' => $d['phone'],
                    'country' => $d['country'],
                    'preferred_contact' => 'email',
                    'start_date' => null,
                    'end_date' => null,
                    'adults' => null,
                    'children' => null,
                    'traveler_count' => $d['traveler_count'],
                    'currency' => $tour->currency,
                    'known_subtotal' => 0,
                    'quote_required' => true,
                    'privacy_accepted_at' => now(),
                ]);
                $booking->items()->create([
                    'catalog_item_id' => $tour->id,
                    'description' => $tour->displayName(),
                    'quantity' => 1,
                    'unit' => 'tour_request',
                    'unit_price' => null,
                    'amount' => null,
                    'snapshot' => [
                        'kind' => $tour->kind,
                        'duration_days' => $tour->duration_days,
                        'region' => $tour->region->name,
                        'package_price' => $tour->is_free ? '0.00' : $tour->price,
                        'currency' => $tour->currency,
                        'pricing_basis' => $tour->pricing_basis,
                        'is_free' => $tour->is_free,
                    ],
                ]);
                app(OutboxService::class)->create($booking->email, 'Your request '.$booking->reference, ['type' => 'booking', 'booking_id' => $booking->id, 'audience' => 'customer']);
                if ($email = SiteSetting::valueFor('notification_email') ?: SiteSetting::valueFor('email')) {
                    app(OutboxService::class)->create($email, 'New tour request '.$booking->reference, ['type' => 'booking', 'booking_id' => $booking->id, 'audience' => 'admin']);
                }

                return $booking;
            }, 3);
        } catch (UniqueConstraintViolationException $e) {
            $booking = Booking::where('idempotency_key', $key)->first();
            if (! $booking) {
                throw $e;
            }
            abort_unless(hash_equals($booking->guest_hash, $guestHash), 403);
            abort_unless(hash_equals($booking->payload_hash, $payloadHash), 409);
        }
        app(OutboxService::class)->dispatchPending();

        return $booking;
    }

    public function submit(string $kind, array $d, array $q, string $key, string $guestHash): Booking
    {
        try {
            $booking = DB::transaction(function () use ($kind, $d, $q, $key, $guestHash) {
                $existing = Booking::where('idempotency_key', $key)->lockForUpdate()->first();
                if ($existing) {
                    abort_unless(hash_equals($existing->guest_hash, $guestHash), 403);
                    abort_unless(hash_equals($existing->payload_hash, hash('sha256', json_encode($d))), 409);

                    return $existing;
                }
                $tour = $kind === 'tour';
                $start = $tour ? $d['start_date'] : substr($d['pickup_at'], 0, 10);
                $end = $tour ? $d['end_date'] : substr($d['return_at'] ?? $d['pickup_at'], 0, 10);
                $b = Booking::create([
                    'public_id' => (string) Str::uuid(), 'reference' => app(SequenceService::class)->next($tour ? 'TOUR' : 'TRN'),
                    'idempotency_key' => $key, 'guest_hash' => $guestHash, 'payload_hash' => hash('sha256', json_encode($d)),
                    'kind' => $kind, 'source' => $tour ? (! empty($d['tour_id']) ? 'package' : 'custom') : 'route',
                    'region_id' => $tour ? $d['region_id'] : $q['region_id'], 'name' => $d['name'], 'email' => $d['email'], 'phone' => $d['phone'], 'country' => $d['country'],
                    'preferred_contact' => $d['preferred_contact'], 'special_requests' => $d['special_requests'] ?? null, 'start_date' => $start, 'end_date' => $end,
                    'adults' => $d['adults'], 'children' => $d['children'], 'currency' => $q['currency'], 'known_subtotal' => Money::decimal($q['total_minor']),
                    'traveler_count' => $d['adults'] + $d['children'],
                    'quote_required' => $q['pending'] || ($tour && ! empty($d['needs_accommodation'])), 'privacy_accepted_at' => now(),
                ]);
                $b->items()->createMany($q['lines']);
                if ($tour && ! empty($d['needs_accommodation'])) {
                    $a = $d['accommodation'];
                    $labels = [];
                    foreach (['type', 'class', 'location', 'room', 'meal'] as $group) {
                        $labels[$group] = empty($a[$group.'_id']) ? 'Not sure' : PreferenceOption::findOrFail($a[$group.'_id'])->label;
                    }
                    $labels['amenities'] = PreferenceOption::whereIn('id', $a['amenities'] ?? [])->pluck('label')->all();
                    $b->accommodation()->create(['preferences' => array_merge($a, $labels), 'status' => 'requested']);
                }
                if (! $tour) {
                    $b->transport()->create([
                        'transport_fare_id' => $d['fare_id'], 'pickup_at' => CarbonImmutable::parse($d['pickup_at'], config('tourism.timezone'))->utc(),
                        'return_at' => empty($d['return_at']) ? null : CarbonImmutable::parse($d['return_at'], config('tourism.timezone'))->utc(),
                        'passengers' => $d['adults'] + $d['children'], 'vehicles' => $q['vehicles'], 'luggage' => $d['luggage'],
                        'pickup_details' => $d['pickup_details'], 'dropoff_details' => $d['dropoff_details'], 'arrival_number' => $d['arrival_number'] ?? null, 'snapshot' => $q['snapshot'],
                    ]);
                }
                app(OutboxService::class)->create($b->email, 'Your request '.$b->reference, ['type' => 'booking', 'booking_id' => $b->id, 'audience' => 'customer']);
                if ($email = (SiteSetting::valueFor('notification_email') ?: SiteSetting::valueFor('email'))) {
                    app(OutboxService::class)->create($email, 'New '.$kind.' request '.$b->reference, ['type' => 'booking', 'booking_id' => $b->id, 'audience' => 'admin']);
                }

                return $b;
            }, 3);
        } catch (UniqueConstraintViolationException $e) {
            // A concurrent retry may commit the same idempotency key first.
            $booking = Booking::where('idempotency_key', $key)->first();
            if (! $booking) {
                throw $e;
            }
            abort_unless(hash_equals($booking->guest_hash, $guestHash), 403);
            abort_unless(hash_equals($booking->payload_hash, hash('sha256', json_encode($d))), 409);
        }
        app(OutboxService::class)->dispatchPending();

        return $booking;
    }
}
