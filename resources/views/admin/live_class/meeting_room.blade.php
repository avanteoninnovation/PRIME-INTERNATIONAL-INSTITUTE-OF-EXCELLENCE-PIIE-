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

@if(!empty($attendanceId))
<script>
(function () {
    // Fires when this attendee actually leaves the embedded room — the only
    // platform this app can observe a departure from at all (Zoom/Google
    // Meet/BigBlueButton open in a separate tab this app never hears from
    // again). Fired on both pagehide and visibility-hidden since browsers
    // are inconsistent about which one runs on a tab close; the server side
    // only acts on the first of the two (see LiveClassController::attendanceLeave()).
    var attendanceId = @json($attendanceId);
    var leaveUrl = "{{ route($routePrefix . '.live_classes.attendance_leave', $liveClass->id) }}";
    var csrfToken = "{{ csrf_token() }}";

    function sendLeaveBeacon() {
        var data = new FormData();
        data.append('_token', csrfToken);
        data.append('attendance_id', attendanceId);

        if (navigator.sendBeacon) {
            navigator.sendBeacon(leaveUrl, data);
        } else {
            fetch(leaveUrl, { method: 'POST', body: data, keepalive: true });
        }
    }

    window.addEventListener('pagehide', sendLeaveBeacon);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') sendLeaveBeacon();
    });
})();
</script>
@endif
@endsection
