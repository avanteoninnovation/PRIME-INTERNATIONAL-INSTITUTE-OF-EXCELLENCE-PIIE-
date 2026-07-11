@extends('student.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Live Classes') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="{{ route('student.dashboard') }}">{{ get_phrase('Home') }}</a></li><li><a href="#">{{ get_phrase('Live Classes') }}</a></li></ul>
        </div>
    </div>
</div></div></div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="row"><div class="col-12"><div class="eSection-wrap mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3"><label class="eForm-label">{{ get_phrase('Search') }}</label><input type="text" name="search" value="{{ $search }}" class="form-control eForm-control"></div>
        <div class="col-md-2"><label class="eForm-label">{{ get_phrase('Course') }}</label><select name="subject_id" class="form-control eForm-control"><option value="">{{ get_phrase('All') }}</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}" {{ (string)$subjectId===(string)$subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="eForm-label">{{ get_phrase('Platform') }}</label><select name="platform" class="form-control eForm-control"><option value="">{{ get_phrase('All') }}</option>@foreach(['jitsi','google_meet','zoom','bigbluebutton','custom'] as $platformValue)<option value="{{ $platformValue }}" {{ $platform === $platformValue ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $platformValue)) }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="eForm-label">{{ get_phrase('Status') }}</label><select name="status" class="form-control eForm-control"><option value="">{{ get_phrase('All') }}</option>@foreach(['scheduled','live','ended'] as $statusValue)<option value="{{ $statusValue }}" {{ $status === $statusValue ? 'selected' : '' }}>{{ ucfirst($statusValue) }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="eForm-label">{{ get_phrase('Date') }}</label><input type="date" name="date" value="{{ $date }}" class="form-control eForm-control"></div>
        <div class="col-md-1"><button type="submit" class="eBtn eBtn-primary">{{ get_phrase('Go') }}</button></div>
    </form>
</div></div></div>

<div class="row"><div class="col-12"><div class="eSection-wrap">
    <div class="row g-3">
        @forelse($classes as $lc)
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h6>{{ $lc->title }}</h6>
                        <span class="badge bg-{{ $lc->computed_status=='live'?'danger':($lc->computed_status=='scheduled'?'warning':'secondary') }}">{{ ucfirst($lc->computed_status) }}</span>
                    </div>
                    <div class="text-muted small">{{ optional($lc->subject)->name }}</div>
                    @if($lc->start_date)
                    <div class="mt-2"><i class="bi bi-calendar-event"></i> {{ $lc->start_date->format('d M Y') }} {{ $lc->start_time ? \Illuminate\Support\Carbon::parse($lc->start_time)->format('H:i') : '' }} - {{ $lc->end_time ? \Illuminate\Support\Carbon::parse($lc->end_time)->format('H:i') : '' }}</div>
                    @endif
                    @if($lc->description)<p class="small mt-2">{{ Str::limit($lc->description,80) }}</p>@endif
                    @if($lc->can_join)
                        <a href="{{ route('student.live_classes.join', $lc->id) }}" class="eBtn eBtn-sm eBtn-{{ $lc->computed_status=='live'?'danger':'primary' }} w-100 mt-2">
                            <i class="bi bi-camera-video"></i> {{ $lc->computed_status=='live' ? get_phrase('Join Now') : get_phrase('Join Class') }}
                        </a>
                    @endif
                    @if($lc->computed_status === \App\Models\LiveClass::STATUS_ENDED && $lc->safe_recording_url)
                        <a href="{{ $lc->safe_recording_url }}" target="_blank" class="eBtn eBtn-sm eBtn-dark w-100 mt-2">{{ get_phrase('View Recording') }}</a>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center text-muted py-4">{{ get_phrase('No live classes scheduled') }}</div>
        @endforelse
    </div>
    <div class="mt-3">{{ $classes->appends(request()->query())->links() }}</div>
</div></div></div>
@endsection
