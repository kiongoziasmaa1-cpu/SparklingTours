<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\CatalogItem;
use App\Models\TransportFare;
use App\Services\TourPricingService;
use App\Services\TransportPricingService;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoBookingSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demonstration requests may only be seeded locally.');
        }
        if (Booking::where('email', 'demo-tour@example.com')->exists()) {
            return;
        }
        $this->call(DatabaseSeeder::class);
        DB::transaction(function () {
            $tour = CatalogItem::where('slug', 'stone-town-and-coast')->firstOrFail();
            $data = ['region_id' => $tour->region_id, 'tour_id' => $tour->id, 'service_ids' => [], 'start_date' => today()->addDays(20)->toDateString(), 'end_date' => today()->addDays(20)->toDateString(), 'adults' => 2, 'children' => 0, 'currency' => 'TZS'];
            $booking = $this->create('tour', 'demo-tour@example.com', $data, app(TourPricingService::class)->quote($data));
            $booking->accommodation()->create(['status' => 'requested', 'preferences' => ['type' => 'Hotel', 'class' => 'Mid-range', 'location' => 'Nungwi', 'room' => 'Double', 'meal' => 'Breakfast', 'rooms' => 1, 'adults' => 2, 'children' => 0, 'check_in' => today()->addDays(19)->toDateString(), 'check_out' => today()->addDays(22)->toDateString(), 'budget_currency' => 'TZS', 'notes' => 'Demonstration preference only.', 'amenities' => ['Wi-Fi']]]);
            $booking->update(['quote_required' => true]);
            $custom = $data;
            unset($custom['tour_id']);
            $custom['service_ids'] = [CatalogItem::where('slug', 'stone-town')->firstOrFail()->id];
            $this->create('tour', 'demo-custom@example.com', $custom, app(TourPricingService::class)->quote($custom));
            $fare = TransportFare::whereHas('vehicleType', fn ($q) => $q->where('name', 'Private car'))->firstOrFail();
            $transfer = ['route_id' => $fare->transport_route_id, 'fare_id' => $fare->id, 'pickup_at' => now()->addDays(21)->format('Y-m-d\TH:i'), 'adults' => 2, 'children' => 0, 'luggage' => 2, 'currency' => 'TZS'];
            $quote = app(TransportPricingService::class)->quote($transfer);
            $b = $this->create('transport', 'demo-transfer@example.com', $transfer, $quote);
            $b->transport()->create(['transport_fare_id' => $fare->id, 'pickup_at' => CarbonImmutable::parse($transfer['pickup_at'], config('tourism.timezone'))->utc(), 'passengers' => 2, 'vehicles' => 1, 'luggage' => 2, 'pickup_details' => 'DEMO airport meeting point', 'dropoff_details' => 'DEMO Stone Town address', 'snapshot' => $quote['snapshot']]);
        });
        // No mail/outbox records are created for these fictitious requests.
    }

    private function create(string $kind, string $email, array $data, array $quote): Booking
    {
        $b = Booking::create(['public_id' => (string) Str::uuid(), 'reference' => 'DEMO-'.strtoupper($kind).'-'.Str::upper(Str::random(8)), 'idempotency_key' => (string) Str::uuid(), 'guest_hash' => hash('sha256', Str::random(32)), 'payload_hash' => hash('sha256', json_encode($data)), 'kind' => $kind, 'source' => $kind === 'transport' ? 'route' : (isset($data['tour_id']) ? 'package' : 'custom'), 'region_id' => $data['region_id'] ?? $quote['region_id'], 'name' => 'DEMO Traveler', 'email' => $email, 'phone' => 'DEMO - not a real number', 'country' => 'Demonstration', 'preferred_contact' => 'email', 'start_date' => $data['start_date'] ?? substr($data['pickup_at'], 0, 10), 'end_date' => $data['end_date'] ?? substr($data['pickup_at'], 0, 10), 'adults' => $data['adults'], 'children' => 0, 'currency' => 'TZS', 'known_subtotal' => Money::decimal($quote['total_minor']), 'quote_required' => $quote['pending'], 'privacy_accepted_at' => now()]);
        $b->items()->createMany($quote['lines']);

        return $b;
    }
}
