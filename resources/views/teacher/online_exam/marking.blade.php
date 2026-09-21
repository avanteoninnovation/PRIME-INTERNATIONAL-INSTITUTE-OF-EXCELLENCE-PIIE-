@extends('teacher.navigation')

@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12 d-flex justify-content-between align-items-center">
    <h4>{{ get_phrase('Marking Queue') }}</h4>
    <a class="export_btn" href="{{ route('teacher.online_exams.index') }}">{{ get_phrase('Back') }}</a>
</div></div></div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<style>
    .online-exam-marking-table { min-width: 1180px; }
    .online-exam-marking-table th, .online-exam-marking-table td { vertical-align: middle; }
    .online-exam-marking-actions { min-width: 280px; }
    .online-exam-marking-actions form { flex-wrap: wrap; }
    .online-exam-summary { background: #f8fafc; }
    .online-exam-summary .badge { font-weight: 500; }
</style>

<div class="eSection-wrap mb-3">
    <form method="GET" action="{{ route('teacher.online_exams.marking') }}" class="d-flex gap-2">
        <select class="form-select eForm-select" name="status" style="max-width:220px;">
            <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>{{ get_phrase('Pending') }}</option>
            <option value="marked" {{ $status === 'marked' ? 'selected' : '' }}>{{ get_phrase('Marked') }}</option>
        </select>
        <button class="eBtn eBtn-primary" type="submit">{{ get_phrase('Filter') }}</button>
    </form>
</div>

<div class="eSection-wrap">
    <div class="table-responsive online-exam-marking-wrap">
        <table class="table eTable online-exam-marking-table">
            <thead>
            <tr>
                <th>#</th><th>{{ get_phrase('Student') }}</th><th>{{ get_phrase('Exam') }}</th><th>{{ get_phrase('Question') }}</th><th>{{ get_phrase('Answer') }}</th><th>{{ get_phrase('Max') }}</th><th>{{ get_phrase('Awarded') }}</th><th>{{ get_phrase('Comment') }}</th><th>{{ get_phrase('Status') }}</th><th>{{ get_phrase('Action') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($answers as $index => $answer)
                @php
                    $submission = $answer->submission;
                    $summary = \App\Support\OnlineExams\OnlineExamMarking::summary($submission);
                    $pending = (int) $summary['pending'];
                    $readyToFinalize = $pending === 0 && in_array($submission->status, ['submitted', 'timed_out', 'pending_manual_marking'], true);
                    $markingComplete = $pending === 0 && in_array($submission->status, ['submitted', 'timed_out', 'finalized', 'result_published'], true);
                    $markingLabel = $pending > 0 ? 'Awaiting Marking' : ($submission->isFinalized() ? ($submission->isResultVisible() ? 'Result published' : 'Finalized / not published') : ($markingComplete ? 'Marking Complete' : 'Ready to finalize'));
                    $finalizationLabel = $submission->isFinalized() ? 'Finalized' : 'Not Finalized';
                    $releaseLabel = $submission->isResultVisible() ? 'Released' : 'Not Released';
                @endphp
                <tr>
                    <td>{{ $answers->firstItem() + $index }}</td>
                    <td>{{ optional($answer->submission->student)->name ?? '—' }}</td>
                    <td>{{ $answer->submission->exam->title ?? '—' }}</td>
                    <td>{{ $answer->question->question ?? '—' }}</td>
                    <td>{{ $answer->answer_text ?: ($answer->selected_option ?: '—') }}</td>
                    <td>{{ $answer->question->marks ?? 0 }}</td>
                    <td>{{ is_null($answer->awarded_marks) ? '—' : $answer->awarded_marks }}</td>
                    <td>{{ $answer->teacher_comment ?: '—' }}</td>
                    <td>{{ \App\Support\OnlineExams\OnlineExamMarking::isManuallyMarked($answer) ? 'Marked' : 'Pending' }}</td>
                    <td class="online-exam-marking-actions">
                        <form method="POST" action="{{ route('teacher.online_exams.answers.mark', $answer->id) }}" class="d-flex gap-1">
                            @csrf
                            <input type="hidden" name="answer_id" value="{{ $answer->id }}">
                            <input class="form-control eForm-control" type="number" step="0.01" min="0" max="{{ $answer->question->marks ?? 0 }}" name="awarded_marks" value="{{ old('awarded_marks', $answer->awarded_marks) }}" style="width:90px;">
                            <input class="form-control eForm-control" type="text" name="teacher_comment" value="{{ old('teacher_comment', $answer->teacher_comment) }}" placeholder="{{ get_phrase('Comment') }}">
                            <button class="eBtn eBtn-sm eBtn-primary" type="submit">{{ get_phrase('Save') }}</button>
                        </form>
                    </td>
                </tr>
                <tr class="online-exam-summary">
                    <td colspan="10">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <strong>{{ $submission->student->name ?? '—' }} · {{ $submission->exam->title ?? '—' }}</strong>
                            <span class="badge bg-light text-dark">{{ get_phrase('Automatic') }}: {{ number_format($summary['objective_score'], 2) }}</span>
                            <span class="badge bg-light text-dark">{{ get_phrase('Manual') }}: {{ number_format($summary['manual_score'], 2) }}</span>
                            <span class="badge bg-primary">{{ get_phrase('Total') }}: {{ number_format($summary['score'], 2) }} / {{ number_format($submission->result_total_marks, 2) }}</span>
                            <span class="badge {{ $pending ? 'bg-warning text-dark' : 'bg-success' }}">{{ get_phrase('Pending') }}: {{ $pending }}</span>
                            <span class="badge bg-secondary">{{ get_phrase('Marking') }}: {{ $markingLabel }}</span>
                            <span class="badge bg-secondary">{{ get_phrase('Finalization') }}: {{ $finalizationLabel }}</span>
                            <span class="badge bg-secondary">{{ get_phrase('Release') }}: {{ $releaseLabel }}</span>
                            @if($readyToFinalize)
                                @can('grade', $submission)
                                    <form method="POST" action="{{ route('teacher.online_exams.results.finalize', $submission->id) }}" class="ms-auto">
                                        @csrf
                                        <button class="eBtn eBtn-sm eBtn-primary" type="submit" title="{{ get_phrase('Confirm that marking is complete and lock this result for release.') }}">{{ get_phrase('Finalize Result') }}</button>
                                    </form>
                                @endcan
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-center text-muted">{{ get_phrase('No answers in queue') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $answers->links() }}
</div>
@endsection
