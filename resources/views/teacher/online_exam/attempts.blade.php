@extends('teacher.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12 d-flex justify-content-between align-items-center">
    <h4>{{ get_phrase('Attempts') }}: {{ $exam->title }}</h4>
    <div class="d-flex gap-2"><a class="export_btn bg-secondary" href="{{ route('teacher.online_exams.results', $exam->id) }}">{{ get_phrase('Results') }}</a><a class="export_btn" href="{{ route('teacher.online_exams.index') }}">{{ get_phrase('Back') }}</a></div>
</div></div></div>
<style>
    .online-exam-attempts-table { min-width: 1420px; }
    .online-exam-attempts-table th, .online-exam-attempts-table td { vertical-align: middle; white-space: nowrap; }
    .online-exam-attempts-actions { min-width: 300px; white-space: normal !important; }
</style>
<div class="eSection-wrap"><div class="table-responsive online-exam-attempts-wrap">
<table class="table eTable online-exam-attempts-table"><thead><tr>
    <th>#</th><th>{{ get_phrase('Student') }}</th><th>{{ get_phrase('Attempt') }}</th><th>{{ get_phrase('Submitted') }}</th><th>{{ get_phrase('Automatic') }}</th><th>{{ get_phrase('Manual') }}</th><th>{{ get_phrase('Total') }}</th><th>{{ get_phrase('Maximum') }}</th><th>{{ get_phrase('Pending') }}</th><th>{{ get_phrase('Marking Status') }}</th><th>{{ get_phrase('Finalization') }}</th><th>{{ get_phrase('Release') }}</th><th>{{ get_phrase('Proctoring') }}</th><th>{{ get_phrase('Actions') }}</th>
</tr></thead><tbody>
@forelse($submissions as $i => $submission)
    @php
        $summary = \App\Support\OnlineExams\OnlineExamMarking::summary($submission);
        $pending = (int) $summary['pending'];
        $ready = $pending === 0 && in_array($submission->status, ['submitted', 'timed_out', 'pending_manual_marking'], true);
        $reviewState = $submission->result_review_state ?? ($submission->isFinalized() ? 'pending_review' : 'not_ready');
        $markingComplete = $pending === 0 && in_array($submission->status, ['submitted', 'timed_out', 'finalized', 'result_published'], true);
        $markingLabel = $pending > 0 ? 'Awaiting Marking' : ($reviewState === 'published' ? 'Result published' : ($reviewState === 'pending_review' ? 'Pending Admin Review' : ($reviewState === 'returned_for_correction' ? 'Returned for Correction' : ($markingComplete ? 'Marking Complete' : 'Ready to finalize'))));
    @endphp
    <tr>
        <td>{{ $submissions->firstItem() + $i }}</td>
        <td>{{ optional($submission->student)->name ?? '—' }}</td>
        <td>{{ $submission->attempt_no }}</td>
        <td>{{ optional($submission->submitted_at)->format('d M Y H:i') ?? '—' }}</td>
        <td>{{ number_format($summary['objective_score'], 2) }}</td>
        <td>{{ number_format($summary['manual_score'], 2) }}</td>
        <td>{{ number_format($summary['score'], 2) }}</td>
        <td>{{ number_format($submission->result_total_marks, 2) }}</td>
        <td>{{ $pending }}</td>
        <td>{{ $markingLabel }}</td>
        <td>{{ $submission->isFinalized() ? 'Finalized' : 'Not Finalized' }}</td>
        <td>{{ $submission->isResultVisible() ? 'Released' : 'Not Released' }}</td>
        <td>{{ $submission->proctoring_events_count }}</td>
        <td class="online-exam-attempts-actions">
            @if($pending > 0)<a class="eBtn eBtn-sm eBtn-info" href="{{ route('teacher.online_exams.marking') }}">{{ get_phrase('Continue Marking') }}</a>@endif
            @if($ready)
                @can('grade', $submission)
                <form method="POST" action="{{ route('teacher.online_exams.results.finalize', $submission->id) }}" class="d-inline">@csrf<button class="eBtn eBtn-sm eBtn-primary" type="submit">{{ get_phrase('Submit Marking for Review') }}</button></form>
                @endcan
            @endif
            <a class="eBtn eBtn-sm eBtn-secondary" href="{{ route('teacher.online_exams.results', $exam->id) }}">{{ $submission->isResultVisible() ? get_phrase('View Result') : get_phrase('Review') }}</a>
            @if($canReviewProctoring)
                <a class="eBtn eBtn-sm eBtn-warning" href="{{ route('teacher.online_exams.proctoring.review', ['exam' => $exam->id, 'submission_id' => $submission->id]) }}">{{ get_phrase('Proctor') }}</a>
            @endif
        </td>
    </tr>
@empty
    <tr><td colspan="14" class="text-center text-muted">{{ get_phrase('No attempts found') }}</td></tr>
@endforelse
</tbody></table></div>{{ $submissions->links() }}</div>
@endsection
