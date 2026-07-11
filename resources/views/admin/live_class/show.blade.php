@extends('admin.navigation')
@section('content')
<div class="mainSection-title">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
                <div class="d-flex flex-column">
                    <h4>{{ get_phrase('Live Class Details') }}</h4>
                    <ul class="d-flex align-items-center eBreadcrumb-2">
                        <li><a href="{{ route('admin.dashboard') }}">{{ get_phrase('Home') }}</a></li>
                        <li><a href="{{ route('admin.live_classes.index') }}">{{ get_phrase('Live Classes') }}</a></li>
                        <li><a href="#">{{ get_phrase('Details') }}</a></li>
                    </ul>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.live_classes.edit', $liveClass->id) }}" class="eBtn eBtn-warning">{{ get_phrase('Edit') }}</a>
                    @if($liveClass->can_join)
                        <a href="{{ route('admin.live_classes.join', $liveClass->id) }}" target="_blank" class="eBtn eBtn-primary">{{ get_phrase('Join') }}</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="eSection-wrap">
            <div class="row g-3">
                <div class="col-md-6"><strong>{{ get_phrase('Title') }}:</strong> {{ $liveClass->title }}</div>
                <div class="col-md-6"><strong>{{ get_phrase('Course') }}:</strong> {{ optional($liveClass->subject)->name ?: '—' }}</div>
                <div class="col-md-6"><strong>{{ get_phrase('Lecturer') }}:</strong> {{ optional($liveClass->teacher)->name ?: '—' }}</div>
                <div class="col-md-6"><strong>{{ get_phrase('Programme') }}:</strong> {{ optional($liveClass->programme)->name ?: '—' }}</div>
                <div class="col-md-6"><strong>{{ get_phrase('Platform') }}:</strong> {{ ucwords(str_replace('_', ' ', $liveClass->platform)) }}</div>
                <div class="col-md-6"><strong>{{ get_phrase('Status') }}:</strong> {{ ucfirst($liveClass->computed_status) }}</div>
                <div class="col-md-6"><strong>{{ get_phrase('Date') }}:</strong> {{ optional($liveClass->start_date)->format('d M Y') }}</div>
                <div class="col-md-6"><strong>{{ get_phrase('Time') }}:</strong> {{ $liveClass->start_time ? \Illuminate\Support\Carbon::parse($liveClass->start_time)->format('H:i') : '—' }} - {{ $liveClass->end_time ? \Illuminate\Support\Carbon::parse($liveClass->end_time)->format('H:i') : '—' }}</div>
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
            <form method="POST" action="{{ route('admin.live_classes.publish', $liveClass->id) }}" class="mb-2">
                @csrf
                <button type="submit" class="eBtn eBtn-primary w-100">{{ $liveClass->is_published ? get_phrase('Unpublish') : get_phrase('Publish') }}</button>
            </form>
            @if($liveClass->computed_status !== \App\Models\LiveClass::STATUS_CANCELLED)
                <form method="POST" action="{{ route('admin.live_classes.cancel', $liveClass->id) }}" class="mb-2" onsubmit="return confirm('{{ get_phrase('Cancel this class?') }}')">
                    @csrf
                    <button type="submit" class="eBtn eBtn-danger w-100">{{ get_phrase('Cancel Class') }}</button>
                </form>
            @endif
            <form method="POST" action="{{ route('admin.live_classes.destroy', $liveClass->id) }}" onsubmit="return confirm('{{ get_phrase('Delete this class?') }}')">
                @csrf
                @method('DELETE')
                <button type="submit" class="eBtn eBtn-danger w-100">{{ get_phrase('Delete') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection
