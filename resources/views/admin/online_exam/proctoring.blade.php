@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Proctoring Review') }}: {{ optional($submission->student)->name }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2">
                <li><a href="{{ route('admin.online_exams.index') }}">{{ get_phrase('Online Exams') }}</a></li>
                <li><a href="{{ route('admin.online_exams.submissions', $exam->id) }}">{{ $exam->title }}</a></li>
                <li><a href="#">{{ get_phrase('Proctoring') }}</a></li>
            </ul>
        </div>
        <a class="export_btn bg-secondary" href="{{ route('admin.online_exams.submissions', $exam->id) }}">{{ get_phrase('Back to Submissions') }}</a>
    </div>
</div></div></div>

<div class="row"><div class="col-12"><div class="eSection-wrap mb-3">
    <div class="row">
        <div class="col-md-3"><strong>{{ get_phrase('Attempt') }}:</strong> {{ $submission->attempt_no }}</div>
        <div class="col-md-3"><strong>{{ get_phrase('Status') }}:</strong> {{ ucwords(str_replace('_',' ',$submission->status)) }}</div>
        <div class="col-md-3"><strong>{{ get_phrase('Webcam Consent') }}:</strong> {{ $submission->camera_consent_at ? $submission->camera_consent_at->format('d M Y H:i') : get_phrase('Not given') }}</div>
        <div class="col-md-3"><strong>{{ get_phrase('Fullscreen Started') }}:</strong> {{ $submission->fullscreen_started_at ? $submission->fullscreen_started_at->format('d M Y H:i') : '—' }}</div>
    </div>
    <div class="row mt-2">
        <div class="col-md-3"><strong>{{ get_phrase('IP Address') }}:</strong> {{ $submission->ip_address ?: '—' }}</div>
        <div class="col-md-9"><strong>{{ get_phrase('Browser') }}:</strong> {{ \Illuminate\Support\Str::limit($submission->user_agent, 120) ?: '—' }}</div>
    </div>
</div></div></div>

<div class="row"><div class="col-12"><div class="eSection-wrap">
    <h5 class="mb-3">{{ get_phrase('Event Timeline') }}</h5>
    <div class="table-responsive">
        <table class="table eTable">
            <thead><tr><th>#</th><th>{{ get_phrase('Event') }}</th><th>{{ get_phrase('Time') }}</th><th>{{ get_phrase('Details') }}</th></tr></thead>
            <tbody>
            @forelse($events as $i => $event)
                @php
                    $flagged = in_array($event->event_type, ['tab_hidden', 'fullscreen_exited', 'camera_permission_denied', 'connection_lost', 'snapshot_failed'], true);
                @endphp
                <tr class="{{ $flagged ? 'table-warning' : '' }}">
                    <td>{{ $events->firstItem() + $i }}</td>
                    <td>
                        <span class="badge bg-{{ $flagged ? 'danger' : 'secondary' }}">{{ $event->event_type ? ucwords(str_replace('_',' ', $event->event_type)) : get_phrase('Event type not recorded') }}</span>
                    </td>
                    <td>{{ $event->event_time?->format('d M Y H:i:s') }}</td>
                    <td>{{ !empty($event->metadata) ? json_encode($event->metadata) : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">{{ get_phrase('No proctoring events recorded for this attempt') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $events->links() }}
</div></div></div>
@endsection
