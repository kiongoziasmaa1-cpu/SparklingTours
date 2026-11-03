@extends('layouts.public')
@section('title',$page->title)
@section('description',$page->meta_description??'Travel information and booking arrangements.')
@section('content')
<header class="page-intro">
    <div class="container">
        <div class="eyebrow">{{$site['company_name']??'Sparkling Tours'}}</div>
        <h1>{{$page->title}}</h1>
    </div>
</header>

@if($page->slug === 'about')
    <section class="container section-pad about-page">
        <div class="row align-items-center g-5 mb-5">
            <div class="col-lg-6">
                <img
                    class="about-page-photo"
                    src="{{asset(!empty($site['about_path']) ? $site['about_path'] : (!empty($site['fallback_path']) ? $site['fallback_path'] : 'images/tanzania-safari-sunset.jpg'))}}"
                    alt="{{$site['about_alt']??'Tanzanian landscape at sunset'}}"
                    loading="lazy"
                >
            </div>
            
<div class="col-lg-6">
    <h2>Who We Are.</h2>

    <p class="mt-4">
        Sparkling Tours and Safaris is a Tanzania-based travel and tour
        company dedicated to helping travellers discover the beauty,
        culture and unforgettable experiences of Tanzania and Zanzibar.
        We are passionate about creating meaningful travel experiences
        that connect visitors with the country's remarkable destinations,
        rich traditions, wildlife and beautiful coastlines.
    </p>

    <p>
        We offer travel planning and tourism services, including
        safari experiences, Zanzibar excursions, cultural tours,
        beach holidays, accommodation guidance and customised
        travel arrangements. Whether you are looking for a relaxing
        island escape, an exciting wildlife adventure or an opportunity
        to explore local communities, we help you find experiences
        that match your interests, preferences and budget.
    </p>

    <p>
        Our goal is to make travelling easier, more enjoyable and
        more rewarding through personalised assistance, local destination
        knowledge, thoughtful planning and a commitment to customer care.
        From your first enquiry to the planning of your journey,
        we aim to provide helpful guidance and a welcoming experience
        at every step.
    </p>

    <p>
        At Sparkling Tours and Safaris, we believe that every journey
        should tell a story. Let us help you discover Tanzania,
        one unforgettable experience at a time.
    </p>

    <a class="btn btn-primary mt-2" href="{{ route('contact') }}">
        Discover Your Next Journey →
    </a>
</div>

        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <article class="about-page-panel h-100">
                    <div class="eyebrow">Our mission</div>
                    <h2>Make planning feel clear and personal.</h2>
                    <p class="mb-0">Our mission is to help travelers explore Tanzania through useful local guidance, clear communication and travel arrangements shaped around each guest’s interests. We review every request with care and confirm services, availability and costs before arrangements are finalized.</p>
                </article>
            </div>
            <div class="col-md-6">
                <article class="about-page-panel h-100">
                    <div class="eyebrow">Our vision</div>
                    <h2>Help every guest feel welcome.</h2>
                    <p class="mb-0">Our vision is for visitors to experience Tanzania with curiosity, respect and a sense of connection—to its places, people, cultures and natural landscapes—and to take home memories they are glad to share.</p>
                </article>
            </div>
        </div>

        <div class="about-page-services">
            <div class="eyebrow">How we can help</div>
            <h2>Travel services</h2>
            <p class="mb-4">Explore the services available through our website and send us a request. Our team can discuss the details and check availability with relevant providers.</p>
            <div class="row g-3">
                <div class="col-sm-6 col-lg-4"><div class="about-service-card"><h3>Tour planning</h3><p class="mb-0">Browse published tours or tell us what you would like to include in a trip.</p></div></div>
                <div class="col-sm-6 col-lg-4"><div class="about-service-card"><h3>Private transport</h3><p class="mb-0">Request a vehicle for an available route, with trip details reviewed by our team.</p></div></div>
                <div class="col-sm-6 col-lg-4"><div class="about-service-card"><h3>Guides and activities</h3><p class="mb-0">Explore listed guide services and experiences, subject to availability and agreed arrangements.</p></div></div>
                <div class="col-sm-6 col-lg-4"><div class="about-service-card"><h3>Places and culture</h3><p class="mb-0">Discover destinations, local food experiences and travel information across the site.</p></div></div>
                <div class="col-sm-6 col-lg-4"><div class="about-service-card"><h3>Accommodation requests</h3><p class="mb-0">For eligible tour requests, share your stay preferences so our team can discuss suitable options.</p></div></div>
                <div class="col-sm-6 col-lg-4"><div class="about-service-card"><h3>Helpful guidance</h3><p class="mb-0">Ask questions about your request and the next steps before confirming arrangements.</p></div></div>
            </div>
        </div>

        <div class="about-page-welcome text-center mt-5">
            <h2>Let’s start planning your Tanzania experience.</h2>
            <p class="mx-auto mt-3">We look forward to welcoming you. Get in touch with Sparkling Tours and Safaris, share what you have in mind, and let our team guide you through the options.</p>
            <a class="btn btn-primary mt-2" href="{{route('contact')}}">Contact us →</a>
        </div>
    </section>
@else
    <section class="container section-pad">
        <div class="row">
            <div class="col-lg-8">
                <div class="detail-copy">{{$page->body}}</div>
                <a class="btn btn-outline-primary mt-4" href="{{route('contact')}}">Talk to our team →</a>
            </div>
        </div>
    </section>
@endif
@endsection
<style>
.about-page-photo{width:100%;height:430px;object-fit:cover;border-radius:16px}
.about-page-panel{padding:clamp(24px,4vw,40px);background:#fffdf8;border:1px solid var(--line);border-radius:16px}
.about-page-panel h2{font-size:clamp(1.7rem,2.7vw,2.3rem)}
.about-page-services>p,.about-page-panel p,.about-page-welcome p{max-width:760px;color:var(--muted)}
.about-service-card{height:100%;padding:22px;background:#fffdf8;border:1px solid var(--line);border-radius:12px}
.about-service-card h3{font-size:1.15rem}
.about-service-card p{font-size:13px;color:var(--muted)}
.about-page-welcome{padding:clamp(28px,5vw,55px);background:#eaeedf;border-radius:18px}
.about-page-welcome p{max-width:680px}
@media(max-width:767px){.about-page-photo{height:300px}}
</style>
