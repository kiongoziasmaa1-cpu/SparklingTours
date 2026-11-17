@extends('layouts.public')

@section('content')
<header class="page-intro contact-intro">
    <div class="container">
        <div class="eyebrow">Let’s start a conversation</div>
        <h1>Get in touch with us</h1>
        <p>Tell us what you have in mind. Our local team is ready to help you plan a journey that feels right for you.</p>
    </div>
</header>

<section class="contact-section section-pad">
    <div class="container">
        <div class="row g-4 g-lg-5 align-items-stretch">
            <div class="col-lg-5">
                <div class="contact-details">
                    <div class="eyebrow">Contact our team</div>
                    <h2>Let’s make your trip memorable.</h2>
                    <p class="contact-intro-copy">Share your destination, travel dates or any questions you have. We’ll help you take the next step.</p>

                    <div class="contact-detail-list">
                        @if(!empty($site['email']))
                            <a class="contact-detail" href="mailto:{{$site['email']}}">
                                <span class="contact-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24"><path d="M3 5.5h18v13H3v-13Zm1.5 1.2 7.5 6 7.5-6M4.5 17l5.2-4.2m9.8 4.2-5.2-4.2"/></svg>
                                </span>
                                <span><small>Email us</small><strong>{{$site['email']}}</strong></span>
                            </a>
                        @endif
                        @if(!empty($site['phone']))
                            <a class="contact-detail" href="tel:{{preg_replace('/[^0-9+]/', '', $site['phone'])}}">
                                <span class="contact-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24"><path d="M7.2 3.5h3l1.5 4-2 1.6a15 15 0 0 0 5.2 5.2l1.6-2 4 1.5v3a2 2 0 0 1-2.2 2A16.3 16.3 0 0 1 5.2 5.7a2 2 0 0 1 2-2.2Z"/></svg>
                                </span>
                                <span><small>Call us</small><strong>{{$site['phone']}}</strong></span>
                            </a>
                        @endif
                        @if(!empty($site['address']))
                            <div class="contact-detail">
                                <span class="contact-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24"><path d="M19 10.2c0 5-7 10.3-7 10.3S5 15.2 5 10.2a7 7 0 1 1 14 0Z"/><circle cx="12" cy="10" r="2.3"/></svg>
                                </span>
                                <span><small>Visit us</small><strong>{{$site['address']}}</strong></span>
                            </div>
                        @endif
                    </div>

                    <div class="contact-promise">
                        <span class="contact-promise-mark" aria-hidden="true">✦</span>
                        <span><strong>Personal, local support</strong><small>Thoughtful advice from a team that knows Tanzania.</small></span>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="form-panel contact-form-card">
                    <div class="contact-form-heading">
                        <div>
                            <div class="step-label">Send us a message</div>
                            <h2>How can we help?</h2>
                        </div>
                        <span class="contact-form-mark" aria-hidden="true">✉</span>
                    </div>
                    <p class="contact-form-copy">Complete the form below and our team will be in touch.</p>

                    <form method="post" action="{{route('contact')}}">
                        @csrf
                        <x-input name="name" label="Full name" :required="true" maxlength="150" autocomplete="name"/>
                        <x-input name="email" label="Email address" type="email" :required="true" maxlength="190" autocomplete="email"/>

                        <div class="mb-3">
                            <label class="form-label" for="message">Your message <span aria-hidden="true">*</span></label>
                            <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="5" required minlength="10" maxlength="3000" placeholder="Tell us a little about your plans...">{{old('message')}}</textarea>
                            @error('message')<div class="invalid-feedback">{{$message}}</div>@enderror
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" name="terms" value="1" id="contact-terms" required @checked(old('terms'))>
                            <label class="form-check-label" for="contact-terms">I have read the <a href="{{route('pages.show','privacy')}}">privacy notice</a>.</label>
                            @error('terms')<div class="invalid-feedback">{{$message}}</div>@enderror
                        </div>

                        @include('components.captcha',['action'=>'contact'])
                        <button class="btn btn-primary contact-submit" type="submit">Send your enquiry <span aria-hidden="true">→</span></button>
                        <p class="contact-privacy-note">Your details will only be used to respond to your enquiry.</p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
