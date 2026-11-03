@extends('layouts.public')
@section('content')
<section class="hero" data-hero-slideshow>
    <div class="hero-slides" aria-hidden="true">
        <img class="hero-image is-active" data-hero-slide src="{{asset(!empty($site['hero_path'])?$site['hero_path']:(!empty($site['fallback_path'])?$site['fallback_path']:'images/zanzibar-dhow.jpg'))}}" alt="" fetchpriority="high">
        <img class="hero-image" data-hero-slide src="{{asset('images/zanzibar-beach.jpg')}}" alt="" loading="lazy">
        <img class="hero-image" data-hero-slide src="{{asset('images/serengeti-safari-vehicle.jpg')}}" alt="" loading="lazy">
    </div>
<div class="container"><div class="hero-content">
    
    <h1>{{$site['hero_heading']??'Find your kind of Tanzania.'}}</h1><p class="mt-4">{{$site['hero_text']??'From quiet island shores to the wild heart of the mainland, make room for a journey that feels like yours.'}}</p>
    <div class="d-flex flex-wrap gap-3 mt-4"><a class="btn btn-sand" href="{{route('tour.book')}}">{{$site['hero_button_primary']??__('ui.plan')}} ↗</a>
<a class="btn btn-outline-light" href="{{route('tours.index')}}">{{$site['hero_button_secondary']??__('ui.browse_tours')}}</a></div>
</div>
</div>
</section>
<section class="section-pad">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6"><img class="about-img" src="{{asset(!empty($site['about_path'])?$site['about_path']:(!empty($site['fallback_path'])?$site['fallback_path']:'images/tanzania-safari-sunset.jpg'))}}" alt="{{$site['about_alt']??'Safari landscape in Tanzania'}}" loading="lazy">
        </div>
        <div class="col-lg-6">
            <div class="eyebrow">Journeys with a local perspective</div>
            <h2>Less rushing.<br>More remembering.</h2>
            <p class="mt-4">{{$site['about_text']??''}}</p><a href="{{route('pages.show','about')}}" class="btn btn-outline-primary mt-2">Get to know us →</a>
            <div class="feature-line"><span class="pill">Island escapes</span> <span class="pill">Wildlife &amp; landscapes</span> <span class="pill">Culture &amp; connection</span>
        </div>
    </div>
</div>
    </section>
    <section class="section-pad photo-carousel" aria-label="Photo gallery" style="padding-top: 0; margin-top: -40px;">
    <div class="container">
        <div class="photo-carousel-controls"> 
            <h2>Most preferable places</h2>
            <button class="gallery-arrow" type="button" data-gallery-prev aria-label="Scroll photographs left"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg></button>
            <button class="gallery-arrow" type="button" data-gallery-next aria-label="Scroll photographs right"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg></button>
        </div>
        <div class="gallery-track" data-gallery-track>
            @foreach($galleries as $photo)
                <a class="destination-gallery-card" href="{{$photo->url()}}" data-gallery data-caption="{{$photo->alt}}" data-description="{{$photo->alt}}" aria-label="Open photograph details">
                    <img src="{{$photo->url()}}" alt="" loading="lazy">
                </a>
            @endforeach
        </div>
    </div>
</section>
<section class="section-pad why-section">
    <div class="container"><div class="row g-4">
        <div class="col-lg-4">
            <div class="eyebrow">Why travel with us
        </div>
        <h2>Your trip.<br>Thoughtfully arranged.</h2><p class="mt-3">{{$site['why_text']??''}}</p>
    </div>@foreach([['01','Make it personal','Choose the places and experiences that matter to you. We help connect the details.'],['02','Know what comes next','Review your request, receive a clear invoice and confirm your arrangements with our team.'],['03','Travel at your pace','Enjoy a tour or simply arrange the transfer you need. The choice is yours.']] as $feature)<div class="col-md-4 col-lg"><div class="why-card">
        <div class="why-no">{{$feature[0]}}

        </div>
        <h3>{{$feature[1]}}</h3><p class="small mt-3 mb-0">{{$feature[2]}}</p></div></div>@endforeach</div></div></section>
@if(($site['demo_mode']??'1')==='1')
<section class="section-pad testimonials-section" aria-labelledby="testimonials-heading">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="eyebrow">Experiences that speak for themselves</div>
                <h2 id="testimonials-heading">Every journey leaves a beautiful story.</h2>
                <p class="mt-3 mb-0">
                    Discover what makes travelling with Sparkling Tours and Safaris special —
                    thoughtful planning, warm hospitality, local expertise and unforgettable
                    moments across Tanzania and Zanzibar.
                </p>
            </div>
        </div>

@php
    $stories = [
        [
            'quote' => 'From the very first inquiry to the final day of our trip, everything was thoughtfully arranged. The team made us feel welcome, cared for and completely at ease.',
            'name' => 'Amina Hassan',
            'trip' => 'Zanzibar Holiday',
            'location' => 'Zanzibar'
        ],
        [
            'quote' => 'Our safari experience was beyond expectations. The guides were knowledgeable, friendly and passionate about Tanzania. Every moment felt special.',
            'name' => 'James Anderson',
            'trip' => 'Wildlife Safari',
            'location' => 'Mainland Tanzania'
        ],
        [
            'quote' => 'What impressed us most was the personal attention. The team listened to what we wanted, helped us plan the perfect getaway and made our journey truly memorable.',
            'name' => 'Sarah Williams',
            'trip' => 'Custom Travel Experience',
            'location' => 'Tanzania'
        ],
    ];
@endphp

    <div class="row g-4 mt-2">
@foreach ($stories as $story)
    <div class="col-md-4">
        <figure class="testimonial-card" data-testimonial-reveal
            style="--testimonial-delay: {{ $loop->index * 120 }}ms">

            <div class="testimonial-stars" aria-label="Five-star illustrative rating">
                ★★★★★
            </div>

            <blockquote>
                “{{ $story['quote'] }}”
            </blockquote>

            <figcaption>
                <span class="testimonial-mark" aria-hidden="true">“</span>
                <span>
                    <strong>{{ $story['name'] }}</strong>
                    <small>{{ $story['trip'] }} · {{ $story['location'] }}</small>
                </span>
            </figcaption>
        </figure>
    </div>
@endforeach
        </div>
    </div>
</section>
@endif
<div class="modal fade" id="gallery-modal" tabindex="-1" aria-labelledby="gallery-caption" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content"><div class="modal-header"><span id="gallery-caption"></span><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close photograph"></button>
</div>
<div class="modal-body"><img id="gallery-photo" class="gallery-modal-photo" alt="">
    <p id="gallery-description" class="gallery-description mb-0"></p>
</div>
</div>
</div>
</div>
@endsection
<style>
.eyebrow {
    font-family: 'Palatino Linotype', 'Book Antiqua', Georgia, 'Times New Roman', serif;
    font-weight: 600;
    letter-spacing: 0.08em;
}
.photo-carousel{padding-top:18px;padding-bottom:38px}.photo-carousel-controls{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px}.photo-carousel-controls h2{position:relative;margin:0 auto 0 0;padding-bottom:8px;color:var(--forest);font-family:'Palatino Linotype','Book Antiqua',Georgia,serif;font-size:clamp(1.5rem,3vw,2.1rem);font-weight:600;letter-spacing:.015em}.photo-carousel-controls h2::after{position:absolute;bottom:0;left:0;width:52px;height:3px;border-radius:3px;background:var(--sand,#c9a66b);content:''}.gallery-track{display:flex;flex-wrap:nowrap;gap:14px;overflow-x:auto;scroll-snap-type:x mandatory;scroll-behavior:smooth;scrollbar-width:none;padding:2px 1px 10px}.gallery-track::-webkit-scrollbar{display:none}.destination-gallery-card{display:block;position:relative;flex:0 0 190px;height:145px;overflow:hidden;border-radius:12px;background:#dfe5dc;scroll-snap-align:start}.destination-gallery-card img{display:block;width:100%;height:100%;object-fit:cover;transition:transform .35s ease,filter .35s ease}.destination-gallery-card:hover img,.destination-gallery-card:focus-visible img{transform:scale(1.05);filter:brightness(.9)}.gallery-arrow{display:grid;place-items:center;width:38px;height:38px;border:1px solid var(--line);border-radius:50%;background:#fffdf8;color:var(--forest);transition:background-color .2s ease,color .2s ease,transform .2s ease}.gallery-arrow:hover:not(:disabled){background:var(--forest);color:white;transform:translateY(-2px)}.gallery-arrow:disabled{opacity:.4;cursor:not-allowed}.gallery-arrow svg{width:20px;height:20px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}.gallery-description{max-width:760px;margin:18px auto 0;color:var(--muted);text-align:center}.gallery-modal-photo{width:100%;max-height:80vh;object-fit:contain}@media(max-width:575px){.destination-gallery-card{flex-basis:150px;height:115px}}@media(prefers-reduced-motion:reduce){.gallery-track,.destination-gallery-card img,.gallery-arrow{scroll-behavior:auto;transition:none}}
</style>