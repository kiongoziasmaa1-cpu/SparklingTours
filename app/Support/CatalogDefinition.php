<?php

namespace App\Support;

use App\Models\CatalogItem;
use App\Models\Page;
use App\Models\PreferenceOption;
use App\Models\TransportFare;
use App\Models\TransportRoute;
use App\Models\VehicleType;

class CatalogDefinition
{
    public static function get(string $type): array
    {
        $kinds = ['destinations' => 'destination', 'tours' => 'tour', 'activities' => 'activity', 'food' => 'food', 'guides' => 'guide'];
        $common = ['name' => ['Name', 'text', 'required|string|max:150'], 'region_id' => ['Region', 'region', 'required|integer|exists:regions,id'], 'media_asset_id' => ['Photograph', 'media', 'nullable|integer|exists:media_assets,id']];
        if (isset($kinds[$type])) {
            $fields = array_merge($common, [
                'name_sw' => ['Kiswahili name', 'text', 'nullable|string|max:150'], 'summary' => ['Short introduction', 'textarea', 'nullable|string|max:600'],
                'description' => ['Full description', 'textarea', 'nullable|string|max:15000'], 'location' => ['Location', 'text', 'nullable|string|max:190'],
                'price' => ['Unit price (leave blank for quotation)', 'number', 'nullable|decimal:0,2|min:0|max:9999999999'],
                'currency' => ['Currency', 'currency', 'required|in:TZS,USD'], 'pricing_basis' => ['Price basis', 'basis', 'required|in:per_person,per_group,per_day,per_person_day,per_person_meal,per_unit'],
                'is_free' => ['This service is explicitly free', 'checkbox', 'boolean'], 'published' => ['Published', 'checkbox', 'boolean'],
                'duration_days' => ['Tour duration in days', 'number', 'required|integer|min:1|max:365'],
                'inclusions' => ['What is included (one per line)', 'textarea', 'nullable|string|max:3000'], 'exclusions' => ['What is excluded (one per line)', 'textarea', 'nullable|string|max:3000'],
                'requirements' => ['Age / participation requirements', 'textarea', 'nullable|string|max:2000'], 'languages' => ['Guide languages', 'text', 'nullable|string|max:150'],
            ]);

            if ($kinds[$type] === 'food') {
                $fields['name'][0] = 'Food or meal name';
                $fields['summary'][0] = 'Short menu description';
                $fields['description'][0] = 'Meal and menu details';
                $fields['location'][0] = 'Where the meal is served';
                $fields['price'][0] = 'Meal price (leave blank for quotation)';
                $fields['pricing_basis'] = ['Meal price basis', 'food_basis', 'required|in:per_person_meal,per_person,per_group,per_unit'];
                $fields['inclusions'][0] = 'What comes with the meal (one per line)';
                $fields['exclusions'][0] = 'Optional extras (one per line)';
                $fields['requirements'][0] = 'Dietary and allergy information';
                unset($fields['languages']);
                unset($fields['duration_days']);
            } elseif ($kinds[$type] === 'guide') {
                $fields['name'][0] = 'Guide service name';
                $fields['summary'][0] = 'Short guide profile';
                $fields['description'][0] = 'Guide service description';
                $fields['location'][0] = 'Areas covered';
                $fields['price'][0] = 'Guide rate (leave blank for quotation)';
                $fields['pricing_basis'] = ['Guide rate basis', 'guide_basis', 'required|in:per_day,per_person_day,per_group,per_person'];
                $fields['inclusions'][0] = 'What the guide service includes (one per line)';
                $fields['exclusions'][0] = 'What is not included (one per line)';
                $fields['requirements'][0] = 'Experience and qualifications';
                $fields['languages'][0] = 'Languages spoken';
                unset($fields['duration_days']);
            }

            return ['model' => CatalogItem::class, 'kind' => $kinds[$type], 'label' => ucfirst($type), 'fields' => $fields];
        }

        return match ($type) {
            'vehicles' => ['model' => VehicleType::class, 'label' => 'Vehicle types', 'fields' => [
                'name' => ['Type name', 'text', 'required|string|max:150'], 'mode' => ['Travel mode', 'mode', 'required|in:road,sea,air,other'],
                'description' => ['Description', 'textarea', 'nullable|string|max:3000'], 'media_asset_id' => ['Illustrative photograph', 'media', 'nullable|integer|exists:media_assets,id'],
                'capacity' => ['Passenger capacity', 'number', 'required|integer|min:1|max:500'], 'luggage_allowance' => ['Luggage bags per vehicle (blank if unspecified)', 'number', 'nullable|integer|min:0|max:200'], 'active' => ['Active', 'checkbox', 'boolean']]],
            'routes' => ['model' => TransportRoute::class, 'label' => 'Transport routes', 'fields' => array_merge($common, [
                'origin' => ['Origin area', 'text', 'required|string|max:150'], 'destination' => ['Destination area', 'text', 'required|string|max:150'],
                'duration_minutes' => ['Approximate journey minutes', 'number', 'required|integer|min:1|max:10000'], 'description' => ['Description', 'textarea', 'nullable|string|max:4000'],
                'instructions' => ['Pickup/drop-off instructions', 'textarea', 'nullable|string|max:3000'], 'inclusions' => ['Included', 'textarea', 'nullable|string|max:3000'], 'exclusions' => ['Excluded', 'textarea', 'nullable|string|max:3000'],
                'cutoff_hours' => ['Minimum advance booking (hours)', 'number', 'required|integer|min:0|max:720'], 'active_from' => ['Active from (optional)', 'date', 'nullable|date'], 'active_until' => ['Active until (optional)', 'date', 'nullable|date|after_or_equal:active_from'], 'published' => ['Published (requires an active fare)', 'checkbox', 'boolean']])],
            'fares' => ['model' => TransportFare::class, 'label' => 'Route fares', 'fields' => [
                'route_name' => ['Route name', 'text', 'required|string|max:150'], 'vehicle_type_name' => ['Vehicle type', 'text', 'required|string|max:150'],
                'price' => ['Outbound unit price', 'number', 'required|decimal:0,2|min:0|max:9999999999'], 'return_price' => ['Return unit price (blank to disable return)', 'number', 'nullable|decimal:0,2|min:0|max:9999999999'],
                'currency' => ['Currency', 'currency', 'required|in:TZS,USD'], 'pricing_basis' => ['Fare basis', 'fare_basis', 'required|in:per_vehicle,per_person'],
                'capacity' => ['Capacity per vehicle / craft', 'number', 'required|integer|min:1|max:500'], 'luggage_allowance' => ['Bags per vehicle / craft (optional)', 'number', 'nullable|integer|min:0|max:200'],
                'minimum_passengers' => ['Minimum passengers', 'number', 'required|integer|min:1|max:500'], 'maximum_passengers' => ['Maximum passengers', 'number', 'required|integer|min:1|max:500|gte:minimum_passengers'],
                'multiple_vehicles' => ['Permit multiple private vehicles', 'checkbox', 'boolean'], 'maximum_vehicles' => ['Maximum vehicles', 'number', 'required|integer|min:1|max:20'], 'active' => ['Active fare', 'checkbox', 'boolean']]],
            'preferences' => ['model' => PreferenceOption::class, 'label' => 'Accommodation preference options', 'fields' => [
                'group' => ['Preference category', 'preference_group', 'required|in:type,class,location,room,meal,amenity'], 'label' => ['Customer-facing label', 'text', 'required|string|max:150'], 'region_id' => ['Region (blank for both)', 'region', 'nullable|integer|exists:regions,id'], 'active' => ['Active option', 'checkbox', 'boolean']]],
            'pages' => ['model' => Page::class, 'label' => 'Information pages', 'fields' => [
                'title' => ['Page title', 'text', 'required|string|max:150'], 'slug' => ['Page address', 'text', 'required|alpha_dash:ascii|max:120'], 'body' => ['Content', 'textarea', 'required|string|max:30000'], 'meta_description' => ['Search description', 'textarea', 'nullable|string|max:190'], 'published' => ['Published', 'checkbox', 'boolean']]],
            default => abort(404),
        };
    }
}
