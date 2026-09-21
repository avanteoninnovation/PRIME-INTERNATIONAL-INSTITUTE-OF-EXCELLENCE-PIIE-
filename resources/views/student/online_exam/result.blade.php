@extends('student.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Exam Result') }}: {{ $exam->title }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="{{ route('student.online_exam.list') }}">{{ get_phrase('Exams') }}</a></li><li><a href="#">{{ get_phrase('Result') }}</a></li></ul>
        </div>
    </div>
</div></div></div>
<div class="row justify-content-center online-exam-result-page"><div class="col-lg-8"><div class="eSection-wrap text-center">
    <div class="mb-3">
        <h5>{{ $exam->title }}</h5>
        <div class="text-muted">{{ get_phrase('Course') }}: {{ optional($exam->subject)->name ?? '—' }}</div>
        <div class="text-muted">{{ get_phrase('Student') }}: {{ auth()->user()->name }}</div>
        <div class="text-muted">{{ get_phrase('Submitted') }}: {{ optional($submission->submitted_at)->format('d M Y H:i') ?? '—' }}</div>
    </div>
    <div class="my-4">
        @if($submission->passed)
            <div class="display-1 text-success"><i class="bi bi-trophy-fill"></i></div>
            <h3 class="text-success mt-2">{{ get_phrase('Congratulations! You Passed!') }}</h3>
        @else
            <div class="display-1 text-danger"><i class="bi bi-x-circle-fill"></i></div>
            <h3 class="text-danger mt-2">{{ get_phrase('Sorry, You Did Not Pass') }}</h3>
        @endif
    </div>
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-4"><div class="card"><div class="card-body"><h6 class="text-muted">{{ get_phrase('Score') }}</h6><h4>{{ $submission->score }}/{{ $submission->result_total_marks }}</h4></div></div></div>
        <div class="col-12 col-sm-4"><div class="card"><div class="card-body"><h6 class="text-muted">{{ get_phrase('Percentage') }}</h6><h4>{{ $submission->result_total_marks > 0 ? round(($submission->score/$submission->result_total_marks)*100,1) : 0 }}%</h4></div></div></div>
        <div class="col-12 col-sm-4"><div class="card"><div class="card-body"><h6 class="text-muted">{{ get_phrase('Pass Mark') }}</h6><h4>{{ $exam->pass_mark }} {{ get_phrase('marks') }}</h4></div></div></div>
    </div>
    <div class="row g-3 mb-4 text-start">
        <div class="col-md-6"><div class="card"><div class="card-body"><strong>{{ get_phrase('Automatic Marks') }}</strong><div>{{ number_format((float) ($submission->objective_score ?? 0), 2) }}</div></div></div></div>
        <div class="col-md-6"><div class="card"><div class="card-body"><strong>{{ get_phrase('Manual Marks') }}</strong><div>{{ number_format((float) ($submission->manual_score ?? 0), 2) }}</div></div></div></div>
    </div>
    <a href="{{ route('student.online_exam.list') }}" class="eBtn eBtn-primary">{{ get_phrase('Back to Exams') }}</a>
</div></div></div>
@endsection
