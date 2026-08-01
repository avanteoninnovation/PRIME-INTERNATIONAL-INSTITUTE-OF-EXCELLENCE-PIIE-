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
    .join-now-btn {
        background: #16a34a !important; border-color: #16a34a !important; color: #fff !important;
        font-weight: 600;
    }
    .join-now-btn:hover { background: #15803d !important; }
</style>
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Live Classes') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="{{ route($routePrefix === 'teacher' ? 'teacher.dashboard' : 'admin.dashboard') }}">{{ get_phrase('Home') }}</a></li><li><a href="#">{{ get_phrase('Live Classes') }}</a></li></ul>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route($routePrefix . '.live_classes.create') }}" class="eBtn eBtn-primary">{{ get_phrase('Schedule Class') }}</a>
            <form method="POST" action="{{ route($routePrefix . '.live_classes.meet_now') }}" target="_blank" class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                @csrf
                <select name="class_id" class="form-control eForm-control" style="min-width: 150px; height: 40px;" aria-label="{{ get_phrase('Class') }}">
                    <option value="">{{ get_phrase('All classes') }}</option>
                    @foreach($classList as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                    @endforeach
                </select>
                <select name="subject_id" class="form-control eForm-control" style="min-width: 170px; height: 40px;" aria-label="{{ get_phrase('Course') }}">
                    <option value="">{{ get_phrase('All courses') }}</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                    @endforeach
                </select>
                <select name="academic_session_id" class="form-control eForm-control" style="min-width: 170px; height: 40px;" aria-label="{{ get_phrase('Academic session') }}">
                    <option value="">{{ get_phrase('All sessions') }}</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}">{{ $session->session_title }}</option>
                    @endforeach
                </select>
                <select name="platform" class="form-control eForm-control" style="min-width: 165px; height: 40px;" aria-label="{{ get_phrase('Meeting platform') }}">
                    @foreach(['jitsi' => 'Jitsi (Auto In-System)', 'google_meet' => 'Google Meet', 'zoom' => 'Zoom'] as $value => $label)
                        @if($value === 'jitsi' || !empty($platformStatus[$value]))
                            <option value="{{ $value }}" {{ $defaultPlatform === $value ? 'selected' : '' }}>{{ get_phrase($label) }}</option>
                        @endif
                    @endforeach
                </select>
                <button type="submit" class="eBtn eBtn-success">{{ get_phrase('Meet Now') }}</button>
            </form>
            <a href="javascript:;" class="eBtn eBtn-dark" onclick="rightModal('{{ route($routePrefix . '.live_classes.open_modal') }}', '{{ get_phrase('Quick Schedule') }}')">{{ get_phrase('Quick Modal') }}</a>
        </div>
    </div>
</div></div></div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="row">
    <div class="col-12">
        <div class="eSection-wrap">
            @php
                $activeView = $status ? null : ($view ?? 'upcoming');
                $viewTabs = [
                    'upcoming' => get_phrase('Upcoming'),
                    'live' => get_phrase('Live Now'),
                    'completed' => get_phrase('Completed'),
                    'cancelled' => get_phrase('Cancelled'),
                    'all' => get_phrase('All'),
                ];
            @endphp
            <ul class="nav nav-pills mb-3 gap-2">
                @foreach($viewTabs as $tabKey => $tabLabel)
                    <li class="nav-item">
                        <a class="nav-link {{ $activeView === $tabKey ? 'active' : '' }}"
                           href="{{ route($routePrefix . '.live_classes.index', array_filter(['view' => $tabKey, 'search' => $search, 'subject_id' => $subjectId, 'platform' => $platform, 'date' => $date])) }}">
                            {{ $tabLabel }}
                        </a>
                    </li>
                @endforeach
            </ul>

            <form method="GET" class="row g-2 align-items-end mb-3">
                <div class="col-md-3"><label class="eForm-label">{{ get_phrase('Search') }}</label><input type="text" name="search" value="{{ $search }}" class="form-control eForm-control" placeholder="{{ get_phrase('Title') }}"></div>
                <div class="col-md-2"><label class="eForm-label">{{ get_phrase('Course') }}</label><select name="subject_id" class="form-control eForm-control"><option value="">{{ get_phrase('All') }}</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}" {{ (string)$subjectId===(string)$subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="eForm-label">{{ get_phrase('Platform') }}</label><select name="platform" class="form-control eForm-control"><option value="">{{ get_phrase('All') }}</option>@foreach(['jitsi','google_meet','zoom','bigbluebutton','custom'] as $platformValue)<option value="{{ $platformValue }}" {{ $platform === $platformValue ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ', $platformValue)) }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="eForm-label">{{ get_phrase('Status') }}</label><select name="status" class="form-control eForm-control"><option value="">{{ get_phrase('All') }}</option>@foreach(['draft','scheduled','live','ended','cancelled'] as $statusValue)<option value="{{ $statusValue }}" {{ $status === $statusValue ? 'selected' : '' }}>{{ ucfirst($statusValue) }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="eForm-label">{{ get_phrase('Date') }}</label><input type="date" name="date" class="form-control eForm-control" value="{{ $date }}"></div>
                <div class="col-md-1"><button type="submit" class="eBtn eBtn-primary">{{ get_phrase('Go') }}</button></div>
            </form>

            <div class="table-responsive">
                <table class="table eTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ get_phrase('Title') }}</th>
                            <th>{{ get_phrase('Course') }}</th>
                            <th>{{ get_phrase('Lecturer') }}</th>
                            <th>{{ get_phrase('Platform') }}</th>
                            <th>{{ get_phrase('Date') }}</th>
                            <th>{{ get_phrase('Time') }}</th>
                            <th>{{ get_phrase('Status') }}</th>
                            <th>{{ get_phrase('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($classes as $i => $lc)
                        @php
                            $statusClass = $lc->computed_status === 'live' ? 'danger' : ($lc->computed_status === 'scheduled' ? 'warning' : ($lc->computed_status === 'cancelled' ? 'secondary' : ($lc->computed_status === 'draft' ? 'dark' : 'success')));
                        @endphp
                        <tr>
                            <td>{{ $classes->firstItem() + $i }}</td>
                            <td>{{ $lc->title }}</td>
                            <td>{{ optional($lc->subject)->name ?: '—' }}</td>
                            <td>{{ optional($lc->teacher)->name ?: '—' }}</td>
                            <td>
                                <span class="badge bg-info">{{ ucwords(str_replace('_',' ', $lc->platform)) }}</span>
                                @if($lc->exceedsGoogleMeetFreeTierLimit())
                                    <i class="bi bi-exclamation-triangle-fill text-warning" title="{{ get_phrase('Longer than the 60-minute Google Meet free-tier limit') }}"></i>
                                @endif
                            </td>
                            <td>{{ optional($lc->start_date)->format('d M Y') }}</td>
                            <td>{{ $lc->start_time ? \Illuminate\Support\Carbon::parse($lc->start_time)->format('H:i') : '—' }} - {{ $lc->end_time ? \Illuminate\Support\Carbon::parse($lc->end_time)->format('H:i') : '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $statusClass }}">
                                    @if($lc->computed_status === 'live')<span class="live-pulse-dot"></span>@endif
                                    {{ ucfirst($lc->computed_status) }}
                                </span>
                            </td>
                            <td class="d-flex flex-wrap gap-1">
                                <a href="{{ route($routePrefix . '.live_classes.show', $lc->id) }}" class="eBtn eBtn-sm eBtn-dark">{{ get_phrase('View') }}</a>
                                <a href="{{ route($routePrefix . '.live_classes.edit', $lc->id) }}" class="eBtn eBtn-sm eBtn-warning">{{ get_phrase('Edit') }}</a>
                                @if($lc->can_join)
                                    <a href="{{ route($routePrefix . '.live_classes.join', $lc->id) }}" target="_blank" class="eBtn eBtn-sm join-now-btn">
                                        <i class="bi bi-camera-video-fill"></i> {{ $lc->computed_status === 'live' ? get_phrase('Join Now') : get_phrase('Join') }}
                                    </a>
                                @endif
                                @if($lc->safe_recording_url)
                                    <a href="{{ $lc->safe_recording_url }}" target="_blank" class="eBtn eBtn-sm eBtn-dark">{{ get_phrase('Recording') }}</a>
                                @endif
                                <a href="javascript:;" class="eBtn eBtn-sm eBtn-dark" title="{{ get_phrase('Materials') }}" onclick="rightModal('{{ route($routePrefix . '.live_classes.materials', $lc->id) }}', '{{ get_phrase('Class Materials') }}')">
                                    <i class="bi bi-paperclip"></i>
                                </a>
                                <a href="{{ route($routePrefix . '.live_classes.attendance', $lc->id) }}" class="eBtn eBtn-sm eBtn-dark" title="{{ get_phrase('Attendance') }}">
                                    <i class="bi bi-people"></i>
                                </a>
                                @if($lc->computed_status !== \App\Models\LiveClass::STATUS_CANCELLED)
                                    <form method="POST" action="{{ route($routePrefix . '.live_classes.cancel', $lc->id) }}" onsubmit="return confirm('{{ get_phrase('Cancel this class?') }}')">
                                        @csrf
                                        <button type="submit" class="eBtn eBtn-sm eBtn-danger">{{ get_phrase('Cancel') }}</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route($routePrefix . '.live_classes.publish', $lc->id) }}">
                                    @csrf
                                    <button type="submit" class="eBtn eBtn-sm eBtn-primary">{{ $lc->is_published ? get_phrase('Unpublish') : get_phrase('Publish') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">{{ get_phrase('No live classes scheduled') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $classes->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection
