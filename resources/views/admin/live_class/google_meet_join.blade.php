@extends(request()->routeIs('teacher.*') ? 'teacher.navigation' : (request()->routeIs('student.*') ? 'student.navigation' : 'admin.navigation'))
@section('content')
@php
    $routePrefix = request()->routeIs('teacher.*') ? 'teacher' : (request()->routeIs('student.*') ? 'student' : 'admin');
    $isStaff = (int) auth()->user()->role_id !== 7;
@endphp

<div class="mainSection-title">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
                <div class="d-flex flex-column">
                    <h4>{{ get_phrase('Join Google Meet') }}</h4>
                    <ul class="d-flex align-items-center eBreadcrumb-2">
                        <li><a href="{{ route($routePrefix . '.live_classes.index') }}">{{ get_phrase('Live Classes') }}</a></li>
                        <li><a href="#">{{ $liveClass->title }}</a></li>
                    </ul>
                </div>
                @if($routePrefix !== 'student')
                    <a href="{{ route($routePrefix . '.live_classes.show', $liveClass->id) }}" class="eBtn eBtn-dark">{{ get_phrase('Class Details') }}</a>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="eSection-wrap" style="max-width: 640px;">
            <h5 class="mb-3">{{ $liveClass->title }}</h5>

            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle-fill"></i>
                {{--
                    Not wrapped in get_phrase(): that helper auto-inserts
                    every string it's given into language.phrase, a
                    varchar(191) column — anything longer than that throws
                    a SQL truncation error under strict mode (which is how
                    this was actually caught). Long explanatory paragraphs
                    like this one have to stay as plain, untranslated text.
                --}}
                @if($isStaff)
                    This class runs on this school's one shared Google Meet account, not your own personal Google sign-in. If you are running this session, make sure you are signed into that same shared Google account in this browser tab before continuing — otherwise Google Meet will not recognise you as host, and you will have to wait to be let in just like everyone else.
                @else
                    This class runs on Google Meet. You may briefly see a "waiting to be admitted" screen until the host lets you in — that is expected, just wait a moment.
                @endif
            </div>

            <a href="{{ $meetingUrl }}" target="_blank" rel="noopener" class="eBtn eBtn-primary">
                <i class="bi bi-camera-video-fill"></i> {{ get_phrase('Continue to Google Meet') }}
            </a>
        </div>
    </div>
</div>
@endsection
