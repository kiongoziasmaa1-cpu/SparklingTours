<fieldset class="border rounded p-3 mb-3" data-route-fare>
    @php($field = fn ($name, $default = '') => old("fares.$index.$name", $fare[$name] ?? $default))
    @if(!empty($fare['id']))<input type="hidden" name="fares[{{$index}}][id]" value="{{$fare['id']}}">@endif
    <div class="d-flex justify-content-between align-items-center">
        <legend class="float-none w-auto px-2 fs-5">Vehicle and fare</legend>
        <button type="button" class="btn btn-outline-danger btn-sm" data-remove-route-fare>Remove</button>
    </div>
    <div class="row">
        <div class="col-md-6"><x-input name="fares[{{$index}}][vehicle_type_name]" label="Vehicle type name" :required="true" maxlength="150" :value="$field('vehicle_type_name')"/></div>
        <div class="col-md-6 mb-3"><label class="form-label" for="fare-mode-{{$index}}">Travel mode *</label><select class="form-select" name="fares[{{$index}}][mode]" id="fare-mode-{{$index}}" required>@foreach(['road'=>'Road','sea'=>'Sea','air'=>'Air','other'=>'Other'] as $value=>$label)<option value="{{$value}}" @selected($field('mode','road')===$value)>{{$label}}</option>@endforeach</select></div>
        <div class="col-12"><label class="form-label" for="fare-vehicle-description-{{$index}}">Vehicle description (optional)</label><textarea class="form-control mb-3" name="fares[{{$index}}][vehicle_description]" id="fare-vehicle-description-{{$index}}" rows="2" maxlength="3000">{{$field('vehicle_description')}}</textarea></div>
        <div class="col-md-4"><x-input name="fares[{{$index}}][price]" label="One-way price" type="number" :required="true" min="0" step="0.01" :value="$field('price')"/></div>
        <div class="col-md-4"><x-input name="fares[{{$index}}][return_price]" label="Return price (optional)" type="number" min="0" step="0.01" :value="$field('return_price')"/></div>
        <div class="col-md-4 mb-3"><label class="form-label" for="fare-currency-{{$index}}">Currency *</label><select class="form-select" name="fares[{{$index}}][currency]" id="fare-currency-{{$index}}" required>@foreach(['TZS','USD'] as $currency)<option @selected($field('currency','TZS')===$currency)>{{$currency}}</option>@endforeach</select></div>
        <div class="col-md-4 mb-3"><label class="form-label" for="fare-basis-{{$index}}">Fare basis *</label><select class="form-select" name="fares[{{$index}}][pricing_basis]" id="fare-basis-{{$index}}" required>@foreach(['per_vehicle'=>'Per vehicle / trip','per_person'=>'Per person / seat'] as $value=>$label)<option value="{{$value}}" @selected($field('pricing_basis','per_vehicle')===$value)>{{$label}}</option>@endforeach</select></div>
        <div class="col-md-4"><x-input name="fares[{{$index}}][capacity]" label="Passenger capacity" type="number" :required="true" min="1" max="500" :value="$field('capacity',1)"/></div>
        <div class="col-md-4"><x-input name="fares[{{$index}}][luggage_allowance]" label="Luggage bags (optional)" type="number" min="0" max="200" :value="$field('luggage_allowance')"/></div>
        <div class="col-md-4"><x-input name="fares[{{$index}}][minimum_passengers]" label="Minimum passengers" type="number" :required="true" min="1" max="500" :value="$field('minimum_passengers',1)"/></div>
        <div class="col-md-4"><x-input name="fares[{{$index}}][maximum_passengers]" label="Maximum passengers" type="number" :required="true" min="1" max="500" :value="$field('maximum_passengers',100)"/></div>
        <div class="col-md-4"><x-input name="fares[{{$index}}][maximum_vehicles]" label="Maximum vehicles" type="number" :required="true" min="1" max="20" :value="$field('maximum_vehicles',1)"/></div>
        <div class="col-md-6">
            <input type="hidden" name="fares[{{$index}}][multiple_vehicles]" value="0">
            <label class="form-check mt-2"><input class="form-check-input" type="checkbox" name="fares[{{$index}}][multiple_vehicles]" value="1" @checked($field('multiple_vehicles',0))><span class="form-check-label">Allow multiple vehicles when party exceeds capacity</span></label>
        </div>
        <div class="col-md-6">
            <input type="hidden" name="fares[{{$index}}][active]" value="0">
            <label class="form-check mt-2"><input class="form-check-input" type="checkbox" name="fares[{{$index}}][active]" value="1" @checked($field('active',1))><span class="form-check-label">Fare is active and visible to customers</span></label>
        </div>
    </div>
</fieldset>
