@extends(request()->routeIs('teacher.*') ? 'teacher.navigation' : 'admin.navigation')
@section('content')
@php
    $routePrefix = request()->routeIs('teacher.*') ? 'teacher' : 'admin';
@endphp
<style>
    .live-pulse-dot {
        display: inline-block; width: 8px; height: 8px; border-radius: 50%;
        background: #fff; margin-right: 5px; animation: liveClassPulse 1.4s infinite;
    }
    @keyframes liveClassPulse {
        0% { box-shadow: 0 0 0 0 rgba(255,255,255,.7); }
        70% { box-shadow: 0 0 0 6px rgba(255,255,255,0); }
        100% { box-shadow: 0 0 0 0 rgba(255,255,255,0); }
    }
    .join-now-btn { background: #16a34a !important; border-color: #16a34a !important; color: #fff !important; font-weight: 600; }
    .join-now-btn:hover { background: #15803d !important; color: #fff !important; }
</style>
<div class="mainSection-title">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
                <div class="d-flex flex-column">
                    <h4>{{ get_phrase('Live Class Details') }}</h4>
                    <ul class="d-flex align-items-center eBreadcrumb-2">
                        <li><a href="{{ route($routePrefix === 'teacher' ? 'teacher.dashboard' : 'admin.dashboard') }}">{{ get_phrase('Home') }}</a></li>
                        <li><a href="{{ route($routePrefix . '.live_classes.index') }}">{{ get_phrase('Live Classes') }}</a></li>
                        <li><a href="#">{{ get_phrase('Details') }}</a></li>
                    </ul>
                </div>
                <div class="d-flex gap-2">
                    <a href="javascript:;" class="eBtn eBtn-dark" onclick="rightModal('{{ route($routePrefix . '.live_classes.materials', $liveClass->id) }}', '{{ get_phrase('Class Materials') }}')">
                        <i class="bi bi-paperclip"></i> {{ get_phrase('Materials') }}
                    </a>
                    <a href="{{ route($routePrefix . '.live_classes.attendance', $liveClass->id) }}" class="eBtn eBtn-dark">
                        <i class="bi bi-people"></i> {{ get_phrase('Attendance') }}
                    </a>
                    <a href="{{ route($routePrefix . '.live_classes.edit', $liveClass->id) }}" class="eBtn eBtn-warning">{{ get_phrase('Edit') }}</a>
                    @if($liveClass->can_join)
                        <a href="{{ route($routePrefix . '.live_classes.join', $liveClass->id) }}" target="_blank" class="eBtn join-now-btn">
                            <i class="bi bi-camera-video-fill"></i> {{ $liveClass->computed_status === 'live' ? get_phrase('Join Now') : get_phrase('Join') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        @if($liveClass->exceedsGoogleMeetFreeTierLimit())
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle-fill"></i>
                {{ get_phrase('This class is scheduled for :minutes minutes on Google Meet — longer than the 60-minute free-tier limit for group calls. It may be cut off automatically unless the host is on a paid Google Workspace plan.', ['minutes' => $liveClass->duration_minutes]) }}
            </div>
        @endif
        <div class="eSection-wrap">
            <div class="row g-3">
                <div class="col-md-6"><strong>{{ get_phrase('Title') }}:</strong> {{ $liveClass->title }}</div>
                <div class="col-md-6"><strong>{{ get_phrase('Course') }}:</strong> {{ optional($liveClass->subject)->name ?: '—' }}</div>
                <div class="col-md-6"><strong>{{ get_phrase('Lecturer') }}:</strong> {{ optional($liveClass->teacher)->name ?: '—' }}</div>
                <div class="col-md-6"><strong>{{ get_phrase('Programme') }}:</strong> {{ optional($liveClass->programme)->name ?: '—' }}</div>
                <div class="col-md-6"><strong>{{ get_phrase('Platform') }}:</strong> {{ ucwords(str_replace('_', ' ', $liveClass->platform)) }}</div>
                <div class="col-md-6">
                    <strong>{{ get_phrase('Status') }}:</strong>
                    <span class="badge bg-{{ $liveClass->computed_status === 'live' ? 'danger' : ($liveClass->computed_status === 'scheduled' ? 'warning' : ($liveClass->computed_status === 'cancelled' ? 'secondary' : ($liveClass->computed_status === 'draft' ? 'dark' : 'success'))) }}">
                        @if($liveClass->computed_status === 'live')<span class="live-pulse-dot"></span>@endif
                        {{ ucfirst($liveClass->computed_status) }}
                    </span>
                </div>
                <div class="col-md-6"><strong>{{ get_phrase('Date') }}:</strong> {{ optional($liveClass->start_date)->format('d M Y') }}</div>
                <div class="col-md-6"><strong>{{ get_phrase('Time') }}:</strong> {{ $liveClass->start_time ? \Illuminate\Support\Carbon::parse($liveClass->start_time)->format('H:i') : '—' }} - {{ $liveClass->end_time ? \Illuminate\Support\Carbon::parse($liveClass->end_time)->format('H:i') : '—' }} @if($liveClass->duration_minutes !== null)<span class="text-muted">({{ $liveClass->duration_minutes }} {{ get_phrase('min') }})</span>@endif</div>
                <div class="col-md-6"><strong>{{ get_phrase('Published') }}:</strong> {{ $liveClass->is_published ? get_phrase('Yes') : get_phrase('No') }}</div>
                <div class="col-md-6"><strong>{{ get_phrase('Attendance enabled') }}:</strong> {{ $liveClass->attendance_enabled ? get_phrase('Yes') : get_phrase('No') }}</div>
                <div class="col-12"><strong>{{ get_phrase('Description') }}:</strong><br>{{ $liveClass->description ?: '—' }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="eSection-wrap">
            <h6>{{ get_phrase('Meeting') }}</h6>
            <p class="mb-2"><strong>{{ get_phrase('URL') }}:</strong><br>{{ $liveClass->safe_meeting_url ?: '—' }}</p>
            @if($liveClass->safe_recording_url)
                <a href="{{ $liveClass->safe_recording_url }}" target="_blank" class="eBtn eBtn-dark w-100 mb-2">{{ get_phrase('View Recording') }}</a>
            @endif
            <form method="POST" action="{{ route($routePrefix . '.live_classes.publish', $liveClass->id) }}" class="mb-2">
                @csrf
                <button type="submit" class="eBtn eBtn-primary w-100">{{ $liveClass->is_published ? get_phrase('Unpublish') : get_phrase('Publish') }}</button>
            </form>
            @if($liveClass->computed_status !== \App\Models\LiveClass::STATUS_CANCELLED)
                <form method="POST" action="{{ route($routePrefix . '.live_classes.cancel', $liveClass->id) }}" class="mb-2" onsubmit="return confirm('{{ get_phrase('Cancel this class?') }}')">
                    @csrf
                    <button type="submit" class="eBtn eBtn-danger w-100">{{ get_phrase('Cancel Class') }}</button>
                </form>
            @endif
            <form method="POST" action="{{ route($routePrefix . '.live_classes.destroy', $liveClass->id) }}" onsubmit="return confirm('{{ get_phrase('Delete this class?') }}')">
                @csrf
                @method('DELETE')
                <button type="submit" class="eBtn eBtn-danger w-100">{{ get_phrase('Delete') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection
