@extends('teacher.navigation')

@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12 d-flex justify-content-between align-items-center">
    <h4>{{ get_phrase('Results') }}: {{ $exam->title }}</h4>
    <a class="export_btn" href="{{ route('teacher.online_exams.attempts', $exam->id) }}">{{ get_phrase('Back to Attempts') }}</a>
</div></div></div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="alert alert-info">{{ get_phrase('Submit Marking for Review sends completed marking to an administrator. Only an administrator can publish the official result.') }}</div>

<style>
    .online-exam-results-table { min-width: 1250px; }
    .online-exam-results-table th, .online-exam-results-table td { vertical-align: middle; white-space: nowrap; }
    .online-exam-results-actions { min-width: 300px; white-space: normal !important; }
</style>

<div class="eSection-wrap">
    <div class="table-responsive online-exam-results-wrap">
    <table class="table eTable online-exam-results-table">
        <thead><tr>
            <th>#</th><th>{{ get_phrase('Student') }}</th><th>{{ get_phrase('Submitted') }}</th><th>{{ get_phrase('Automatic') }}</th><th>{{ get_phrase('Manual') }}</th><th>{{ get_phrase('Total') }}</th><th>{{ get_phrase('Maximum') }}</th><th>{{ get_phrase('Pending') }}</th><th>{{ get_phrase('Marking Status') }}</th><th>{{ get_phrase('Finalization') }}</th><th>{{ get_phrase('Release') }}</th><th>{{ get_phrase('Actions') }}</th>
        </tr></thead>
        <tbody>
        @forelse($submissions as $i => $submission)
            @php
                $totalMarks = $submission->result_total_marks;
                $summary = \App\Support\OnlineExams\OnlineExamMarking::summary($submission);
                $score = (float) ($summary['score'] ?? $submission->effective_score ?? 0);
                $percentage = $totalMarks > 0 ? round(($score / $totalMarks) * 100, 2) : 0;
                $pendingManual = (int) $summary['pending'];
                $isFinalized = $submission->isFinalized();
                $readyToFinalize = $pendingManual === 0 && in_array($submission->status, ['submitted', 'timed_out', 'pending_manual_marking'], true);
                $reviewState = $submission->result_review_state ?? ($isFinalized ? 'pending_review' : 'not_ready');
                $markingComplete = $pendingManual === 0 && in_array($submission->status, ['submitted', 'timed_out', 'finalized', 'result_published'], true);
                $markingLabel = $pendingManual > 0 ? 'Awaiting Marking' : ($reviewState === 'published' ? 'Result published' : ($reviewState === 'pending_review' ? 'Pending Admin Review' : ($reviewState === 'returned_for_correction' ? 'Returned for Correction' : ($markingComplete ? 'Marking Complete' : 'Ready to finalize'))));
            @endphp
            <tr>
                <td>{{ $submissions->firstItem() + $i }}</td>
                <td>{{ optional($submission->student)->name ?? '—' }}</td>
                <td>{{ optional($submission->submitted_at)->format('d M Y H:i') ?? '—' }}</td>
                <td>{{ number_format($summary['objective_score'], 2) }}</td>
                <td>{{ number_format($summary['manual_score'], 2) }}</td>
                <td>{{ number_format($score, 2) }} <small class="text-muted">({{ $percentage }}%)</small></td>
                <td>{{ number_format($totalMarks, 2) }}</td>
                <td>{{ $pendingManual }}</td>
                <td>{{ $markingLabel }}</td>
                <td>{{ $isFinalized ? 'Finalized' : 'Not Finalized' }}</td>
                <td>{{ $submission->isResultVisible() ? 'Released' : 'Not Released' }}</td>
                <td class="online-exam-results-actions">
                    @if($pendingManual > 0)
                        <a class="eBtn eBtn-sm eBtn-info" href="{{ route('teacher.online_exams.marking') }}">{{ get_phrase('Continue Marking') }}</a>
                    @elseif(!$isFinalized)
                        <a class="eBtn eBtn-sm eBtn-secondary" href="{{ route('teacher.online_exams.marking') }}">{{ get_phrase('Review') }}</a>
                    @endif
                    @can('grade', $submission)
                    @if($readyToFinalize && !$isFinalized)
                        <form method="POST" action="{{ route('teacher.online_exams.results.finalize', $submission->id) }}" class="d-inline">
                            @csrf
                            <button class="eBtn eBtn-sm eBtn-primary" type="submit" title="{{ get_phrase('Send completed marking to Admin review.') }}">{{ get_phrase('Submit Marking for Review') }}</button>
                        </form>
                    @endif
                    @if($reviewState === 'pending_review')<span class="badge bg-warning text-dark">{{ get_phrase('Awaiting Admin Review') }}</span>@endif
                    @if($submission->isResultVisible())<span class="badge bg-success">{{ get_phrase('View Result') }}</span>@endif
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="12" class="text-center text-muted">{{ get_phrase('No results found') }}</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    {{ $submissions->links() }}
</div>
@endsection
