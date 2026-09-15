@php
    $selectedSubject = old('subject_id', $liveClass->subject_id ?? '');
    $selectedClass = old('class_id', $liveClass->class_id ?? '');
    $selectedProgramme = old('programme_id', $liveClass->programme_id ?? '');
    $selectedSession = old('academic_session_id', $liveClass->academic_session_id ?? '');
    $selectedPlatform = old('platform', $liveClass->platform ?? 'jitsi');
    $selectedStatus = old('status', $liveClass->status ?? \App\Models\LiveClass::STATUS_DRAFT);
    $platformStatus = $platformStatus ?? ['jitsi' => true, 'google_meet' => false, 'zoom' => false, 'bigbluebutton' => true, 'custom' => true];
@endphp

<div id="googleMeetFreeTierWarning" class="alert alert-warning d-none" role="alert">
    <i class="bi bi-exclamation-triangle-fill"></i>
    {{ get_phrase('Google Meet is free to 60 minutes for group calls. This class is scheduled for longer than that — the call may be cut off automatically unless the host is on a paid Google Workspace plan.') }}
</div>

<div class="live-class-form-section">
    <div class="live-class-form-section__title">
        <i class="bi bi-camera-video"></i> {{ get_phrase('Class Details') }}
    </div>
    <div class="row">
        <div class="col-md-6 mt-3">
            <label class="eForm-label">{{ get_phrase('Title') }} *</label>
            <input type="text" class="form-control eForm-control" name="title" value="{{ old('title', $liveClass->title) }}" placeholder="{{ get_phrase('e.g. Introduction to Financial Accounting') }}" required>
        </div>

        <div class="col-md-6 mt-3">
            <label class="eForm-label">{{ get_phrase('Platform') }} *</label>
            <select class="form-control eForm-control" name="platform" id="liveClassPlatform" required>
                @foreach(['jitsi' => 'Jitsi Meet', 'google_meet' => 'Google Meet (Recommended)', 'zoom' => 'Zoom', 'bigbluebutton' => 'BigBlueButton', 'custom' => 'Custom'] as $value => $label)
                    <option value="{{ $value }}" {{ $selectedPlatform === $value ? 'selected' : '' }}>
                        {{ $label }}{{ empty($platformStatus[$value]) ? ' — ' . get_phrase('not yet configured') : '' }}
                    </option>
                @endforeach
            </select>
            <small class="text-muted d-block mt-1">{{ get_phrase('Jitsi is auto-created by default. Zoom and Google Meet can also be auto-created when API credentials are configured.') }}</small>
        </div>

        <div class="col-12 mt-3">
            <label class="eForm-label">{{ get_phrase('Description') }}</label>
            <textarea class="form-control eForm-control" rows="3" name="description" placeholder="{{ get_phrase('What will this class cover?') }}">{{ old('description', $liveClass->description) }}</textarea>
        </div>
    </div>
</div>

<div class="live-class-form-section">
    <div class="live-class-form-section__title">
        <i class="bi bi-diagram-3"></i> {{ get_phrase('Assign To') }}
    </div>
    <div class="row">
        <div class="col-md-6 mt-3">
            <label class="eForm-label">{{ get_phrase('Course') }}</label>
            <select class="form-control eForm-control" name="subject_id">
                <option value="">{{ get_phrase('Select course') }}</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}" {{ (string) $selectedSubject === (string) $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                @endforeach
            </select>
            <small class="text-muted d-block mt-1">
                @if(request()->routeIs('teacher.*'))
                    <a href="{{ route('teacher.subject.create') }}">{{ get_phrase('Add course') }}</a>
                @else
                    <a href="javascript:;" onclick="rightModal('{{ route('admin.subject.open_modal') }}', '{{ get_phrase('Create Subject') }}')">{{ get_phrase('Add course') }}</a>
                @endif
            </small>
        </div>

        <div class="col-md-6 mt-3">
            <label class="eForm-label">{{ get_phrase('Class') }}</label>
            <select class="form-control eForm-control" name="class_id">
                <option value="">{{ get_phrase('Select class') }}</option>
                @foreach($classList as $class)
                    <option value="{{ $class->id }}" {{ (string) $selectedClass === (string) $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6 mt-3">
            <label class="eForm-label">{{ get_phrase('Programme') }}</label>
            <select class="form-control eForm-control" name="programme_id">
                <option value="">{{ get_phrase('Select programme') }}</option>
                @foreach($programmes as $programme)
                    <option value="{{ $programme->id }}" {{ (string) $selectedProgramme === (string) $programme->id ? 'selected' : '' }}>{{ $programme->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6 mt-3">
            <label class="eForm-label">{{ get_phrase('Academic session') }}</label>
            <select class="form-control eForm-control" name="academic_session_id">
                <option value="">{{ get_phrase('Select session') }}</option>
                @foreach($sessions as $session)
                    <option value="{{ $session->id }}" {{ (string) $selectedSession === (string) $session->id ? 'selected' : '' }}>{{ $session->session_title }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="live-class-form-section">
    <div class="live-class-form-section__title">
        <i class="bi bi-calendar-event"></i> {{ get_phrase('Schedule') }}
    </div>
    <div class="row">
        <div class="col-md-4 mt-3">
            <label class="eForm-label">{{ get_phrase('Date') }} *</label>
            <input type="date" class="form-control eForm-control" name="start_date" value="{{ old('start_date', optional($liveClass->start_date)->format('Y-m-d')) }}" required>
        </div>

        <div class="col-md-4 mt-3">
            <label class="eForm-label">{{ get_phrase('Start time') }} *</label>
            <input type="time" class="form-control eForm-control" id="liveClassStartTime" name="start_time" value="{{ old('start_time', $liveClass->start_time ? \Illuminate\Support\Carbon::parse($liveClass->start_time)->format('H:i') : '') }}" required>
        </div>

        <div class="col-md-4 mt-3">
            <label class="eForm-label">{{ get_phrase('End time') }} *</label>
            <input type="time" class="form-control eForm-control" id="liveClassEndTime" name="end_time" value="{{ old('end_time', $liveClass->end_time ? \Illuminate\Support\Carbon::parse($liveClass->end_time)->format('H:i') : '') }}" required>
        </div>

        <div class="col-md-4 mt-3">
            <label class="eForm-label">{{ get_phrase('Timezone') }}</label>
            <input type="text" class="form-control eForm-control" name="timezone" value="{{ old('timezone', $liveClass->timezone ?: config('app.timezone')) }}" placeholder="Africa/Kampala">
        </div>
    </div>
</div>

<div class="live-class-form-section">
    <div class="live-class-form-section__title">
        <i class="bi bi-sliders"></i> {{ get_phrase('Meeting & Publishing') }}
    </div>
    <div class="row">
        <div class="col-md-4 mt-3">
            <label class="eForm-label">{{ get_phrase('Status') }}</label>
            <select class="form-control eForm-control" name="status">
                @foreach(['draft', 'scheduled', 'live', 'ended', 'cancelled'] as $statusValue)
                    <option value="{{ $statusValue }}" {{ $selectedStatus === $statusValue ? 'selected' : '' }}>{{ ucfirst($statusValue) }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-8 mt-3">
            <label class="eForm-label">{{ get_phrase('Meeting URL') }}</label>
            <input type="url" class="form-control eForm-control" name="meeting_url" value="{{ old('meeting_url', $liveClass->meeting_url) }}" placeholder="https://...">
            <small class="text-muted d-block mt-1">{{ get_phrase('Leave blank to have the selected platform auto-create the meeting link.') }}</small>
        </div>

        <div class="col-md-4 mt-3">
            <label class="eForm-label">{{ get_phrase('Meeting ID') }}</label>
            <input type="text" class="form-control eForm-control" name="meeting_id" value="{{ old('meeting_id', $liveClass->meeting_id) }}">
        </div>

        <div class="col-md-4 mt-3">
            <label class="eForm-label">{{ get_phrase('Meeting Password') }}</label>
            <input type="password" class="form-control eForm-control" name="meeting_password" value="{{ old('meeting_password', $liveClass->meeting_password) }}">
        </div>

        <div class="col-md-4 mt-3">
            <label class="eForm-label">{{ get_phrase('Recording URL') }}</label>
            <input type="url" class="form-control eForm-control" name="recording_url" value="{{ old('recording_url', $liveClass->recording_url) }}" placeholder="https://...">
        </div>

        <div class="col-12 mt-4 d-flex gap-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1" {{ old('is_published', $liveClass->is_published) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_published">{{ get_phrase('Published') }}</label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="attendance_enabled" name="attendance_enabled" value="1" {{ old('attendance_enabled', $liveClass->attendance_enabled) ? 'checked' : '' }}>
                <label class="form-check-label" for="attendance_enabled">{{ get_phrase('Attendance enabled') }}</label>
            </div>
        </div>
    </div>
</div>

<style>
    .live-class-form-section {
        padding: 18px 0;
        border-bottom: 1px solid #eef0f4;
    }
    .live-class-form-section:last-of-type {
        border-bottom: none;
        padding-bottom: 0;
    }
    .live-class-form-section:first-of-type {
        padding-top: 4px;
    }
    .live-class-form-section__title {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #3a86ff;
        display: flex;
        align-items: center;
        gap: 8px;
    }
</style>

<script>
(function () {
    var FREE_TIER_LIMIT_MINUTES = {{ \App\Models\LiveClass::FREE_TIER_MINUTE_LIMIT }};
    var platformSelect = document.getElementById('liveClassPlatform');
    var startInput = document.getElementById('liveClassStartTime');
    var endInput = document.getElementById('liveClassEndTime');
    var warning = document.getElementById('googleMeetFreeTierWarning');

    function toMinutes(value) {
        if (!value) return null;
        var parts = value.split(':');
        return (parseInt(parts[0], 10) * 60) + parseInt(parts[1], 10);
    }

    function refresh() {
        if (!platformSelect || !startInput || !endInput || !warning) return;

        var start = toMinutes(startInput.value);
        var end = toMinutes(endInput.value);
        var isGoogleMeet = platformSelect.value === 'google_meet';
        var duration = (start !== null && end !== null) ? (end - start) : null;

        var exceedsLimit = isGoogleMeet && duration !== null && duration > FREE_TIER_LIMIT_MINUTES;
        warning.classList.toggle('d-none', !exceedsLimit);
    }

    [platformSelect, startInput, endInput].forEach(function (el) {
        if (el) el.addEventListener('change', refresh);
    });

    refresh();
})();
</script>
