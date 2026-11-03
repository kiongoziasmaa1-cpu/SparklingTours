@extends('layouts.admin')
@section('title', $def['label'])
@section('content')
<div class="eyebrow">{{$def['label']}}</div>
<h1>{{$record->exists ? 'Edit record' : 'Add new record'}}</h1>
@php
    $formDescription = match($type) {
    'destinations' => 'Describe this destination, where it is, and what visitors can expect.',
    'tours' => 'Set up the tour package, its duration, service details, and price.',
    'activities' => 'Describe the activity, where it takes place, and any participation requirements.',
    'food' => 'Add a meal or food experience with its menu details, price, and dietary information.',
    'guides' => 'Add a guide service with languages, coverage areas, experience, and rates.',
    'routes' => 'Save a draft route first, configure its fares, then publish it.',
    default => 'Complete the details for this record.',
    };
@endphp
<p>{{$formDescription}}</p>
<div class="admin-card">
    <form method="post" enctype="multipart/form-data" action="{{$record->exists ? route('admin.catalog.update', [$type, $record->id]) : route('admin.catalog.store', $type)}}">
        @csrf
        @if($record->exists) @method('PATCH') @endif
        <div class="row">
            @foreach($def['fields'] as $name => [$label, $fieldType, $rules])
                @if($name === 'media_asset_id') @continue @endif
                @php
                    $value = old($name, match ($name) {
                        'route_name' => $record->route?->name,
                        'vehicle_type_name' => $record->vehicleType?->name,
                        default => $record->$name,
                    });
                    $required = str_contains($rules, 'required');
                @endphp
                <div class="{{$fieldType === 'textarea' || $fieldType === 'included' ? 'col-12' : 'col-md-6'}} mb-3">
                    @if($fieldType === 'checkbox')
                        <input type="hidden" name="{{$name}}" value="0">
                        <label class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="{{$name}}" value="1" @checked($value)>
                            <span class="form-check-label">{{$label}}</span>
                        </label>
                    @elseif($fieldType === 'textarea')
                        <label class="form-label" for="edit-{{$name}}">
                            {{$label}}@if($required) <span class="text-danger" aria-hidden="true">*</span> <span class="text-muted">(required)</span>@endif
                        </label>
                        <textarea class="form-control @error($name)is-invalid @enderror" name="{{$name}}" id="edit-{{$name}}" rows="4" @required($required) @if($errors->has($name)) aria-invalid="true" aria-describedby="error-{{$name}}" @endif>{{$value}}</textarea>
                    @elseif(in_array($fieldType, ['text', 'number', 'date']))
                        <label class="form-label" for="edit-{{$name}}">
                            {{$label}}@if($required) <span class="text-danger" aria-hidden="true">*</span> <span class="text-muted">(required)</span>@endif
                        </label>
                        <input class="form-control @error($name)is-invalid @enderror" id="edit-{{$name}}" name="{{$name}}" type="{{$fieldType}}" value="{{$value ?? (in_array($name, ['duration_days', 'minimum_passengers', 'maximum_vehicles']) ? 1 : ($name === 'maximum_passengers' ? 100 : ($name === 'duration_minutes' ? 60 : ($name === 'cutoff_hours' ? 0 : ''))))}}" @if($fieldType === 'number')step="any" min="0"@endif @required($required) @if($errors->has($name)) aria-invalid="true" aria-describedby="error-{{$name}}" @endif>
                        @if($type === 'fares' && in_array($name, ['route_name', 'vehicle_type_name'], true))
                            <div class="form-text">Enter the exact name of a route or vehicle type already saved in the admin catalog.</div>
                        @endif
                    @else
                        <label class="form-label" for="edit-{{$name}}">
                            {{$label}}@if($required) <span class="text-danger" aria-hidden="true">*</span> <span class="text-muted">(required)</span>@endif
                        </label>
                        <select class="form-select @error($name)is-invalid @enderror" id="edit-{{$name}}" name="{{$name}}{{$fieldType === 'included' ? '[]' : ''}}" @if($fieldType === 'included')multiple size="6"@endif @required($required) @if($errors->has($name)) aria-invalid="true" aria-describedby="error-{{$name}}" @endif>
                            @if($fieldType !== 'included')
                                <option value="" @selected(blank($value))>{{$fieldType === 'region' && $required ? 'Select a region' : 'Choose / optional'}}</option>
                            @endif
                            @php
                                $choices = match ($fieldType) {
                                    'region' => $regions->pluck('name', 'id')->all(),
                                    'media' => $media->pluck('alt', 'id')->all(),
                                    'route' => $routes->pluck('name', 'id')->all(),
                                    'vehicle' => $vehicles->pluck('name', 'id')->all(),
                                    'included' => $included->pluck('name', 'id')->all(),
                                    'currency' => ['TZS' => 'TZS', 'USD' => 'USD'],
                                    'basis' => ['per_person' => 'Per person', 'per_group' => 'Per group', 'per_day' => 'Per day', 'per_person_day' => 'Per person per day', 'per_person_meal' => 'Per person per meal', 'per_unit' => 'Per unit'],
                                    'food_basis' => ['per_person_meal' => 'Per person per meal', 'per_person' => 'Per person', 'per_group' => 'Per group', 'per_unit' => 'Per item'],
                                    'guide_basis' => ['per_day' => 'Per day', 'per_person_day' => 'Per person per day', 'per_group' => 'Per group', 'per_person' => 'Per person'],
                                    'fare_basis' => ['per_vehicle' => 'Per vehicle / trip', 'per_person' => 'Per person / seat'],
                                    'mode' => ['road' => 'Road', 'sea' => 'Sea', 'air' => 'Air', 'other' => 'Other'],
                                    'preference_group' => ['type' => 'Accommodation type', 'class' => 'Class / star preference', 'location' => 'Location', 'room' => 'Room type', 'meal' => 'Meal plan', 'amenity' => 'Useful amenity'],
                                    default => [],
                                };
                            @endphp
                            @foreach($choices as $key => $text)
                                <option value="{{$key}}" @selected($fieldType === 'included' ? in_array($key, $value ?? []) : (string) $value === (string) $key)>{{$text}}</option>
                            @endforeach
                        </select>
                    @endif
                    @error($name)
                        <div id="error-{{$name}}" class="invalid-feedback d-block" role="alert">{{$message}}</div>
                    @enderror
                </div>
            @endforeach
        </div>
        @if(array_key_exists('media_asset_id', $def['fields']))
            <div class="border rounded p-3 mb-3">
                @if($record->media?->path)
                    <img class="card-photo mb-3" src="{{$record->media->url()}}" alt="{{$record->media->alt}}">
                @endif
                <label class="form-label" for="catalog-image">Upload or replace photograph</label>
                <input class="form-control mb-2" id="catalog-image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
                <label class="form-label" for="catalog-image-alt">Photo description (optional)</label>
                <input class="form-control mb-2" id="catalog-image-alt" name="image_alt" type="text" maxlength="190" value="{{old('image_alt')}}" placeholder="Leave blank to use the record name">
                <div class="form-text">A short image description is helpful for accessibility. If left blank, the record name will be used.</div>
            </div>
        @endif
        <button class="btn btn-primary" type="submit">Save changes</button>
        <a class="btn btn-link" href="{{route('admin.catalog.index', $type)}}">Back to list</a>
    </form>
    @if($record->exists)
        <hr>
        <form method="post" action="{{route('admin.catalog.destroy', [$type, $record->id])}}" onsubmit="return confirm('Delete this record? This cannot be undone.')">
            @csrf
            @method('DELETE')
            <button class="btn btn-outline-danger" type="submit">Delete this record</button>
            <span class="form-text ms-2">This action cannot be undone. Records used by bookings cannot be deleted.</span>
        </form>
    @endif
</div>
@endsection
