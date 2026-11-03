@extends('layouts.admin')
@section('title','Website settings')
@section('content')
<h1>Website settings</h1>
<p>Brand, customer-facing content, photographs and payment instructions.</p>
@if(blank($settings['notification_email']??null) && blank($settings['email']??null))
    <div class="alert alert-warning" role="alert">Add a Booking notification email or Public email to receive new booking requests. Configure SMTP in the hosting environment for real email delivery.</div>
@else
    <p class="small text-muted">New booking requests are emailed to the Booking notification email; if that is blank, they go to the Public email.</p>
@endif
<div class="admin-card">
    <form method="post" enctype="multipart/form-data" action="{{route('admin.settings')}}">
        @csrf
        <div class="row">
            @foreach(['company_name'=>'Company name','email'=>'Public email','notification_email'=>'Booking notification email','phone'=>'Public phone','whatsapp'=>'WhatsApp number','address'=>'Address','social_twitter'=>'Twitter URL','social_instagram'=>'Instagram URL','social_facebook'=>'Facebook URL','hero_button_primary'=>'Primary hero button label','hero_button_secondary'=>'Secondary hero button label','hero_heading'=>'Homepage main heading','usd_tzs_rate'=>'Manual rate: TZS for USD 1','exchange_rate_date'=>'Exchange-rate effective date'] as $name=>$label)
                <div class="col-md-6"><x-input :name="$name" :label="$label" :type="$name==='exchange_rate_date'?'date':'text'" :value="$settings[$name]??''"/></div>
            @endforeach
        </div>
        @foreach([
            'hero'=>['Hero photograph','hero_media_id','hero_image','hero_alt'],
            'about'=>['Homepage about photograph','about_media_id','about_image','about_alt'],
            'fallback'=>['Default photograph for content without its own image','fallback_media_id','fallback_image','fallback_alt'],
            'logo'=>['Company logo','logo_id','logo_image','logo_alt'],
        ] as $slot=>[$label,$mediaField,$imageField,$altField])
            @php($altPlaceholder = $slot === 'logo' ? 'Optional; company name will be used if blank' : 'Image description (required when uploading)')
            <div class="border rounded p-3 mb-3">
                <label class="form-label" for="{{$mediaField}}">{{$label}}</label>
                @if(!empty($settings[$slot.'_path']))
                    <img class="{{$slot === 'logo' ? 'public-site-logo mb-3' : 'card-photo mb-3'}}" src="{{asset($settings[$slot.'_path'])}}" alt="{{$slot === 'logo' ? ($settings['company_name']??'Company logo') : ($settings[$slot.'_alt']??$label)}}">
                @endif
                <select class="form-select mb-3" id="{{$mediaField}}" name="{{$mediaField}}">
                    <option value="">Use uploaded image below or leave blank for default</option>
                    @foreach($media as $photo)
                        <option value="{{$photo->id}}" @selected(($settings[$slot.'_path']??'')===$photo->path)>{{$photo->alt}}</option>
                    @endforeach
                </select>
                <label class="form-label" for="{{$imageField}}">Upload or replace</label>
                <input class="form-control mb-2" type="file" id="{{$imageField}}" name="{{$imageField}}" accept="image/jpeg,image/png,image/gif,image/bmp,image/webp,image/avif">
                <input class="form-control" type="text" name="{{$altField}}" maxlength="190" placeholder="{{$altPlaceholder}}">
            </div>
        @endforeach
        @foreach(['hero_text'=>'Homepage introduction','about_text'=>'Homepage about text','why_text'=>'Why travel with us introduction','payment_instructions'=>'Payment instructions (required before issuing invoices)','invoice_terms'=>'Default invoice / cancellation terms'] as $name=>$label)
            <label class="form-label" for="{{$name}}">{{$label}}</label>
            <textarea class="form-control mb-4" id="{{$name}}" rows="4" name="{{$name}}">{{old($name,$settings[$name]??'')}}</textarea>
        @endforeach
        <input type="hidden" name="demo_mode" value="0">
        <label class="form-check mb-4"><input type="checkbox" class="form-check-input" name="demo_mode" value="1" @checked(($settings['demo_mode']??'1')==='1')><span class="form-check-label">Show the illustrative prices / demo collection banner</span></label>
        <p class="tiny">Mail server credentials, Turnstile keys and production security are configured in the hosting environment, not here.</p>
        <button class="btn btn-primary" type="submit">Save website settings</button>
    </form>
</div>
@endsection
