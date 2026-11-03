@extends('layouts.admin')
@section('title',$def['label'])
@section('content')
<div class="d-flex justify-content-between mb-4">
    <div><h1>{{$def['label']}}</h1><p>Manage content, availability settings and prices.</p></div>
    <a class="btn btn-primary align-self-start" href="{{route('admin.catalog.create', $type)}}">Add new →</a>
</div>
<div class="admin-card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Record</th><th>Status / details</th><th></th></tr></thead>
            <tbody>
                @forelse($records as $record)
                    <tr>
                        <td>{{$record->name ?? $record->title ?? $record->label ?? ($type === 'fares' ? $record->route->name.' / '.$record->vehicleType->name : '#'.$record->id)}}</td>
                        <td>
                            @if(isset($record->price)){{\App\Support\Money::format($record->price, $record->currency)}} @endif
                            @if(isset($record->published)){{$record->published ? 'Published' : 'Draft'}} @elseif(isset($record->active)){{$record->active ? 'Active' : 'Inactive'}} @endif
                        </td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-outline-primary btn-sm" href="{{route('admin.catalog.edit', [$type, $record->id])}}">Edit</a>
                            <form method="post" action="{{route('admin.catalog.destroy', [$type, $record->id])}}" class="d-inline" onsubmit="return confirm('Delete this record? This cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="data-empty">No records yet. Add your first one.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{$records->links()}}
</div>
@endsection
