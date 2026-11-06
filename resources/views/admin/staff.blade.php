@extends('layouts.admin')
@section('title','Staff & permissions')
@section('content')
<h1>Staff &amp; permissions</h1>
<p>Guides only see assigned requests. Grant invoicing and payment verification separately.</p>
@foreach($staff->concat([new \App\Models\User]) as $person)
<div class="admin-card">
    <h2>{{$person->exists?$person->name:'Create staff account'}}</h2>
    <form method="post" action="{{$person->exists?route('admin.staff.update',$person):route('admin.staff.create')}}">@csrf @if($person->exists)@method('PATCH')@endif
        <div class="row">
            <div class="col-md-6">
                <x-input name="name" label="Full name" :value="$person->name" :required="true"/></div><div class="col-md-6"><x-input name="email" label="Email" type="email" :value="$person->email" :required="true"/>
            </div>
            <div class="col-md-6">
                <x-input name="password" label="Password (12+ characters; blank keeps existing)" type="password" autocomplete="new-password"/>
            </div>
            <div class="col-md-6">
                <x-input name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password"/></div><div class="col-md-4 mb-3"><label class="form-label">Role</label>
                <select class="form-select" name="role">
                    <option value="guide">Guide / staff</option>
                    <option value="admin" @selected($person->role==='admin')>Administrator</option></select></div></div>@foreach(['active'=>'Account active','can_invoice'=>'Can prepare and issue invoices','can_verify_payments'=>'Can verify payments and refunds'] as $field=>$label)
                    <input type="hidden" name="{{$field}}" value="0">
                    <label class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="{{$field}}" value="1" @checked($person->$field)>
                        <span class="form-check-label">{{$label}}</span>
                    </label>@endforeach<div class="mt-3">
                        <button class="btn btn-primary btn-sm" type="submit">Save staff account</button><span class="tiny ms-3">Authenticator: {{$person->two_factor_confirmed_at?'enabled':'enrolment required'}}</span>
                    </div>
                </form>
            </div>
            @endforeach @endsection
