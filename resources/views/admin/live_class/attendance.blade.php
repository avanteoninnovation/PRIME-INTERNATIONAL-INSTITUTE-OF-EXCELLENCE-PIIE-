@extends(request()->routeIs('teacher.*') ? 'teacher.navigation' : 'admin.navigation')
@section('content')
@php
    $routePrefix = request()->routeIs('teacher.*') ? 'teacher' : 'admin';
    $withDuration = $records->filter(fn ($r) => $r->hasKnownDuration());
@endphp

<div class="mainSection-title">
    <div class="row"><div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
            <div class="d-flex flex-column">
                <h4>{{ get_phrase('Attendance') }} — {{ $liveClass->title }}</h4>
                <ul class="d-flex align-items-center eBreadcrumb-2">
                    <li><a href="{{ route($routePrefix . '.live_classes.index') }}">{{ get_phrase('Live Classes') }}</a></li>
                    <li><a href="{{ route($routePrefix . '.live_classes.show', $liveClass->id) }}">{{ $liveClass->title }}</a></li>
                    <li><a href="#">{{ get_phrase('Attendance') }}</a></li>
                </ul>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route($routePrefix . '.live_classes.attendance_export', $liveClass->id) }}" class="export_btn export_btn-outline"><i class="bi bi-download"></i> {{ get_phrase('Export CSV') }}</a>
                <a href="{{ route($routePrefix . '.live_classes.show', $liveClass->id) }}" class="eBtn eBtn-dark">{{ get_phrase('Back') }}</a>
            </div>
        </div>
    </div></div>
</div>

<div class="row mb-3">
    <div class="col-md-4"><div class="eCard text-center p-3"><h4 class="text-primary">{{ $records->count() }}</h4><small>{{ get_phrase('Join Events') }}</small></div></div>
    <div class="col-md-4"><div class="eCard text-center p-3"><h4 class="text-primary">{{ $records->pluck('user_id')->unique()->count() }}</h4><small>{{ get_phrase('Unique Students') }}</small></div></div>
    <div class="col-md-4"><div class="eCard text-center p-3"><h4 class="text-secondary">{{ $withDuration->count() }}</h4><small>{{ get_phrase('With Known Duration') }}</small></div></div>
</div>

@unless($liveClass->attendance_enabled)
    <div class="alert alert-info">{{ get_phrase('Attendance tracking was not enabled for this class, so this list may be incomplete.') }}</div>
@endunless

@if($liveClass->platform !== 'jitsi')
    <div class="alert alert-warning">
        {{ str_replace(':platform', ucwords(str_replace('_', ' ', $liveClass->platform)), get_phrase('This class runs on an external platform (:platform). Join time is recorded when a student clicks Join in the portal; leave time cannot be observed once they move to the external meeting.')) }}
    </div>
@endif

<div class="row"><div class="col-12"><div class="eSection-wrap">
    <div class="table-responsive">
        <table class="table eTable">
            <thead><tr>
                <th>#</th><th>{{ get_phrase('Student') }}</th><th>{{ get_phrase('Email') }}</th>
                <th>{{ get_phrase('Joined') }}</th><th>{{ get_phrase('Left') }}</th><th>{{ get_phrase('Duration') }}</th>
            </tr></thead>
            <tbody>
            @forelse($records as $i => $record)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ optional($record->user)->name ?: '—' }}</td>
                    <td>{{ optional($record->user)->email ?: '—' }}</td>
                    <td>{{ $record->joined_at?->format('d M Y, H:i:s') }}</td>
                    <td>{{ $record->left_at?->format('d M Y, H:i:s') ?: get_phrase('Unknown') }}</td>
                    <td>{{ $record->hasKnownDuration() ? round($record->duration_seconds / 60, 1) . ' ' . get_phrase('min') : get_phrase('Unknown') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">{{ get_phrase('No one has joined this class yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div></div></div>
@endsection
