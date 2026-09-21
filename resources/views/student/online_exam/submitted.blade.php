@extends('student.navigation')

@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Exam Submitted') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2">
                <li><a href="{{ route('student.online_exam.list') }}">{{ get_phrase('Exams') }}</a></li>
                <li><a href="#">{{ get_phrase('Submission status') }}</a></li>
            </ul>
        </div>
    </div>
</div></div></div>

<div class="row justify-content-center"><div class="col-lg-8"><div class="eSection-wrap">
    <div class="alert alert-success">
        <h5>{{ get_phrase('Exam submitted successfully.') }}</h5>
        @if($submission->status === \App\Models\OnlineExamSubmission::STATUS_PENDING_MANUAL)
            <p class="mb-0">{{ get_phrase('Some answers are awaiting marking. Your result will be available after marking and publication.') }}</p>
        @elseif($submission->status === \App\Models\OnlineExamSubmission::STATUS_SUBMITTED)
            <p class="mb-0">{{ get_phrase('Marking is complete. Your result is awaiting publication after finalization.') }}</p>
        @elseif($submission->status === \App\Models\OnlineExamSubmission::STATUS_FINALIZED)
            <p class="mb-0">{{ get_phrase('Your result is finalized and awaiting publication.') }}</p>
        @else
            <p class="mb-0">{{ get_phrase('Your result is awaiting publication.') }}</p>
        @endif
    </div>
    <p class="text-muted">{{ $submission->exam->title }}</p>
    <a href="{{ route('student.online_exam.list') }}" class="eBtn eBtn-primary">{{ get_phrase('Back to Exams') }}</a>
</div></div></div>
@endsection
