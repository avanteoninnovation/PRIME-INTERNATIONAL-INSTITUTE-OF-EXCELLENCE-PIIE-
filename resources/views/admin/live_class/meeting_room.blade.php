@extends(request()->routeIs('teacher.*') ? 'teacher.navigation' : (request()->routeIs('student.*') ? 'student.navigation' : 'admin.navigation'))
@section('content')
@php
    $routePrefix = request()->routeIs('teacher.*') ? 'teacher' : (request()->routeIs('student.*') ? 'student' : 'admin');
@endphp

<div class="mainSection-title">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
                <div class="d-flex flex-column">
                    <h4>{{ get_phrase('Live Meeting Room') }}</h4>
                    <ul class="d-flex align-items-center eBreadcrumb-2">
                        @if($routePrefix !== 'student')
                            <li><a href="{{ route($routePrefix . '.live_classes.index') }}">{{ get_phrase('Live Classes') }}</a></li>
                        @else
                            <li><a href="{{ route('student.live_classes.index') }}">{{ get_phrase('Live Classes') }}</a></li>
                        @endif
                        <li><a href="#">{{ $liveClass->title }}</a></li>
                    </ul>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ $meetingUrl }}" target="_blank" rel="noopener" class="eBtn eBtn-primary">{{ get_phrase('Open External') }}</a>
                    @if($routePrefix !== 'student')
                        <a href="{{ route($routePrefix . '.live_classes.show', $liveClass->id) }}" class="eBtn eBtn-dark">{{ get_phrase('Class Details') }}</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="eSection-wrap p-3">
            <div class="alert alert-info mb-3">
                {{ get_phrase('This Jitsi meeting is auto-created by the system. No separate Google Meet creation is required.') }}
            </div>
            <div style="height: calc(100vh - 260px); min-height: 520px; border-radius: 8px; overflow: hidden; background: #111;">
                <iframe
                    src="{{ $meetingUrl }}"
                    title="{{ $liveClass->title }}"
                    width="100%"
                    height="100%"
                    allow="camera; microphone; fullscreen; display-capture"
                    referrerpolicy="strict-origin-when-cross-origin"
                    style="border: 0;"
                ></iframe>
            </div>
        </div>
    </div>
</div>
@endsection
