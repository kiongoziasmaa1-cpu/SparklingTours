@extends('layouts.public')

@section('title', 'Gallery')

@section('content')
<section class="gallery-page section-pad">
    <div class="container">
        <h1 class="gallery-heading">Gallery</h1>
        <div class="gallery-photo-grid">
            @foreach($galleries->flatten(1) as $photo)
                <a class="gallery-photo-card" href="{{$photo->url()}}" data-gallery data-caption="{{$photo->alt}}" data-description="" aria-label="View {{$photo->alt}}">
                    <img src="{{$photo->url()}}" alt="{{$photo->alt}}" loading="lazy">
                </a>
            @endforeach
        </div>
    </div>
</section>

<div class="modal fade" id="gallery-modal" tabindex="-1" aria-labelledby="gallery-caption" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <span id="gallery-caption"></span>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close photograph"></button>
            </div>
            <div class="modal-body">
                <img id="gallery-photo" class="gallery-modal-photo" alt="">
                <p id="gallery-description" class="gallery-description mb-0"></p>
            </div>
        </div>
    </div>
</div>
@endsection
