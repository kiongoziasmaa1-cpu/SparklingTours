@extends('layouts.admin')
@section('title','Photos & galleries')
@section('content')
<h1>Photos &amp; galleries</h1>
<p>Upload photographs you have permission to use. JPG, PNG or WebP, up to 10 MB and 6000 × 6000 pixels. Replacing a library photo updates every place using it.</p>
<div class="admin-card">
    <form method="post" enctype="multipart/form-data" action="{{route('admin.media')}}">
        @csrf
        <div class="row">
            <div class="col-md-4"><x-input name="image" label="Photograph" type="file" :required="true" accept="image/jpeg,image/png,image/webp"/></div>
            <div class="col-md-4"><x-input name="alt" label="Caption / accessible description" :required="true"/></div>
            <div class="col-md-2"><label class="form-label">Region</label><select class="form-select" name="region_id"><option value="">General</option>@foreach($regions as $region)<option value="{{$region->id}}">{{$region->name}}</option>@endforeach</select></div>
            <div class="col-md-2"><x-input name="sort_order" label="Display order" type="number" value="0" min="0" :required="true"/></div>
        </div>
        <label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="gallery" value="1"><span class="form-check-label">Show in region gallery</span></label>
        <button class="btn btn-primary" type="submit">Upload photograph</button>
    </form>
</div>
<div class="admin-card">
    <h2 class="h4">Photo credits images</h2>
    <p>Select a library image for each Photo credits entry. Upload images above first and update the attribution/licence details to match any replacement photograph.</p>
    <form method="post" action="{{route('admin.media.credits')}}">
        @csrf
        <div class="row g-3">
            @foreach($credits as $index=>$credit)
                <div class="col-md-6">
                    <label class="form-label" for="credit-{{$index}}">{{$creditMetadata[$index]['caption']}}</label>
                    @php($creditPreview=$media->firstWhere('id',$creditPhotoIds[$index]??null))
                    <img class="card-photo mb-2" src="{{$creditPreview?->url()??asset('images/'.$credit['filename'])}}" alt="{{$creditMetadata[$index]['caption']}}">
                    <select class="form-select mb-2" id="credit-{{$index}}" name="credit_media_ids[{{$index}}]">
                        <option value="">Use original credited photograph</option>
                        @foreach($media as $photo)
                            <option value="{{$photo->id}}" @selected((string)($creditPhotoIds[$index]??'')===(string)$photo->id)>{{$photo->alt}}</option>
                        @endforeach
                    </select>
                    <details>
                        <summary class="small">Edit photo attribution and licence</summary>
                        <div class="pt-2">
                            @foreach(['title'=>'Photo title','caption'=>'Caption','author'=>'Photographer / source','author_url'=>'Photographer URL','source_url'=>'Original source URL','license'=>'Licence','license_url'=>'Licence URL','changes'=>'Changes made','conditions'=>'Licence conditions'] as $field=>$label)
                                <label class="form-label small" for="credit-{{$index}}-{{$field}}">{{$label}}</label>
                                <textarea class="form-control mb-2" id="credit-{{$index}}-{{$field}}" name="credit_metadata[{{$index}}][{{$field}}]" rows="{{$field==='conditions'||$field==='changes'?2:1}}" required>{{$creditMetadata[$index][$field]}}</textarea>
                            @endforeach
                        </div>
                    </details>
                </div>
            @endforeach
        </div>
        <button class="btn btn-outline-primary mt-3" type="submit">Save credit images</button>
    </form>
</div>
<div class="row g-3">
    @foreach($media as $photo)
        <div class="col-md-4">
            <div class="card">
                <img class="card-photo" src="{{$photo->url()}}" alt="{{$photo->alt}}">
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data" action="{{route('admin.media.update',$photo)}}">
                        @csrf @method('PATCH')
                        <label class="form-label">Replace image (optional)</label>
                        <input class="form-control mb-3" type="file" name="image" accept="image/jpeg,image/png,image/webp">
                        <x-input name="alt" label="Caption" :value="$photo->alt" :required="true"/>
                        <x-input name="sort_order" label="Order" type="number" :value="$photo->sort_order" :required="true" min="0"/>
                        <input type="hidden" name="gallery" value="0">
                        <label class="form-check mb-3"><input type="checkbox" name="gallery" value="1" class="form-check-input" @checked($photo->gallery)><span class="form-check-label">In gallery</span></label>
                        <button class="btn btn-outline-primary btn-sm" type="submit">Save photo</button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
