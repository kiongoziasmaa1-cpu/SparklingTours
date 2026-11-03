<!doctype html><html lang="{{app()->getLocale()}}"><head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{csrf_token()}}">
    <title>@yield('title', $site['company_name']??'Sparkling Tours and Safaris')</title>
    <meta name="description" content="@yield('description','Discover Tanzania Mainland and Zanzibar. Plan your tour or request a private transfer.')">
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body>
    @php($defaultLogoPath = 'images/sparkling-tours-logo.svg')
<a href="#main" class="visually-hidden-focusable">Skip to content</a>
<div class="demo-strip contact-strip">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex flex-wrap align-items-center gap-3">
            @if(!empty($site['email']))<a href="mailto:{{$site['email']}}">{{$site['email']}}</a>@endif
            @if(!empty($site['phone']))<a href="tel:{{preg_replace('/[^0-9+]/', '', $site['phone'])}}">{{$site['phone']}}</a>@endif
        </div>
        <div class="social-links d-flex align-items-center gap-2" aria-label="Social media">
            @if(!empty($site['whatsapp']))
                <a class="social-link" href="https://wa.me/{{preg_replace('/\D+/', '', $site['whatsapp'])}}" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp" title="WhatsApp">
            @else
                <span class="social-link is-disabled" aria-label="WhatsApp unavailable" title="WhatsApp not configured" aria-disabled="true">
            @endif
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 3.5A11.8 11.8 0 0 0 12.1 0C5.6 0 .3 5.3.3 11.8c0 2.1.6 4.1 1.6 5.9L.2 24l6.5-1.7a11.8 11.8 0 0 0 5.4 1.3h.1c6.5 0 11.8-5.3 11.8-11.8 0-3.1-1.2-6.1-3.5-8.3ZM12.1 21.6c-1.7 0-3.4-.5-4.9-1.3l-.4-.2-3.9 1 1-3.8-.3-.4a9.7 9.7 0 1 1 8.5 4.7Zm5.3-7.3c-.3-.1-1.8-.9-2.1-1-.3-.1-.5-.1-.7.2-.2.3-.8 1-1 1.2-.2.2-.4.2-.7.1-.3-.1-1.3-.5-2.5-1.6-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.5-.6.3-.5c.1-.2 0-.4 0-.6l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4-.3.3-1.2 1.2-1.2 2.8s1.2 3.2 1.4 3.4c.2.2 2.4 3.7 5.8 5.1.8.3 1.4.5 1.9.6.8.3 1.6.2 2.2.1.7-.1 1.8-.7 2-1.4.3-.7.3-1.3.2-1.4-.1-.1-.3-.2-.6-.4Z"/></svg>
            @if(!empty($site['whatsapp']))</a>@else</span>@endif
            @if(!empty($site['social_twitter']))
                <a class="social-link" href="{{$site['social_twitter']}}" target="_blank" rel="noopener noreferrer" aria-label="Twitter" title="Twitter / X">
            @else
                <span class="social-link is-disabled" aria-label="Twitter unavailable" title="Twitter not configured" aria-disabled="true">
            @endif
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M23.4 4.8a9.2 9.2 0 0 1-2.7.7 4.7 4.7 0 0 0 2-2.6 9.3 9.3 0 0 1-3 1.1 4.6 4.6 0 0 0-8 3.2c0 .4 0 .7.1 1.1a13.1 13.1 0 0 1-9.5-4.8 4.6 4.6 0 0 0 1.4 6.2 4.5 4.5 0 0 1-2.1-.6v.1a4.6 4.6 0 0 0 3.7 4.5 4.6 4.6 0 0 1-2.1.1 4.6 4.6 0 0 0 4.3 3.2A9.3 9.3 0 0 1 0 19a13.1 13.1 0 0 0 7.1 2.1c8.5 0 13.1-7 13.1-13.1v-.6a9.4 9.4 0 0 0 2.3-2.4Z"/></svg>
            @if(!empty($site['social_twitter']))</a>@else</span>@endif
            @if(!empty($site['social_facebook']))
                <a class="social-link" href="{{$site['social_facebook']}}" target="_blank" rel="noopener noreferrer" aria-label="Facebook" title="Facebook">
            @else
                <span class="social-link is-disabled" aria-label="Facebook unavailable" title="Facebook not configured" aria-disabled="true">
            @endif
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.5 24V13.1H17l.5-4.2h-4V6.2c0-1.2.3-2 2-2H18V.4C17.2.3 16.1.2 14.8.2c-3.3 0-5.6 2-5.6 5.8v2.9H5.5v4.2h3.7V24h4.3Z"/></svg>
            @if(!empty($site['social_facebook']))</a>@else</span>@endif
            @if(!empty($site['social_instagram']))
                <a class="social-link" href="{{$site['social_instagram']}}" target="_blank" rel="noopener noreferrer" aria-label="Instagram" title="Instagram">
            @else
                <span class="social-link is-disabled" aria-label="Instagram unavailable" title="Instagram not configured" aria-disabled="true">
            @endif
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5Zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H7Zm5 3a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6Zm5.5-3a1.2 1.2 0 1 1 0 2.4 1.2 1.2 0 0 1 0-2.4Z"/></svg>
            @if(!empty($site['social_instagram']))</a>@else</span>@endif
        </div>
    </div>
</div>
<nav class="navbar navbar-expand-lg">
    <div class="container"><a class="navbar-brand" href="{{route('home')}}">
        <img src="{{asset(!empty($site['logo_path']) ? $site['logo_path'] : $defaultLogoPath)}}" alt="" class="public-site-logo">
    <span>{{$site['company_name']??'Sparkling Tours'}}
        <span class="brand-sub">Tanzania &amp; Zanzibar</span>
    </span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navigation" aria-controls="navigation" aria-expanded="false" aria-label="Open navigation">
        <span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="navigation">
        <ul class="navbar-nav ms-auto align-items-lg-center"><li class="nav-item"><a class="nav-link" href="{{route('home')}}">{{__('ui.home')}}</a></li><li class="nav-item"><a class="nav-link" href="{{route('pages.show','about')}}">{{__('ui.about')}}

    </a>
</li>@foreach($navigationRegions as $navRegion)
<li class="nav-item dropdown">
    <button class="nav-link dropdown-toggle border-0 bg-transparent" type="button" data-bs-toggle="dropdown" aria-expanded="false">{{$navRegion->name}}</button><ul class="dropdown-menu">@foreach(['destinations','tours','activities','food','travel-guide'] as $category)<li><a class="dropdown-item" href="{{route('catalog.index',[$navRegion->slug,$category])}}">{{__('ui.'.$category)}}</a>
</li>@endforeach</ul></li>@endforeach<li class="nav-item"><a class="nav-link" href="{{route('transport.index')}}">{{__('ui.transport')}}</a></li><li class="nav-item"><a class="nav-link" href="{{route('contact')}}">{{__('ui.contact')}}</a></li><li class="nav-item ms-lg-2"><form method="post" action="{{route('language',app()->getLocale()==='en'?'sw':'en')}}">@csrf<button class="btn btn-sm btn-outline-primary" type="submit">{{app()->getLocale()==='en'?'SW':'EN'}}</button></form></li></ul></div></div></nav>
<main id="main">@include('components.messages')@yield('content')

</main>
<footer class="site-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-5">
            <div class="serif fs-3 mb-3">{{$site['company_name']??'Sparkling Tours'}}</div>
            <p class="col-lg-9">Thoughtful journeys, local insight and a little room for discovery. Explore Tanzania and Zanzibar at your own pace.</p></div>
            <div class="col-6 col-lg-2"><h6>Explore</h6><ul class="list-unstyled"><li><a href="{{route('tours.index')}}">{{__('ui.tours')}}</a></li><li><a href="{{route('tour.book')}}">{{__('ui.plan')}}</a></li><li><a href="{{route('transport.index')}}">{{__('ui.transport')}}</a></li></ul>
        </div>
        <div class="col-6 col-lg-2"><h6>Information</h6>
        <ul class="list-unstyled"><li><a href="{{route('pages.show','about')}}">{{__('ui.about')}}</a></li>
        <li><a href="{{route('pages.show','privacy')}}">Privacy policy</a></li>
        <li><a href="{{route('pages.show','terms')}}">Terms &amp; conditions</a></li>
        </ul>
    </div>
    <div class="col-lg-3"><h6>Let’s plan something</h6>
    @if(!empty($site['email']))<a href="mailto:{{$site['email']}}">{{$site['email']}}</a>
    @endif @if(!empty($site['phone']))
    <p class="mt-2 mb-1">{{$site['phone']}}</p>@endif
    <p>{{$site['address']??''}}</p>
    <a href="{{route('contact')}}">Send an enquiry</a>
    <div class="social-links d-flex align-items-center gap-2" aria-label="Social media">
            @if(!empty($site['whatsapp']))
                <a class="social-link" href="https://wa.me/{{preg_replace('/\D+/', '', $site['whatsapp'])}}" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp" title="WhatsApp">
            @else
                <span class="social-link is-disabled" aria-label="WhatsApp unavailable" title="WhatsApp not configured" aria-disabled="true">
            @endif
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 3.5A11.8 11.8 0 0 0 12.1 0C5.6 0 .3 5.3.3 11.8c0 2.1.6 4.1 1.6 5.9L.2 24l6.5-1.7a11.8 11.8 0 0 0 5.4 1.3h.1c6.5 0 11.8-5.3 11.8-11.8 0-3.1-1.2-6.1-3.5-8.3ZM12.1 21.6c-1.7 0-3.4-.5-4.9-1.3l-.4-.2-3.9 1 1-3.8-.3-.4a9.7 9.7 0 1 1 8.5 4.7Zm5.3-7.3c-.3-.1-1.8-.9-2.1-1-.3-.1-.5-.1-.7.2-.2.3-.8 1-1 1.2-.2.2-.4.2-.7.1-.3-.1-1.3-.5-2.5-1.6-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.5-.6.3-.5c.1-.2 0-.4 0-.6l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4-.3.3-1.2 1.2-1.2 2.8s1.2 3.2 1.4 3.4c.2.2 2.4 3.7 5.8 5.1.8.3 1.4.5 1.9.6.8.3 1.6.2 2.2.1.7-.1 1.8-.7 2-1.4.3-.7.3-1.3.2-1.4-.1-.1-.3-.2-.6-.4Z"/></svg>
            @if(!empty($site['whatsapp']))</a>@else</span>@endif
            @if(!empty($site['social_twitter']))
                <a class="social-link" href="{{$site['social_twitter']}}" target="_blank" rel="noopener noreferrer" aria-label="Twitter" title="Twitter / X">
            @else
                <span class="social-link is-disabled" aria-label="Twitter unavailable" title="Twitter not configured" aria-disabled="true">
            @endif
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M23.4 4.8a9.2 9.2 0 0 1-2.7.7 4.7 4.7 0 0 0 2-2.6 9.3 9.3 0 0 1-3 1.1 4.6 4.6 0 0 0-8 3.2c0 .4 0 .7.1 1.1a13.1 13.1 0 0 1-9.5-4.8 4.6 4.6 0 0 0 1.4 6.2 4.5 4.5 0 0 1-2.1-.6v.1a4.6 4.6 0 0 0 3.7 4.5 4.6 4.6 0 0 1-2.1.1 4.6 4.6 0 0 0 4.3 3.2A9.3 9.3 0 0 1 0 19a13.1 13.1 0 0 0 7.1 2.1c8.5 0 13.1-7 13.1-13.1v-.6a9.4 9.4 0 0 0 2.3-2.4Z"/></svg>
            @if(!empty($site['social_twitter']))</a>@else</span>@endif
            @if(!empty($site['social_facebook']))
                <a class="social-link" href="{{$site['social_facebook']}}" target="_blank" rel="noopener noreferrer" aria-label="Facebook" title="Facebook">
            @else
                <span class="social-link is-disabled" aria-label="Facebook unavailable" title="Facebook not configured" aria-disabled="true">
            @endif
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.5 24V13.1H17l.5-4.2h-4V6.2c0-1.2.3-2 2-2H18V.4C17.2.3 16.1.2 14.8.2c-3.3 0-5.6 2-5.6 5.8v2.9H5.5v4.2h3.7V24h4.3Z"/></svg>
            @if(!empty($site['social_facebook']))</a>@else</span>@endif
            @if(!empty($site['social_instagram']))
                <a class="social-link" href="{{$site['social_instagram']}}" target="_blank" rel="noopener noreferrer" aria-label="Instagram" title="Instagram">
            @else
                <span class="social-link is-disabled" aria-label="Instagram unavailable" title="Instagram not configured" aria-disabled="true">
            @endif
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5Zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H7Zm5 3a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6Zm5.5-3a1.2 1.2 0 1 1 0 2.4 1.2 1.2 0 0 1 0-2.4Z"/></svg>
            @if(!empty($site['social_instagram']))</a>@else</span>@endif
        </div>
</div>
</div><div class="footer-bottom d-flex justify-content-between flex-wrap gap-2"><span>© {{date('Y')}} {{$site['company_name']??'Sparkling Tours'}}.</span>
<a href="{{auth()->check()?route('admin.dashboard'):route('login')}}">Staff access</a>
</div>
</div>
</footer>
</body>
</html>
