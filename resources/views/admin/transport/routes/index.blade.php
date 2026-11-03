@extends('layouts.admin')
@section('title','Routes')
@section('content')
<div class="d-flex justify-content-between align-items-start gap-3 mb-4">
    <div><h1>Transport routes</h1><p>Manage routes, vehicle types, fares, availability, and the details shown to travelers.</p></div>
    <a class="btn btn-primary" href="{{route('admin.routes.create')}}">Add new route →</a>
</div>
<div class="admin-card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Route</th><th>Region</th><th>Duration</th><th>Vehicle types and fares</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse($routes as $route)
                <tr>
                    <td><strong>{{$route->origin}} → {{$route->destination}}</strong><span class="tiny text-muted d-block">{{$route->name}}</span></td>
                    <td>{{$route->region->name}}</td>
                    <td>{{$route->duration_minutes}} min</td>
                    <td>
                        @forelse($route->fares as $fare)
                            <div>{{$fare->vehicleType->name}} — {{\App\Support\Money::format($fare->price,$fare->currency)}} {{str_replace('_',' ',$fare->pricing_basis)}} @unless($fare->active)<span class="text-muted">(inactive)</span>@endunless</div>
                        @empty
                            <span class="text-muted">No fares</span>
                        @endforelse
                    </td>
                    <td>{{$route->published ? 'Published' : 'Draft'}}</td>
                    <td class="text-nowrap">
                        <a class="btn btn-outline-primary btn-sm" href="{{route('admin.routes.edit',$route)}}">Edit</a>
                        <form method="post" action="{{route('admin.routes.destroy',$route)}}" class="d-inline" onsubmit="return confirm('Delete this route and its fares? This cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="data-empty">No routes have been added yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{$routes->links()}}
</div>
@endsection
