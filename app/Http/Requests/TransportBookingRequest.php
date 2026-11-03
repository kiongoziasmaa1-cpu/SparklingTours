<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransportBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'route_id' => 'required|integer', 'fare_id' => 'required|integer', 'pickup_at' => 'required|date_format:Y-m-d\TH:i',
            'return_at' => 'nullable|date_format:Y-m-d\TH:i|after:pickup_at',
            'adults' => 'required|integer|min:1|max:100', 'children' => 'required|integer|min:0|max:100',
            'luggage' => 'required|integer|min:0|max:200', 'pickup_details' => 'required|string|max:250', 'dropoff_details' => 'required|string|max:250',
            'arrival_number' => 'nullable|string|max:60', 'name' => 'required|string|max:150', 'email' => 'required|email:rfc|max:190',
            'phone' => 'required|string|min:6|max:35', 'country' => 'required|string|max:100',
            'preferred_contact' => ['required', Rule::in(['email', 'phone', 'whatsapp'])],
            'currency' => ['required', Rule::in(['TZS', 'USD'])], 'special_requests' => 'nullable|string|max:3000', 'terms' => 'accepted', 'website' => 'nullable|string|max:0',
        ];
    }
}
