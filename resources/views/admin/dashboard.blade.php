@extends('layouts.admin')
@section('title','Overview')
@section('content')
<div class="row g-3 mb-4">
    @foreach([['All requests',$total],['Needing review',$pending],['Upcoming journeys',$upcoming]] as [$label,$number])
        <div class="col-md-4"><div class="stat-card"><span class="tiny text-muted d-block">{{$label}}</span><span class="number">{{$number}}</span></div></div>
    @endforeach
</div>
<div class="admin-card">
    <div class="d-flex justify-content-between align-items-center mb-3"><h2>Latest requests</h2><a class="small" href="{{route('admin.bookings.index')}}">View all →</a></div>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Reference</th><th>Customer</th><th>Type</th><th>Travel date</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($bookings as $booking)
                <tr>
                    <td><a href="{{route('admin.bookings.show',$booking)}}">{{$booking->reference}}</a></td>
                    <td>{{$booking->name}}</td>
                    <td>{{$booking->source === 'tour_request' ? 'Tour booking request' : ($booking->source === 'custom' ? 'Plan your trip' : ($booking->source === 'package' ? 'Tour package booking' : 'Transfer request'))}}</td>
                    <td>{{$booking->start_date?->format('d M Y')??'Not provided'}}</td>
                    <td><span class="status-badge">{{str_replace('_',' ',$booking->status)}}</span></td>
                </tr>
            @empty
                <tr><td colspan="5" class="data-empty">New requests will appear here.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="admin-card">
    <h2>Recent invoices</h2>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Invoice</th><th>Lifecycle</th><th>Settlement</th><th>Total</th><th>Balance</th></tr></thead>
            <tbody>
            @forelse($invoices as $i)
                <tr>
                    <td>@if(\Illuminate\Support\Facades\Gate::any(['invoice','payment'],$i->booking))<a href="{{route('admin.invoices.edit',$i)}}">{{$i->number??'Draft #'.$i->id}}</a>@else{{$i->number??'Draft'}}@endif</td>
                    <td>{{$i->status}}</td>
                    <td>{{$i->settlement()}}</td>
                    <td>{{\App\Support\Money::format($i->grand_total,$i->currency)}}</td>
                    <td>{{\App\Support\Money::format($i->balanceMinor(),$i->currency)}}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="data-empty">Prepare an invoice from a reviewed booking.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
