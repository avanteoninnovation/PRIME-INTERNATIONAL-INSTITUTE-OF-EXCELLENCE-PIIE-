@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Results & Marking') }}: {{ $exam->title }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="{{ route('admin.online_exams.index') }}">{{ get_phrase('Online Exams') }}</a></li><li><a href="#">{{ get_phrase('Results') }}</a></li></ul>
        </div>
        <a class="export_btn bg-secondary" href="{{ route('admin.online_exams.submissions', $exam->id) }}">{{ get_phrase('All Submissions') }}</a>
    </div>
</div></div></div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

@if($answersNeedingMarking->count() > 0)
<div class="row"><div class="col-12"><div class="eSection-wrap mb-3">
    <h5 class="mb-3">{{ get_phrase('Pending Manual Marking') }} ({{ $answersNeedingMarking->count() }})</h5>
    <div class="table-responsive">
        <table class="table eTable">
            <thead>
            <tr>
                <th>{{ get_phrase('Student') }}</th><th>{{ get_phrase('Question') }}</th><th>{{ get_phrase('Answer') }}</th><th>{{ get_phrase('Max') }}</th><th>{{ get_phrase('Award Marks') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($answersNeedingMarking as $answer)
                <tr>
                    <td>{{ optional($answer->submission->student)->name ?? '—' }}</td>
                    <td>{{ $answer->question->question ?? '—' }}</td>
                    <td>{{ $answer->answer_text ?: ($answer->selected_option ?: '—') }}</td>
                    <td>{{ $answer->question->marks ?? 0 }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.online_exams.answers.manual_mark', $answer->id) }}" class="d-flex gap-1 ajaxForm resetable">
                            @csrf
                            <input type="hidden" name="answer_id" value="{{ $answer->id }}">
                            <input class="form-control eForm-control" type="number" step="0.01" min="0" max="{{ $answer->question->marks ?? 0 }}" name="awarded_marks" value="{{ old('awarded_marks', $answer->awarded_marks) }}" style="width:90px;" required>
                            <input class="form-control eForm-control" type="text" name="teacher_comment" value="{{ old('teacher_comment', $answer->teacher_comment) }}" placeholder="{{ get_phrase('Comment') }}">
                            <button class="eBtn eBtn-sm eBtn-primary" type="submit">{{ get_phrase('Save') }}</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div></div></div>
@endif

<div class="row"><div class="col-12"><div class="eSection-wrap">
    <div class="table-responsive">
        <table class="table eTable">
            <thead>
                <tr>
                    <th>#</th><th>{{ get_phrase('Student') }}</th><th>{{ get_phrase('Score') }}</th><th>{{ get_phrase('Percentage') }}</th><th>{{ get_phrase('Pass/Fail') }}</th><th>{{ get_phrase('Manual Status') }}</th><th>{{ get_phrase('Finalized') }}</th><th>{{ get_phrase('Release') }}</th><th>{{ get_phrase('Submission Method') }}</th><th>{{ get_phrase('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($submissions as $i => $submission)
                    @php
                        $totalMarks = (float) ($submission->total_marks_snapshot ?: $exam->total_marks ?: 0);
                        $score = (float) ($submission->effective_score ?? 0);
                        $percentage = $totalMarks > 0 ? round(($score / $totalMarks) * 100, 2) : 0;
                        $pendingManual = $submission->status === \App\Models\OnlineExamSubmission::STATUS_PENDING_MANUAL;
                        $isFinalized = $submission->status === \App\Models\OnlineExamSubmission::STATUS_FINALIZED;
                    @endphp
                    <tr>
                        <td>{{ $submissions->firstItem() + $i }}</td>
                        <td>{{ optional($submission->student)->name ?? '—' }}</td>
                        <td>{{ number_format($score, 2) }}</td>
                        <td>{{ $percentage }}%</td>
                        <td>{{ is_null($submission->passed) ? '—' : ($submission->passed ? get_phrase('Pass') : get_phrase('Fail')) }}</td>
                        <td>{{ $pendingManual ? get_phrase('Pending Manual Marking') : get_phrase('Completed') }}</td>
                        <td>{{ $isFinalized ? get_phrase('Yes') : get_phrase('No') }}</td>
                        <td>{{ $submission->isResultVisible() ? get_phrase('Released') : get_phrase('Hidden') }}</td>
                        <td>{{ $submission->submitted_via ?: '—' }}</td>
                        <td>
                            <a class="eBtn eBtn-sm eBtn-secondary" href="{{ route('admin.online_exams.proctoring.review', ['id' => $exam->id, 'submission' => $submission->id]) }}">{{ get_phrase('Proctoring') }}</a>
                            @if(!$isFinalized)
                            <form method="POST" action="{{ route('admin.online_exams.submissions.finalize', $submission->id) }}" class="d-inline">
                                @csrf
                                <button class="eBtn eBtn-sm eBtn-primary" type="submit" {{ $pendingManual ? 'disabled title="' . get_phrase('Finish manual marking above first') . '"' : '' }}>{{ get_phrase('Finalize') }}</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">{{ get_phrase('No results found') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $submissions->links() }}
</div></div></div>
@endsection
