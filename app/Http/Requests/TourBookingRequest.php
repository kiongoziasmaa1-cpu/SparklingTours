<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TourBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $r = [
            'region_id' => 'required|integer|exists:regions,id', 'tour_id' => 'nullable|integer',
            'service_ids' => 'nullable|array|max:30', 'service_ids.*' => 'integer|distinct',
            'name' => 'required|string|max:150', 'email' => 'required|email:rfc|max:190', 'phone' => 'required|string|min:6|max:35',
            'country' => 'required|string|max:100', 'preferred_contact' => ['required', Rule::in(['email', 'phone', 'whatsapp'])],
            'start_date' => 'required|date_format:Y-m-d|after_or_equal:'.now(config('tourism.timezone'))->toDateString(), 'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'adults' => 'required|integer|min:1|max:100', 'children' => 'required|integer|min:0|max:100',
            'currency' => ['required', Rule::in(['TZS', 'USD'])], 'special_requests' => 'nullable|string|max:3000',
            'needs_accommodation' => 'required|boolean', 'accommodation' => 'exclude_unless:needs_accommodation,1|required|array',
            'accommodation.check_in' => 'exclude_unless:needs_accommodation,1|required|date_format:Y-m-d|after_or_equal:'.now(config('tourism.timezone'))->toDateString(),
            'accommodation.check_out' => 'exclude_unless:needs_accommodation,1|required|date_format:Y-m-d|after:accommodation.check_in',
            'accommodation.adults' => 'exclude_unless:needs_accommodation,1|required|integer|min:1|max:100',
            'accommodation.children' => 'exclude_unless:needs_accommodation,1|required|integer|min:0|max:100',
            'accommodation.rooms' => 'exclude_unless:needs_accommodation,1|required|integer|min:1|max:100',
            'accommodation.child_ages' => 'exclude_unless:needs_accommodation,1|nullable|string|max:200',
            'accommodation.location_details' => 'exclude_unless:needs_accommodation,1|nullable|string|max:300',
            'accommodation.budget' => 'exclude_unless:needs_accommodation,1|nullable|decimal:0,2|min:0|max:9999999999',
            'accommodation.budget_currency' => ['exclude_unless:needs_accommodation,1', 'required', Rule::in(['TZS', 'USD'])],
            'accommodation.notes' => 'exclude_unless:needs_accommodation,1|nullable|string|max:3000',
            'terms' => 'accepted', 'website' => 'nullable|string|max:0',
        ];
        foreach (['type', 'class', 'location', 'room', 'meal'] as $group) {
            $r['accommodation.'.$group.'_id'] = ['exclude_unless:needs_accommodation,1', 'nullable', 'integer', Rule::exists('preference_options', 'id')->where(fn ($q) => $q->where('group', $group)->where('active', true)->where(fn ($q) => $q->whereNull('region_id')->orWhere('region_id', $this->input('region_id'))))];
        }
        $r['accommodation.amenities'] = 'exclude_unless:needs_accommodation,1|nullable|array|max:15';
        $r['accommodation.amenities.*'] = ['integer', Rule::exists('preference_options', 'id')->where(fn ($q) => $q->where('group', 'amenity')->where('active', true)->where(fn ($q) => $q->whereNull('region_id')->orWhere('region_id', $this->input('region_id'))))];

        return $r;
    }
}
