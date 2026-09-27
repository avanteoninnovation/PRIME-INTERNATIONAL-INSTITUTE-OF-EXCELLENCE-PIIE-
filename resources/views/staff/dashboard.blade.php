@extends('admin.navigation')

@section('content')
<div class="mainSection-title">
    <div class="row"><div class="col-12">
        <h4>{{ get_phrase('Staff Workspace') }}</h4>
        <p>{{ get_phrase('Welcome') }}, {{ auth()->user()->name }}</p>
    </div></div>
</div>
<div class="eSection-wrap">
    <h5>{{ get_phrase('Your available work') }}</h5>
    @if(count($links))
        <ul class="list-group">
            @foreach($links as $link)
                <li class="list-group-item"><a href="{{ $link['url'] }}">{{ get_phrase($link['label']) }}</a></li>
            @endforeach
        </ul>
    @else
        <p class="text-muted mb-0">{{ get_phrase('No additional application access has been assigned. Contact an administrator to request access.') }}</p>
    @endif
</div>
@endsection
