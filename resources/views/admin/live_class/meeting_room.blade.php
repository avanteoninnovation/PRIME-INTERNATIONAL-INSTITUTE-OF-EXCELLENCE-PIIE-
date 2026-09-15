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
            @if($jitsiConfigured)
                <div class="alert alert-success mb-3">
                    {{ get_phrase('Signed moderator token active') }} — {{ get_phrase('the host has full moderator rights here.') }}
                </div>
            @else
                <div class="alert alert-warning mb-3">
                    <strong>{{ get_phrase('If you see "Waiting for a moderator"') }}:</strong>
                    {{ get_phrase('this meeting has no moderator token configured yet.') }}
                    {{ get_phrase('Click "I am the host" and sign in when Jitsi prompts you.') }}
                    {{ get_phrase('For a permanent fix, see LIVE_CLASS_JITSI_JWT_SETUP.md.') }}
                </div>
            @endif

            <div id="jitsi-meet-container" style="height: calc(100vh - 260px); min-height: 520px; border-radius: 8px; overflow: hidden; background: #111;"></div>
            <noscript>
                <div class="alert alert-danger mt-3">{{ get_phrase('JavaScript is required to join this meeting. You can also') }} <a href="{{ $meetingUrl }}" target="_blank" rel="noopener">{{ get_phrase('open it in a new tab') }}</a>.</div>
            </noscript>
        </div>
    </div>
</div>

<script src="https://{{ $jitsiDomain }}/external_api.js"></script>
<script>
(function () {
    var container = document.getElementById('jitsi-meet-container');
    if (!container) {
        return;
    }
    if (typeof JitsiMeetExternalAPI === 'undefined') {
        container.innerHTML = '<div class="alert alert-danger m-3">{{ get_phrase('Could not load the meeting. Try "Open External" above instead.') }}</div>';
        return;
    }

    var options = {
        roomName: @json($jitsiRoomPath),
        parentNode: container,
        width: '100%',
        height: '100%',
        userInfo: {
            displayName: @json($displayName)
        },
        configOverwrite: {
            prejoinPageEnabled: false,
            startWithAudioMuted: {{ $isModerator ? 'false' : 'true' }},
            disableDeepLinking: true
        },
        interfaceConfigOverwrite: {
            MOBILE_APP_PROMO: false
        }
    };

    @if($jitsiJwt)
        options.jwt = @json($jitsiJwt);
    @endif

    var api = new JitsiMeetExternalAPI(@json($jitsiDomain), options);

    @if(!empty($attendanceId))
        // Fires when this attendee actually leaves the embedded room — the
        // only platform this app can observe a departure from at all
        // (Zoom/Google Meet/BigBlueButton open in a separate tab this app
        // never hears from again). The IFrame API's own leave events fire
        // immediately; pagehide/visibilitychange stay as a fallback for
        // tab closes the API sometimes misses.
        var attendanceId = @json($attendanceId);
        var leaveUrl = "{{ route($routePrefix . '.live_classes.attendance_leave', $liveClass->id) }}";
        var csrfToken = "{{ csrf_token() }}";
        var leaveSent = false;

        function sendLeaveBeacon() {
            if (leaveSent) return;
            leaveSent = true;

            var data = new FormData();
            data.append('_token', csrfToken);
            data.append('attendance_id', attendanceId);

            if (navigator.sendBeacon) {
                navigator.sendBeacon(leaveUrl, data);
            } else {
                fetch(leaveUrl, { method: 'POST', body: data, keepalive: true });
            }
        }

        api.addEventListener('videoConferenceLeft', sendLeaveBeacon);
        api.addEventListener('readyToClose', sendLeaveBeacon);
        window.addEventListener('pagehide', sendLeaveBeacon);
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'hidden') sendLeaveBeacon();
        });
    @endif
})();
</script>
@endsection
