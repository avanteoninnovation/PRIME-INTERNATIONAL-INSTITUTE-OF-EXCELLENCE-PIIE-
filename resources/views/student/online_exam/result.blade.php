@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Exam Result') }}: {{ $exam->title }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="{{ route('student.online_exam.list') }}">{{ get_phrase('Exams') }}</a></li><li><a href="#">{{ get_phrase('Result') }}</a></li></ul>
        </div>
    </div>
</div></div></div>
<div class="row justify-content-center"><div class="col-md-6"><div class="eSection-wrap text-center">
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
        <div class="col-4"><div class="card"><div class="card-body"><h6 class="text-muted">{{ get_phrase('Score') }}</h6><h4>{{ $submission->score }}/{{ $submission->total_marks }}</h4></div></div></div>
        <div class="col-4"><div class="card"><div class="card-body"><h6 class="text-muted">{{ get_phrase('Percentage') }}</h6><h4>{{ $submission->total_marks > 0 ? round(($submission->score/$submission->total_marks)*100,1) : 0 }}%</h4></div></div></div>
        <div class="col-4"><div class="card"><div class="card-body"><h6 class="text-muted">{{ get_phrase('Pass Mark') }}</h6><h4>{{ $exam->pass_mark }}%</h4></div></div></div>
    </div>
    <a href="{{ route('student.online_exam.list') }}" class="eBtn eBtn-primary">{{ get_phrase('Back to Exams') }}</a>
</div></div></div>
@endsection
