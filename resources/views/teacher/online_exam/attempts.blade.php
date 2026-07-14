@extends('teacher.navigation')

@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12 d-flex justify-content-between align-items-center">
    <h4>{{ get_phrase('Attempts') }}: {{ $exam->title }}</h4>
    <div class="d-flex gap-2">
        <a class="export_btn bg-secondary" href="{{ route('teacher.online_exams.results', $exam->id) }}">{{ get_phrase('Results') }}</a>
        <a class="export_btn" href="{{ route('teacher.online_exams.index') }}">{{ get_phrase('Back') }}</a>
    </div>
</div></div></div>

<div class="eSection-wrap">
    <table class="table eTable">
        <thead>
        <tr>
            <th>#</th><th>{{ get_phrase('Student') }}</th><th>{{ get_phrase('Attempt') }}</th><th>{{ get_phrase('Started') }}</th><th>{{ get_phrase('Expires') }}</th><th>{{ get_phrase('Submitted') }}</th><th>{{ get_phrase('Status') }}</th><th>{{ get_phrase('Objective') }}</th><th>{{ get_phrase('Manual') }}</th><th>{{ get_phrase('Total') }}</th><th>{{ get_phrase('Pass/Fail') }}</th><th>{{ get_phrase('Proctoring') }}</th><th>{{ get_phrase('Result') }}</th><th>{{ get_phrase('Actions') }}</th>
        </tr>
        </thead>
        <tbody>
        @forelse($submissions as $i => $submission)
            <tr>
                <td>{{ $submissions->firstItem() + $i }}</td>
                <td>{{ optional($submission->student)->name ?? '—' }}</td>
                <td>{{ $submission->attempt_no }}</td>
                <td>{{ optional($submission->started_at)->format('d M Y H:i') ?? '—' }}</td>
                <td>{{ optional($submission->expires_at)->format('d M Y H:i') ?? '—' }}</td>
                <td>{{ optional($submission->submitted_at)->format('d M Y H:i') ?? '—' }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $submission->status)) }}</td>
                <td>{{ $submission->objective_score ?? 0 }}</td>
                <td>{{ $submission->manual_score ?? 0 }}</td>
                <td>{{ $submission->effective_score }}</td>
                <td>{{ is_null($submission->passed) ? '—' : ($submission->passed ? 'Pass' : 'Fail') }}</td>
                <td>{{ $submission->proctoring_events_count }}</td>
                <td>{{ $submission->isResultVisible() ? 'Visible' : 'Hidden' }}</td>
                <td>
                    <a class="eBtn eBtn-sm eBtn-secondary" href="{{ route('teacher.online_exams.results', $exam->id) }}">{{ get_phrase('Result') }}</a>
                    <a class="eBtn eBtn-sm eBtn-info" href="{{ route('teacher.online_exams.marking') }}">{{ get_phrase('Marking') }}</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="14" class="text-center text-muted">{{ get_phrase('No attempts found') }}</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $submissions->links() }}
</div>
@endsection
