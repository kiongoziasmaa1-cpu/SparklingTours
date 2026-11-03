@if(session('status'))<div class="container mt-3"><div class="alert alert-success" role="status">{{session('status')}}</div></div>@endif
@if($errors->any())<div class="container mt-3"><div class="alert alert-danger" role="alert"><strong>Please check the following:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{$error}}</li>@endforeach</ul></div></div>@endif
