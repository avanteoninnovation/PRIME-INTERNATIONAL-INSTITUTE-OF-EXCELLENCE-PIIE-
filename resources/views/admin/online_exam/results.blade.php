@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12"><div class="d-flex justify-content-between align-items-center flex-wrap gr-15"><div class="d-flex flex-column"><h4>{{ get_phrase('Results & Marking') }}: {{ $exam->title }}</h4><ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="{{ route('admin.online_exams.index') }}">{{ get_phrase('Online Exams') }}</a></li><li><a href="#">{{ get_phrase('Results') }}</a></li></ul></div><a class="export_btn bg-secondary" href="{{ route('admin.online_exams.submissions', $exam->id) }}">{{ get_phrase('All Submissions') }}</a></div></div></div></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="alert alert-info">{{ get_phrase('Administrators review completed marking and publish official results. Students cannot see marks before publication.') }}</div>
@if($answersNeedingMarking->count() > 0)
<div class="row"><div class="col-12"><div class="eSection-wrap mb-3"><h5 class="mb-3">{{ get_phrase('Pending Manual Marking') }} ({{ $answersNeedingMarking->count() }})</h5><div class="table-responsive"><table class="table eTable"><thead><tr><th>{{ get_phrase('Student') }}</th><th>{{ get_phrase('Question') }}</th><th>{{ get_phrase('Answer') }}</th><th>{{ get_phrase('Max') }}</th><th>{{ get_phrase('Award Marks') }}</th></tr></thead><tbody>
@foreach($answersNeedingMarking as $answer)<tr><td>{{ optional($answer->submission->student)->name ?? '—' }}</td><td>{{ $answer->question->question ?? '—' }}</td><td>{{ $answer->answer_text ?: ($answer->selected_option ?: '—') }}</td><td>{{ $answer->question->marks ?? 0 }}</td><td><form method="POST" action="{{ route('admin.online_exams.answers.manual_mark', $answer->id) }}" class="d-flex gap-1 ajaxForm resetable">@csrf<input type="hidden" name="answer_id" value="{{ $answer->id }}"><input class="form-control eForm-control" type="number" step="0.01" min="0" max="{{ $answer->question->marks ?? 0 }}" name="awarded_marks" value="{{ old('awarded_marks', $answer->awarded_marks) }}" style="width:90px" required><input class="form-control eForm-control" type="text" name="teacher_comment" value="{{ old('teacher_comment', $answer->teacher_comment) }}" placeholder="{{ get_phrase('Comment') }}"><button class="eBtn eBtn-sm eBtn-primary" type="submit">{{ get_phrase('Save') }}</button></form></td></tr>@endforeach
</tbody></table></div></div></div></div>
@endif
<style>
    .online-exam-admin-results-table { min-width: 1250px; }
    .online-exam-admin-results-table th, .online-exam-admin-results-table td { vertical-align: middle; white-space: nowrap; }
    .online-exam-admin-actions { min-width: 300px; white-space: normal !important; }
</style>
<div class="row"><div class="col-12"><div class="eSection-wrap"><div class="table-responsive online-exam-results-wrap"><table class="table eTable online-exam-admin-results-table"><thead><tr><th>#</th><th>{{ get_phrase('Student') }}</th><th>{{ get_phrase('Submitted') }}</th><th>{{ get_phrase('Automatic') }}</th><th>{{ get_phrase('Manual') }}</th><th>{{ get_phrase('Total') }}</th><th>{{ get_phrase('Maximum') }}</th><th>{{ get_phrase('Pending') }}</th><th>{{ get_phrase('Marking Status') }}</th><th>{{ get_phrase('Finalization') }}</th><th>{{ get_phrase('Release') }}</th><th>{{ get_phrase('Actions') }}</th></tr></thead><tbody>
@forelse($submissions as $i => $submission)
@php
    $summary = \App\Support\OnlineExams\OnlineExamMarking::summary($submission);
    $pending = (int) $summary['pending'];
    $finalized = $submission->isFinalized();
    $ready = $pending === 0 && in_array($submission->status, ['submitted', 'timed_out', 'pending_manual_marking'], true);
    $reviewState = $submission->result_review_state ?? ($finalized ? 'pending_review' : 'not_ready');
    $markingComplete = $pending === 0 && in_array($submission->status, ['submitted', 'timed_out', 'finalized', 'result_published'], true);
    $markingLabel = $pending > 0 ? 'Awaiting Marking' : ($reviewState === 'published' ? 'Result published' : ($reviewState === 'pending_review' ? 'Pending Admin Review' : ($reviewState === 'returned_for_correction' ? 'Returned for Correction' : ($markingComplete ? 'Marking Complete' : 'Ready to finalize'))));
@endphp
<tr id="submission-{{ $submission->id }}" class="{{ ($highlightSubmissionId ?? 0) === (int) $submission->id ? 'table-warning' : '' }}"><td>{{ $submissions->firstItem()+$i }}</td><td>{{ optional($submission->student)->name ?? '—' }}</td><td>{{ optional($submission->submitted_at)->format('d M Y H:i') ?? '—' }}</td><td>{{ number_format($summary['objective_score'],2) }}</td><td>{{ number_format($summary['manual_score'],2) }}</td><td>{{ number_format($summary['score'],2) }}</td><td>{{ number_format($submission->result_total_marks,2) }}</td><td>{{ $pending }}</td><td>{{ $markingLabel }}</td><td>{{ $reviewState }}</td><td>{{ $submission->isResultVisible() ? 'Released' : 'Not Released' }}</td><td class="online-exam-admin-actions"><a class="eBtn eBtn-sm eBtn-info" href="{{ route('admin.online_exams.proctoring.review',['id'=>$exam->id,'submission'=>$submission->id]) }}">{{ get_phrase('Review') }}</a>@can('grade',$submission) @if($ready)<form method="POST" action="{{ route('admin.online_exams.submissions.finalize',$submission->id) }}" class="d-inline">@csrf<button class="eBtn eBtn-sm eBtn-primary" type="submit">{{ get_phrase('Finalize Result') }}</button></form>@endif @if($finalized && $reviewState==='pending_review')<form method="POST" action="{{ route('admin.online_exams.submissions.return',$submission->id) }}" class="d-inline">@csrf<button class="eBtn eBtn-sm eBtn-warning" type="submit">{{ get_phrase('Return for Correction') }}</button></form><form method="POST" action="{{ route('admin.online_exams.submissions.publish_result',$submission->id) }}" class="d-inline">@csrf<button class="eBtn eBtn-sm eBtn-primary" type="submit">{{ get_phrase('Approve & Publish Result') }}</button></form>@endif @if($submission->isResultVisible())<span class="badge bg-success">{{ get_phrase('Released') }}</span>@endif @endcan</td></tr>
@empty
<tr><td colspan="12" class="text-center text-muted py-4">{{ get_phrase('No results found') }}</td></tr>
@endforelse
</tbody></table></div>{{ $submissions->links() }}</div></div></div>
@endsection
