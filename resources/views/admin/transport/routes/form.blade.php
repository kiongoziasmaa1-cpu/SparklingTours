@extends('layouts.admin')
@section('title',$route->exists ? 'Edit route' : 'Add route')
@section('content')
<div class="eyebrow">Transport routes</div>
<h1>{{$route->exists ? 'Edit route' : 'Add new route'}}</h1>
<p>Route details and active fares are shown to customers on the transport page.</p>
@php
    $fareRows = old('fares') ?? ($route->exists ? $route->fares->map(fn ($fare) => [
        'id' => $fare->id,
        'vehicle_type_name' => $fare->vehicleType->name,
        'mode' => $fare->vehicleType->mode,
        'vehicle_description' => $fare->vehicleType->description,
        'price' => $fare->price,
        'return_price' => $fare->return_price,
        'currency' => $fare->currency,
        'pricing_basis' => $fare->pricing_basis,
        'capacity' => $fare->capacity,
        'luggage_allowance' => $fare->luggage_allowance,
        'minimum_passengers' => $fare->minimum_passengers,
        'maximum_passengers' => $fare->maximum_passengers,
        'multiple_vehicles' => $fare->multiple_vehicles,
        'maximum_vehicles' => $fare->maximum_vehicles,
        'active' => $fare->active,
    ])->all() : [[
        'vehicle_type_name' => '',
        'mode' => 'road',
        'vehicle_description' => '',
        'price' => '',
        'return_price' => '',
        'currency' => 'TZS',
        'pricing_basis' => 'per_vehicle',
        'capacity' => 1,
        'luggage_allowance' => '',
        'minimum_passengers' => 1,
        'maximum_passengers' => 100,
        'multiple_vehicles' => 0,
        'maximum_vehicles' => 1,
        'active' => 1,
    ]]);
@endphp
<div class="admin-card">
    <form method="post" enctype="multipart/form-data" action="{{$route->exists ? route('admin.routes.update',$route) : route('admin.routes.store')}}">
        @csrf
        @if($route->exists) @method('PATCH') @endif
        <h2>Route details</h2>
        <div class="row">
            <div class="col-md-6"><x-input name="name" label="Route name" :required="true" maxlength="150" :value="old('name',$route->name)"/></div>
            <div class="col-md-6 mb-3"><label class="form-label" for="route-region">Region *</label><select class="form-select @error('region_id')is-invalid @enderror" name="region_id" id="route-region" required><option value="">Choose a region</option>@foreach($regions as $region)<option value="{{$region->id}}" @selected(old('region_id',$route->region_id)==$region->id)>{{$region->name}}</option>@endforeach</select>@error('region_id')<div class="invalid-feedback d-block">{{$message}}</div>@enderror</div>
            <div class="col-md-6"><x-input name="origin" label="Origin area" :required="true" maxlength="150" :value="old('origin',$route->origin)"/></div>
            <div class="col-md-6"><x-input name="destination" label="Destination area" :required="true" maxlength="150" :value="old('destination',$route->destination)"/></div>
            <div class="col-md-6"><x-input name="duration_minutes" label="Approximate journey minutes" type="number" :required="true" min="1" max="10000" :value="old('duration_minutes',$route->duration_minutes??60)"/></div>
            <div class="col-md-6"><x-input name="cutoff_hours" label="Minimum advance booking (hours)" type="number" :required="true" min="0" max="720" :value="old('cutoff_hours',$route->cutoff_hours??0)"/></div>
            <div class="col-12"><label class="form-label" for="route-description">Description shown to travelers</label><textarea class="form-control" id="route-description" name="description" rows="3" maxlength="4000">{{old('description',$route->description)}}</textarea></div>
            <div class="col-md-6"><label class="form-label" for="route-instructions">Pickup / drop-off instructions</label><textarea class="form-control" id="route-instructions" name="instructions" rows="3" maxlength="3000">{{old('instructions',$route->instructions)}}</textarea></div>
            <div class="col-md-6"><label class="form-label" for="route-inclusions">Included services</label><textarea class="form-control" id="route-inclusions" name="inclusions" rows="3" maxlength="3000">{{old('inclusions',$route->inclusions)}}</textarea></div>
            <div class="col-md-6"><label class="form-label" for="route-exclusions">Excluded services</label><textarea class="form-control" id="route-exclusions" name="exclusions" rows="3" maxlength="3000">{{old('exclusions',$route->exclusions)}}</textarea></div>
            <div class="col-md-3"><x-input name="active_from" label="Active from (optional)" type="date" :value="old('active_from',$route->active_from?->toDateString())"/></div>
            <div class="col-md-3"><x-input name="active_until" label="Active until (optional)" type="date" :value="old('active_until',$route->active_until?->toDateString())"/></div>
        </div>
        <div class="border rounded p-3 my-3">
            @if($route->media?->path)<img class="card-photo mb-3" src="{{$route->media->url()}}" alt="{{$route->media->alt}}">@endif
            <label class="form-label" for="route-image">Route photo</label>
            <input class="form-control mb-2" id="route-image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
            <label class="form-label" for="route-image-alt">Photo description (optional)</label>
            <input class="form-control" id="route-image-alt" name="image_alt" maxlength="190" value="{{old('image_alt')}}" placeholder="Leave blank to use the route name">
        </div>
        <div class="form-check mb-4">
            <input type="hidden" name="published" value="0">
            <input class="form-check-input" type="checkbox" id="route-published" name="published" value="1" @checked(old('published',$route->published??false))>
            <label class="form-check-label" for="route-published">Publish this route on the transport page</label>
            @error('published')<div class="text-danger small">{{$message}}</div>@enderror
        </div>
        <hr>
        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
            <div><h2 class="mb-1">Vehicle types and fares</h2><p class="small mb-0">Each active fare appears as an option on the public transport page and booking form.</p></div>
            <button type="button" class="btn btn-outline-primary btn-sm text-nowrap" data-add-route-fare>Add vehicle fare</button>
        </div>
        @error('fares')<div class="alert alert-danger">{{$message}}</div>@enderror
        <div data-route-fares>
            @foreach($fareRows as $index => $fare)
                @include('admin.transport.routes.fare-fields',['index'=>$index,'fare'=>$fare])
            @endforeach
        </div>
        <template id="route-fare-template">@include('admin.transport.routes.fare-fields',['index'=>'__INDEX__','fare'=>[]])</template>
        <button class="btn btn-primary mt-3" type="submit">Save route and fares</button>
        <a class="btn btn-link mt-3" href="{{route('admin.routes.index')}}">Back to routes</a>
    </form>
    @if($route->exists)
        <hr>
        <form method="post" action="{{route('admin.routes.destroy',$route)}}" onsubmit="return confirm('Delete this route and its fares? This cannot be undone.')">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger" type="submit">Delete route</button>
            <span class="form-text ms-2">Routes with existing bookings cannot be deleted.</span>
        </form>
    @endif
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const list = document.querySelector('[data-route-fares]');
    const template = document.querySelector('#route-fare-template');
    const addButton = document.querySelector('[data-add-route-fare]');
    if (!list || !template || !addButton) return;
    addButton.addEventListener('click', () => {
        const index = Date.now().toString() + Math.random().toString(36).slice(2);
        list.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', index));
    });
    list.addEventListener('click', event => {
        const button = event.target.closest('[data-remove-route-fare]');
        if (button && list.querySelectorAll('[data-route-fare]').length > 1) button.closest('[data-route-fare]').remove();
    });
});
</script>
@endsection
