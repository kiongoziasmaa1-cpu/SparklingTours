@extends('layouts.public')
@section('title','Book '.$tour->displayName())
@section('description','Send your contact details to ask about '.$tour->displayName().'.')
@section('content')
<header class="page-intro">
    <div class="container">
        <div class="eyebrow">{{$tour->region->name}} / Tour booking</div>
        <h1>Fill in your contact details</h1>
        <p class="mt-3">Ask our team about availability for <strong>{{$tour->displayName()}}</strong>. We’ll contact you to discuss the details.</p>
    </div>
</header>
<section class="container section-pad">
    <div class="row g-5">
        <div class="col-lg-7">
            <form method="post" action="{{route('tour.contact.submit',$tour->slug)}}" class="form-panel">
                @csrf
                <input type="hidden" name="request_key" value="{{$requestKey}}">
                <h2 class="mb-4">Your contact information</h2>
                <x-input name="name" label="Name" :required="true" maxlength="150" autocomplete="name"/>
                <x-input name="email" label="Email" type="email" :required="true" maxlength="190" autocomplete="email"/>
                <x-input name="phone" label="Phone" type="tel" :required="true" maxlength="35" autocomplete="tel"/>
                <x-input name="country" label="Country" :required="true" maxlength="100" autocomplete="country-name"/>
                <x-input name="traveler_count" label="Number of people" type="number" :required="true" min="1" max="100"/>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="terms" value="1" id="tour-contact-terms" required @checked(old('terms'))>
                    <label class="form-check-label" for="tour-contact-terms">I have read the <a href="{{route('pages.show','privacy')}}">privacy notice</a>.</label>
                </div>
                <div class="hp-field" aria-hidden="true"><input name="website" tabindex="-1" autocomplete="off" aria-label="Leave empty"></div>
                @include('components.captcha',['action'=>'tour_booking'])
                <button class="btn btn-primary" type="submit">Send →</button>
            </form>
        </div>
        <aside class="col-lg-5">
            <div class="sticky-summary">
                <div class="eyebrow">Your selected tour</div>
                <h2>{{$tour->displayName()}}</h2>
                <p>{{$tour->summary}}</p>
                <p class="small mb-0">Your request will be sent to our team. This is an enquiry, not a confirmed reservation. We’ll contact you to discuss availability and travel dates.</p>
            </div>
        </aside>
    </div>
</section>
@endsection
