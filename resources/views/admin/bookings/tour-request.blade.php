@extends('layouts.admin')
@section('title',$booking->reference)
@section('content')
<div class="d-flex justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="eyebrow">Tour booking request</div>
        <h1>{{$booking->reference}}</h1>
        <p>Travel dates have not been selected yet · {{$booking->region->name}}</p>
    </div>
    <a class="btn btn-outline-primary" href="{{route('admin.bookings.index')}}">All bookings</a>
</div>
<div class="row g-4">
    <div class="col-xl-8">
        <div class="admin-card">
            <h2>Customer contact</h2>
            <p class="mb-1"><strong>{{$booking->name}}</strong> · {{$booking->country}}</p>
            <p class="mb-1"><a href="mailto:{{$booking->email}}">{{$booking->email}}</a></p>
            <p><a href="tel:{{$booking->phone}}">{{$booking->phone}}</a></p>
            <p><strong>People travelling:</strong> {{$booking->traveler_count}}</p>
            <h2 class="mt-4">Tour requested</h2>
            @foreach($booking->items as $item)
                @php
                    $tour = $item->catalogItem;
                    $price = data_get($item->snapshot, 'package_price', $tour?->is_free ? '0.00' : $tour?->price);
                    $currency = data_get($item->snapshot, 'currency', $tour?->currency ?? $booking->currency);
                    $basis = data_get($item->snapshot, 'pricing_basis', $tour?->pricing_basis);
                    $isFree = data_get($item->snapshot, 'is_free', $tour?->is_free ?? false);
                    $basisLabels = ['per_person' => 'per person', 'per_group' => 'per group', 'per_day' => 'per day', 'per_person_day' => 'per person per day', 'per_person_meal' => 'per person per meal', 'per_unit' => 'per unit'];
                @endphp
                <div class="summary-line">
                    <span><strong>{{$item->description}}</strong>@if($item->snapshot['duration_days']??$tour?->duration_days)<small class="d-block text-muted">{{($item->snapshot['duration_days']??$tour?->duration_days)}} day(s)</small>@endif</span>
                    <span class="text-end">
                        @if($isFree)
                            Free
                        @elseif($price !== null)
                            {{\App\Support\Money::format($price, $currency)}}{{isset($basisLabels[$basis])?' '.$basisLabels[$basis]:''}}
                        @else
                            Quotation required
                        @endif
                        <small class="d-block text-muted">Listed package price; final amount subject to confirmation.</small>
                    </span>
                </div>
            @endforeach
        </div>
    </div>
    <aside class="col-xl-4">
        <div class="admin-card">
            <h2>Manage request</h2>
            <form method="post" action="{{route('admin.bookings.update',$booking)}}">
                @csrf
                @method('PATCH')
                <label class="form-label" for="booking-status">Booking status</label>
                <select class="form-select mb-3" id="booking-status" name="status">
                    @foreach(['submitted','under_review','awaiting_customer','awaiting_payment','confirmed','completed','declined','cancelled'] as $status)
                        <option value="{{$status}}" @selected($booking->status===$status)>{{str_replace('_',' ',$status)}}</option>
                    @endforeach
                </select>
                @if(auth()->user()->isAdmin())
                    <label class="form-label" for="assigned-staff">Assigned staff</label>
                    <select class="form-select mb-3" name="assigned_to" id="assigned-staff">
                        <option value="">Unassigned</option>
                        @foreach($staff as $person)<option value="{{$person->id}}" @selected($booking->assigned_to===$person->id)>{{$person->name}}</option>@endforeach
                    </select>
                @endif
                <input type="hidden" name="availability_confirmed" value="0">
                <label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="availability_confirmed" value="1" @checked($booking->availability_confirmed)><span class="form-check-label">Actual supplier/service availability confirmed.</span></label>
                <label class="form-label">Confirmation terms (required to confirm)</label>
                <textarea class="form-control mb-3" name="confirmation_note" rows="3" maxlength="1000"></textarea>
                <label class="form-label">Internal notes / communication history</label>
                <textarea class="form-control mb-3" name="internal_notes" rows="7" maxlength="6000">{{$booking->internal_notes}}</textarea>
                <button class="btn btn-primary w-100" type="submit">Save booking</button>
            </form>
        </div>
    </aside>
</div>
@endsection
