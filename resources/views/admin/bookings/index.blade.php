@extends('layouts.admin')
@section('title','Bookings')
@section('content')
<h1>Bookings</h1>
<p>Tour requests, accommodation arrangements and independent transfers.</p>
<div class="admin-card">
    <form class="row g-2 mb-4" method="get">
        <div class="col-md-3"><label class="form-label" for="booking-q">Reference or customer</label><input class="form-control" id="booking-q" name="q" value="{{request('q')}}"></div>
        <div class="col-md-2"><label class="form-label" for="kind">Type</label><select class="form-select" name="kind" id="kind"><option value="">All types</option>@foreach(['tour','transport'] as $kind)<option value="{{$kind}}" @selected(request('kind')===$kind)>{{ucfirst($kind)}}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label" for="status-filter">Status</label><select class="form-select" name="status" id="status-filter"><option value="">All statuses</option>@foreach(['submitted','under_review','awaiting_customer','awaiting_payment','confirmed','completed','declined','cancelled'] as $status)<option value="{{$status}}" @selected(request('status')===$status)>{{str_replace('_',' ',$status)}}</option>@endforeach</select></div>
        <div class="col-md-2"><x-input name="from" label="Travel from" type="date" :value="request('from')"/></div>
        <div class="col-md-2"><x-input name="to" label="Travel to" type="date" :value="request('to')"/></div>
        <div class="col-md-1 align-self-center"><button class="btn btn-primary btn-sm" type="submit">Filter</button></div>
    </form>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Reference</th><th>Customer</th><th>Type</th><th>Travel</th><th>Status</th><th>Assigned</th><th>View</th></tr></thead>
            <tbody>
            @forelse($bookings as $b)
                <tr>
                    <td><a href="{{route('admin.bookings.show',$b)}}">{{$b->reference}}</a></td>
                    <td>{{$b->name}}<span class="tiny text-muted d-block">{{$b->email}}</span></td>
                    <td>{{$b->source === 'tour_request' ? 'Tour booking request' : ($b->source === 'custom' ? 'Plan your trip' : ($b->source === 'package' ? 'Tour package booking' : 'Transfer request'))}}</td>
                    <td>{{$b->start_date?->format('d M Y')??'Not provided'}}</td>
                    <td><span class="status-badge">{{str_replace('_',' ',$b->status)}}</span></td>
                    <td>{{$b->assignee?->name??'Unassigned'}}</td>
                    <td><a class="btn btn-outline-primary btn-sm" href="{{route('admin.bookings.show',$b)}}">View</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="data-empty">No requests match these filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{$bookings->links()}}
</div>
@endsection
