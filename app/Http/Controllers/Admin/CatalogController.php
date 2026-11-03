<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\MediaAsset;
use App\Models\Region;
use App\Models\TransportFare;
use App\Models\TransportRoute;
use App\Models\VehicleType;
use App\Services\Audit;
use App\Services\MediaLibrary;
use App\Support\CatalogDefinition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CatalogController extends Controller
{
    public function routesIndex()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('admin.transport.routes.index', [
            'routes' => TransportRoute::with(['region', 'media', 'fares.vehicleType'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function routeForm(?TransportRoute $route = null)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $route ??= new TransportRoute;
        if ($route->exists) {
            $route->load(['region', 'media', 'fares.vehicleType']);
        }

        return view('admin.transport.routes.form', [
            'route' => $route,
            'regions' => Region::all(),
            'media' => MediaAsset::all(),
        ]);
    }

    public function saveRoute(Request $r, ?TransportRoute $route = null)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $route ??= new TransportRoute;
        $d = $r->validate([
            'name' => 'required|string|max:150',
            'region_id' => 'required|integer|exists:regions,id',
            'origin' => 'required|string|max:150',
            'destination' => 'required|string|max:150',
            'duration_minutes' => 'required|integer|min:1|max:10000',
            'description' => 'nullable|string|max:4000',
            'instructions' => 'nullable|string|max:3000',
            'inclusions' => 'nullable|string|max:3000',
            'exclusions' => 'nullable|string|max:3000',
            'cutoff_hours' => 'required|integer|min:0|max:720',
            'active_from' => 'nullable|date',
            'active_until' => 'nullable|date|after_or_equal:active_from',
            'media_asset_id' => 'nullable|integer|exists:media_assets,id',
            'published' => 'required|boolean',
            'image' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:10240|dimensions:max_width=6000,max_height=6000',
            'image_alt' => 'nullable|string|max:190',
            'fares' => 'required|array|min:1|max:20',
            'fares.*.id' => 'nullable|integer|exists:transport_fares,id',
            'fares.*.vehicle_type_name' => 'required|string|max:150|distinct:ignore_case',
            'fares.*.mode' => 'required|in:road,sea,air,other',
            'fares.*.vehicle_description' => 'nullable|string|max:3000',
            'fares.*.price' => 'required|decimal:0,2|min:0|max:9999999999',
            'fares.*.return_price' => 'nullable|decimal:0,2|min:0|max:9999999999',
            'fares.*.currency' => 'required|in:TZS,USD',
            'fares.*.pricing_basis' => 'required|in:per_vehicle,per_person',
            'fares.*.capacity' => 'required|integer|min:1|max:500',
            'fares.*.luggage_allowance' => 'nullable|integer|min:0|max:200',
            'fares.*.minimum_passengers' => 'required|integer|min:1|max:500',
            'fares.*.maximum_passengers' => 'required|integer|min:1|max:500|gte:fares.*.minimum_passengers',
            'fares.*.multiple_vehicles' => 'required|boolean',
            'fares.*.maximum_vehicles' => 'required|integer|min:1|max:20',
            'fares.*.active' => 'required|boolean',
        ], [
            'image.uploaded' => 'The route photo did not reach the server. Choose an image under 10 MB.',
            'fares.*.vehicle_type_name.distinct' => 'Each fare must use a different vehicle type.',
        ]);
        if ($d['published'] && ! collect($d['fares'])->contains(fn ($fare) => (bool) $fare['active'])) {
            return back()->withInput()->withErrors(['published' => 'Add at least one active fare before publishing this route.']);
        }
        foreach ($d['fares'] as $fareData) {
            if ($fareData['pricing_basis'] === 'per_person' && $fareData['multiple_vehicles']) {
                return back()->withInput()->withErrors(['fares' => 'Multiple vehicles can only be enabled for per-vehicle fares.']);
            }
        }
        if ($route->exists) {
            $submittedIds = collect($d['fares'])->pluck('id')->filter()->map(fn ($id) => (int) $id);
            $removedFareIds = $route->fares()->whereNotIn('id', $submittedIds)->select('id');
            if (DB::table('transport_booking_details')->whereIn('transport_fare_id', $removedFareIds)->exists()) {
                return back()->withInput()->withErrors(['fares' => 'A fare used by an existing booking cannot be removed.']);
            }
            $submittedExistingIds = collect($d['fares'])->pluck('id')->filter();
            if ($route->fares()->whereIn('id', $submittedExistingIds)->count() !== $submittedExistingIds->count()) {
                abort(422, 'One or more fares do not belong to this route.');
            }
        }

        if ($r->hasFile('image')) {
            $asset = MediaAsset::create([
                'path' => app(MediaLibrary::class)->store($r->file('image')),
                'alt' => trim((string) ($d['image_alt'] ?? '')) ?: $d['name'],
                'region_id' => $d['region_id'],
                'gallery' => false,
                'sort_order' => 0,
            ]);
            $d['media_asset_id'] = $asset->id;
        }
        unset($d['fares'], $d['image'], $d['image_alt']);

        DB::transaction(function () use ($route, $d, $r): void {
            $route->fill($d);
            if (! $route->exists) {
                $route->slug = Str::slug($d['name']).'-'.Str::lower(Str::random(5));
            }
            $route->save();

            $fareRows = $r->input('fares', []);
            $submittedIds = collect($fareRows)->pluck('id')->filter()->map(fn ($id) => (int) $id);
            $route->fares()->whereNotIn('id', $submittedIds)->delete();

            foreach ($fareRows as $fareData) {
                $vehicle = VehicleType::whereRaw('LOWER(name) = ?', [mb_strtolower(trim($fareData['vehicle_type_name']))])->first();
                if (! $vehicle) {
                    $vehicle = new VehicleType;
                }
                $vehicle->fill([
                    'name' => trim($fareData['vehicle_type_name']),
                    'mode' => $fareData['mode'],
                    'description' => $fareData['vehicle_description'] ?? null,
                    'capacity' => $fareData['capacity'],
                    'luggage_allowance' => $fareData['luggage_allowance'] ?? null,
                    'active' => true,
                ])->save();

                $fare = empty($fareData['id']) ? new TransportFare : $route->fares()->findOrFail($fareData['id']);
                $fare->fill([
                    'vehicle_type_id' => $vehicle->id,
                    'price' => $fareData['price'],
                    'return_price' => $fareData['return_price'] ?? null,
                    'currency' => $fareData['currency'],
                    'pricing_basis' => $fareData['pricing_basis'],
                    'capacity' => $fareData['capacity'],
                    'luggage_allowance' => $fareData['luggage_allowance'] ?? null,
                    'minimum_passengers' => $fareData['minimum_passengers'],
                    'maximum_passengers' => $fareData['maximum_passengers'],
                    'multiple_vehicles' => $fareData['multiple_vehicles'],
                    'maximum_vehicles' => $fareData['maximum_vehicles'],
                    'active' => $fareData['active'],
                ]);
                $route->fares()->save($fare);
            }
            Audit::record('catalog.saved', $route, ['section' => 'routes']);
        });

        return redirect()->route('admin.routes.index')->with('status', 'Route and fares saved successfully.');
    }

    public function destroyRoute(TransportRoute $route)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return $this->destroy('routes', $route->id);
    }

    private function definition(string $type): array
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return CatalogDefinition::get($type);
    }

    public function index(string $type)
    {
        $def = $this->definition($type);
        $q = $def['model']::query();
        if (isset($def['kind'])) {
            $q->where('kind', $def['kind']);
        }

return view('admin.catalog.index', ['type' => $type, 'def' => $def, 'records' => $q->latest()->paginate(20)]);
    }

    public function form(string $type, ?int $id = null)
    {
        $def = $this->definition($type);
        $record = $id ? $def['model']::findOrFail($id) : new $def['model'];
        if ($id && isset($def['kind'])) {
            abort_unless($record->kind === $def['kind'], 404);
        }
        if ($type === 'fares' && $record->exists) {
            $record->load(['route', 'vehicleType']);
        }

        return view('admin.catalog.form', compact('type', 'def', 'record') + ['regions' => Region::all(), 'media' => MediaAsset::all(), 'routes' => TransportRoute::all(), 'vehicles' => VehicleType::all(), 'included' => CatalogItem::where('kind', '!=', 'tour')->get()]);
    }

    public function save(Request $r, string $type, ?int $id = null)
    {
        $def = $this->definition($type);
        $record = $id ? $def['model']::findOrFail($id) : new $def['model'];
        if ($id && isset($def['kind'])) {
            abort_unless($record->kind === $def['kind'], 404);
        }
        $rules = [];
        foreach ($def['fields'] as $name => $f) {
            $rules[$name] = $f[2];
        }
        if (array_key_exists('media_asset_id', $def['fields'])) {
            $rules['image'] = 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:10240|dimensions:max_width=6000,max_height=6000';
            $rules['image_alt'] = 'nullable|string|max:190';
        }
        if ($type === 'pages') {
            $rules['slug'] .= '|'.Rule::unique('pages', 'slug')->ignore($id);
        }
        $d = $r->validate($rules, [
            'image.uploaded' => 'The image did not reach the server. Choose an image under 10 MB and make sure PHP upload_max_filesize is at least 10M and post_max_size is at least 14M.',
            'region_id.required' => 'Please select a region.',
        ]);
        foreach ($def['fields'] as $name => $f) {
            if ($f[1] === 'checkbox') {
                $d[$name] = $r->boolean($name);
            }
        }
        if ($type === 'fares') {
            $route = TransportRoute::whereRaw('LOWER(name) = ?', [mb_strtolower(trim($d['route_name']))])->first();
            if (! $route) {
                return back()->withInput()->withErrors(['route_name' => 'No route matches that name. Create the route first, then enter its exact name here.']);
            }
            $vehicle = VehicleType::whereRaw('LOWER(name) = ?', [mb_strtolower(trim($d['vehicle_type_name']))])->first();
            if (! $vehicle) {
                return back()->withInput()->withErrors(['vehicle_type_name' => 'No vehicle type matches that name. Create the vehicle type first, then enter its exact name here.']);
            }
            $d['transport_route_id'] = $route->id;
            $d['vehicle_type_id'] = $vehicle->id;
            unset($d['route_name'], $d['vehicle_type_name']);
        }
        if (isset($def['kind'])) {
            $d['kind'] = $def['kind'];
            if (! $record->exists) {
                $d['slug'] = Str::slug($d['name']).'-'.Str::lower(Str::random(5));
            }
            if ($d['is_free']) {
                $d['price'] = '0.00';
            }
        }
        if ($type === 'routes') {
            if ($d['published'] && (! $record->exists || ! $record->activeFares()->exists())) {
                return back()->withInput()->withErrors(['published' => 'Save a draft route, add an active fare, then publish it.']);
            }
            if (! $record->exists) {
                $d['slug'] = Str::slug($d['name']).'-'.Str::lower(Str::random(5));
            }
        }
        if ($type === 'fares') {
            $duplicate = $def['model']::where('transport_route_id', $d['transport_route_id'])->where('vehicle_type_id', $d['vehicle_type_id'])->when($id, fn ($q) => $q->where('id', '!=', $id))->exists();
            if ($duplicate) {
                return back()->withInput()->withErrors(['vehicle_type_name' => 'A fare already exists for this route and vehicle type.']);
            }
            if ($d['pricing_basis'] === 'per_person' && $d['multiple_vehicles']) {
                return back()->withInput()->withErrors(['multiple_vehicles' => 'Multiple vehicles applies only to per-vehicle fares.']);
            }
        }
        if ($r->hasFile('image')) {
            $asset = MediaAsset::create([
                'path' => app(MediaLibrary::class)->store($r->file('image')),
                'alt' => trim((string) ($d['image_alt'] ?? '')) ?: $d['name'],
                'region_id' => $d['region_id'] ?? null,
                'gallery' => false,
                'sort_order' => 0,
            ]);
            $d['media_asset_id'] = $asset->id;
        }
        unset($d['image'], $d['image_alt']);
        $record->fill($d)->save();
        Audit::record('catalog.saved', $record, ['section' => $type]);

        return redirect()->route('admin.catalog.index',$type)->with('status','Saved successfully.');
    }

    public function destroy(string $type, int $id)
    {
        $def = $this->definition($type);
        $record = $def['model']::findOrFail($id);
        if (isset($def['kind'])) {
            abort_unless($record->kind === $def['kind'], 404);
        }

        if (isset($def['kind']) && DB::table('booking_items')->where('catalog_item_id', $record->id)->exists()) {
            return back()->withErrors(['delete' => 'This record is used by an existing booking and cannot be deleted.']);
        }

        if (in_array($type, ['routes', 'vehicles'], true)) {
            $fares = TransportFare::query()
                ->where($type === 'routes' ? 'transport_route_id' : 'vehicle_type_id', $record->id);
            $fareIds = $fares->pluck('id');

            if ($fareIds->isNotEmpty() && DB::table('transport_booking_details')->whereIn('transport_fare_id', $fareIds)->exists()) {
                return back()->withErrors(['delete' => 'This record has fares used by existing bookings and cannot be deleted.']);
            }
        }

        DB::transaction(function () use ($def, $record, $type): void {
            if (in_array($type, ['routes', 'vehicles'], true)) {
                TransportFare::query()
                    ->where($type === 'routes' ? 'transport_route_id' : 'vehicle_type_id', $record->id)
                    ->delete();
            }

            if (isset($def['kind'])) {
                CatalogItem::query()->where('id', '!=', $record->id)->get(['id', 'included_item_ids'])->each(function (CatalogItem $item) use ($record): void {
                    $included = $item->included_item_ids ?? [];
                    $remaining = array_values(array_filter($included, fn ($id) => (int) $id !== (int) $record->id));

                    if (count($remaining) !== count($included)) {
                        $item->update(['included_item_ids' => $remaining]);
                    }
                });
            }

            Audit::record('catalog.deleted', $record, ['section' => $type]);
            $record->delete();
        });

        return $type === 'routes'
            ? redirect()->route('admin.routes.index')->with('status', 'Route deleted successfully.')
            : redirect()->route('admin.catalog.index', $type)->with('status', 'Record deleted successfully.');
    }
}
