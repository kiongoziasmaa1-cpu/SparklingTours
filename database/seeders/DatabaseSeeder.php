<?php

namespace Database\Seeders;

use App\Models\CatalogItem;
use App\Models\MediaAsset;
use App\Models\Page;
use App\Models\PreferenceOption;
use App\Models\Region;
use App\Models\SiteSetting;
use App\Models\TransportFare;
use App\Models\TransportRoute;
use App\Models\VehicleType;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);

        $z = Region::firstOrCreate(['slug' => 'zanzibar'], ['name' => 'Zanzibar', 'description' => 'The islands and Swahili coast.']);
        $m = Region::firstOrCreate(['slug' => 'mainland'], ['name' => 'Mainland', 'description' => 'Tanzania Mainland wildlife, landscapes and culture.']);
        $photos = [['zanzibar-dhow.jpg', 'A dhow on the Zanzibar coast', $z->id], ['zanzibar-beach.jpg', 'Zanzibar coastline', $z->id], ['stone-town.jpg', 'Stone Town architecture', $z->id], ['serengeti-safari-vehicle.jpg', 'Safari in the Serengeti (illustrative vehicle)', $m->id], ['kilimanjaro-moshi.jpg', 'Kilimanjaro viewed from Moshi', $m->id], ['serengeti-lion.jpg', 'A lion in the Serengeti', $m->id], ['tanzania-safari-sunset.jpg', 'Tanzanian safari landscape', $m->id]];
        $media = [];
        foreach ($photos as $i => [$file,$alt,$region]) {
            $media[$file] = MediaAsset::firstOrCreate(['path' => 'images/'.$file], ['alt' => $alt, 'region_id' => $region, 'gallery' => true, 'sort_order' => $i])->id;
        }
        $make = function ($slug, $kind, $name, $region, $price, $photo, $extra = []) use ($media) {
            return CatalogItem::firstOrCreate(['slug' => $slug], array_merge(['kind' => $kind, 'name' => $name, 'region_id' => $region, 'summary' => 'An illustrative experience to help you explore your travel options.', 'description' => 'Take time to discover the places, people and landscapes that make this part of Tanzania memorable. Our team can discuss the details and arrangements with you. This is sample content; availability and final services are reviewed after a request.', 'price' => $price, 'currency' => 'TZS', 'pricing_basis' => 'per_person', 'published' => true, 'duration_days' => 1, 'media_asset_id' => $media[$photo]], $extra));
        };
        $stone = $make('stone-town', 'destination', 'Stone Town', $z->id, null, 'stone-town.jpg', ['location' => 'Unguja, Zanzibar', 'requirements' => 'Walking routes and entrance fees will be confirmed for the selected itinerary.']);
        $coast = $make('zanzibar-coast', 'destination', 'Zanzibar Coast', $z->id, '0.00', 'zanzibar-beach.jpg', ['is_free' => true, 'location' => 'Unguja coast']);
        $serengeti = $make('serengeti', 'destination', 'Serengeti landscapes', $m->id, null, 'serengeti-lion.jpg');
        $kilimanjaro = $make('kilimanjaro', 'destination', 'Kilimanjaro foothills', $m->id, null, 'kilimanjaro-moshi.jpg');
        $guide = $make('zanzibar-guide', 'guide', 'Local cultural guide', $z->id, '120000.00', 'stone-town.jpg', ['pricing_basis' => 'per_day', 'languages' => 'English, Kiswahili']);
        $meal = $make('zanzibar-lunch', 'food', 'Zanzibar lunch experience', $z->id, '25000.00', 'zanzibar-beach.jpg', ['pricing_basis' => 'per_person_meal', 'requirements' => 'Tell us about dietary requirements when submitting your request.']);
        $boat = $make('coastal-boat-experience', 'activity', 'Coastal dhow experience', $z->id, '60000.00', 'zanzibar-dhow.jpg', ['requirements' => 'Marine conditions and suitable participation requirements will be checked by the operator.']);
        $safariguide = $make('mainland-guide', 'guide', 'Mainland safari guide', $m->id, '150000.00', 'serengeti-safari-vehicle.jpg', ['pricing_basis' => 'per_day', 'languages' => 'English, Kiswahili']);
        $make('stone-town-and-coast', 'tour', 'Stone Town & island rhythm', $z->id, '160000.00', 'stone-town.jpg', ['itinerary' => 'Day 1: Discover Stone Town, then enjoy time on the coast.', 'inclusions' => "Local guide\nSelected walking experiences", 'exclusions' => "Accommodation\nMeals unless added separately\nStandalone airport transfers", 'included_item_ids' => [$guide->id]]);
        $make('zanzibar-island-discovery', 'tour', 'A slower Zanzibar escape', $z->id, '340000.00', 'zanzibar-dhow.jpg', ['duration_days' => 2, 'itinerary' => "Day 1: Stone Town and local culture.\nDay 2: Coastal scenery and a dhow experience.", 'inclusions' => "Guided experiences\nConfigured dhow activity", 'exclusions' => "Accommodation\nMeals\nAirport transfers", 'included_item_ids' => [$boat->id, $guide->id]]);
        $make('serengeti-sample-safari', 'tour', 'The Serengeti chapter', $m->id, '2400000.00', 'serengeti-safari-vehicle.jpg', ['duration_days' => 4, 'itinerary' => "Day 1: Arrival and introductory landscapes.\nDay 2: Wildlife exploration.\nDay 3: Further safari experiences.\nDay 4: Return arrangements.", 'inclusions' => "Safari guide\nTour transport as agreed", 'exclusions' => "Accommodation pending arrangement\nFlights\nServices outside the agreed itinerary", 'included_item_ids' => [$safariguide->id]]);
        $make('kilimanjaro-foothills-discovery', 'tour', 'At the foot of Kilimanjaro', $m->id, '280000.00', 'kilimanjaro-moshi.jpg', ['itinerary' => 'Day 1: A guided foothills and landscape experience near Moshi.', 'inclusions' => 'Guide service', 'exclusions' => 'Accommodation and transfers outside the published itinerary', 'included_item_ids' => [$safariguide->id]]);
        $car = VehicleType::firstOrCreate(['name' => 'Private car'], ['mode' => 'road', 'capacity' => 4, 'luggage_allowance' => 3, 'description' => 'Illustrative private road transfer option.']);
        $van = VehicleType::firstOrCreate(['name' => 'Minivan'], ['mode' => 'road', 'capacity' => 8, 'luggage_allowance' => 8, 'description' => 'Illustrative group road transfer option.']);
        $ferry = VehicleType::firstOrCreate(['name' => 'Boat transfer'], ['mode' => 'sea', 'capacity' => 20, 'luggage_allowance' => 20, 'description' => 'Illustrative marine transfer; subject to operator and sea conditions.', 'media_asset_id' => $media['zanzibar-dhow.jpg']]);
        $airport = TransportRoute::firstOrCreate(['slug' => 'airport-stone-town'], ['region_id' => $z->id, 'name' => 'Zanzibar Airport to Stone Town', 'origin' => 'Zanzibar Airport', 'destination' => 'Stone Town', 'duration_minutes' => 25, 'description' => 'A direct transfer within the stated airport and town areas.', 'instructions' => 'Provide your arrival flight number and the drop-off address within Stone Town.', 'inclusions' => 'Driver and standard route transfer', 'exclusions' => 'Unagreed stops or changes of route', 'cutoff_hours' => 2, 'published' => true]);
        foreach ([[$car, '80000.00', 4, 3, true, 3], [$van, '120000.00', 8, 8, false, 1]] as [$type,$price,$cap,$bags,$multiple,$max]) {
            TransportFare::firstOrCreate(['transport_route_id' => $airport->id, 'vehicle_type_id' => $type->id], ['price' => $price, 'return_price' => $price, 'currency' => 'TZS', 'pricing_basis' => 'per_vehicle', 'capacity' => $cap, 'luggage_allowance' => $bags, 'multiple_vehicles' => $multiple, 'maximum_vehicles' => $max, 'maximum_passengers' => $cap * $max]);
        }
        $north = TransportRoute::firstOrCreate(['slug' => 'stone-town-nungwi'], ['region_id' => $z->id, 'name' => 'Stone Town to Nungwi', 'origin' => 'Stone Town', 'destination' => 'Nungwi', 'duration_minutes' => 90, 'description' => 'A north-coast road transfer.', 'instructions' => 'Provide pickup and drop-off addresses within the stated areas.', 'cutoff_hours' => 4, 'published' => true]);
        TransportFare::firstOrCreate(['transport_route_id' => $north->id, 'vehicle_type_id' => $van->id], ['price' => '160000.00', 'currency' => 'TZS', 'pricing_basis' => 'per_vehicle', 'capacity' => 8, 'maximum_passengers' => 8, 'luggage_allowance' => 8]);
        $sea = TransportRoute::firstOrCreate(['slug' => 'illustrative-boat-transfer'], ['region_id' => $z->id, 'name' => 'Configured island boat transfer', 'origin' => 'Stone Town departure point', 'destination' => 'Agreed island landing point', 'duration_minutes' => 45, 'description' => 'Illustrative route: the operator must replace this with a specific service before publication for real bookings.', 'instructions' => 'Departure and landing arrangements require operator confirmation.', 'cutoff_hours' => 24]);
        TransportFare::firstOrCreate(['transport_route_id' => $sea->id, 'vehicle_type_id' => $ferry->id], ['price' => '30000.00', 'currency' => 'TZS', 'pricing_basis' => 'per_person', 'capacity' => 20, 'maximum_passengers' => 20, 'luggage_allowance' => 20]);
        foreach (['type' => ['Hotel', 'Resort', 'Boutique hotel', 'Guesthouse', 'Apartment', 'Villa'], 'class' => ['Budget', 'Mid-range', 'Luxury', '3-star preference', '4-star preference', '5-star preference'], 'room' => ['Single', 'Double', 'Twin', 'Family'], 'meal' => ['Room only', 'Breakfast', 'Half board', 'Full board', 'All inclusive'], 'amenity' => ['Beachfront', 'Swimming pool', 'Air conditioning', 'Wi-Fi', 'Accessible room']] as $group => $labels) {
            foreach ($labels as $label) {
                PreferenceOption::firstOrCreate(['group' => $group, 'label' => $label], ['active' => true]);
            }
        }
        foreach (['Stone Town', 'Nungwi', 'Paje', 'Jambiani'] as $label) {
            PreferenceOption::firstOrCreate(['group' => 'location', 'label' => $label], ['region_id' => $z->id]);
        }
        foreach (['Arusha', 'Moshi', 'Dar es Salaam'] as $label) {
            PreferenceOption::firstOrCreate(['group' => 'location', 'label' => $label], ['region_id' => $m->id]);
        }
        $defaults = ['company_name' => 'Sparkling Tours and Safaris', 'email' => '', 'notification_email' => '', 'phone' => '', 'whatsapp' => '', 'address' => '', 'hero_heading' => 'Find your kind of Tanzania.', 'hero_text' => 'From quiet island shores to the wild heart of the mainland, make room for a journey that feels like yours.', 'about_text' => 'Discover the coast, culture and wide-open landscapes of Tanzania with a journey shaped around you. Choose a published tour or build your own plan, and let our team help arrange the details.', 'why_text' => 'A thoughtful trip begins with a clear conversation. Tell us your interests and we’ll help put the right pieces together.', 'payment_instructions' => '', 'invoice_terms' => 'Prices, supplier availability and cancellation conditions must be agreed before final confirmation.', 'demo_mode' => '1'];
        foreach ($defaults as $key => $value) {
            SiteSetting::firstOrCreate(['key' => $key],['value' => $value]);
        }
        foreach ([
            'about' => ['About Sparkling Tours and Safaris', "Travel should feel personal.\n\nThis website helps you discover Tanzania Mainland and Zanzibar, explore published tours, and plan your own itinerary. You can request help arranging accommodation at the end of a tour booking, or book an independent transport route.\n\nOur team reviews each request, checks availability, and sends an invoice for the agreed services. This demonstration content should be replaced with the operator’s own verified business story and contacts before launch."],
            'privacy' => ['Privacy notice', "We use the contact details, travel information and preferences you submit to review your request, communicate with you and arrange agreed services. Necessary booking details may be shared with the relevant hotel or transport provider.\n\nAccess is limited to authorized staff and protected customer links. Please avoid submitting passport scans or card details through the initial form.\n\nThe operator must publish its real contact address, retention policy, service-provider disclosures and procedures for privacy requests before launch. This is editable draft policy text for the operator to complete."],
            'terms' => ['Booking terms', "A submitted form is a request for review. It does not by itself confirm a hotel room, vehicle or tour. Availability, final services, price, deposit and cancellation conditions will be communicated by the operator.\n\nAccommodation preferences help our guide source a suitable stay. Any unquoted accommodation is excluded from the tour services subtotal. Independent transport is arranged on the selected route and vehicle basis.\n\nPay using the agreed invoice instructions. Staff verify payments before updating the ledger. Supplier confirmation and the agreed payment conditions determine final reservation status.\n\nThe operator must replace this editable draft with its actual cancellation, refund, delay and supplier terms before accepting real customers."],
        ] as $slug => [$title,$body]) {
            Page::firstOrCreate(['slug' => $slug],['title' => $title, 'body' => $body, 'published' => true]);
        }
    }
}
