@extends('admin.navigation')
@section('content')
<div class="mainSection-title">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
                <div class="d-flex flex-column">
                    <h4>{{ get_phrase('Schedule Live Class') }}</h4>
                    <ul class="d-flex align-items-center eBreadcrumb-2">
                        <li><a href="{{ route('admin.dashboard') }}">{{ get_phrase('Home') }}</a></li>
                        <li><a href="{{ route('admin.live_classes.index') }}">{{ get_phrase('Live Classes') }}</a></li>
                        <li><a href="#">{{ get_phrase('Schedule Class') }}</a></li>
                    </ul>
                </div>
                <a href="{{ route('admin.live_classes.index') }}" class="eBtn eBtn-dark">{{ get_phrase('Back') }}</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="eSection-wrap">
            <form method="POST" action="{{ route('admin.live_classes.store') }}">
                @csrf
                @include('admin.live_class._form')
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="eBtn eBtn-primary">{{ get_phrase('Save') }}</button>
                    <a href="{{ route('admin.live_classes.index') }}" class="eBtn eBtn-dark">{{ get_phrase('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
