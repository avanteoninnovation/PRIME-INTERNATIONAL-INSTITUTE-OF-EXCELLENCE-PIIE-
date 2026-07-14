@extends('teacher.navigation')

@section('content')
<div class="mainSection-title">
    <div class="row">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4>{{ get_phrase('Edit Online Exam') }}: {{ $exam->title }}</h4>
            <div class="d-flex gap-2">
                <a class="export_btn bg-secondary" href="{{ route('teacher.online_exams.preview', $exam->id) }}">{{ get_phrase('Preview') }}</a>
                <a class="export_btn" href="{{ route('teacher.online_exams.index') }}">{{ get_phrase('Back') }}</a>
            </div>
        </div>
    </div>
</div>

<div class="eSection-wrap">
    @include('teacher.online_exam._form')
</div>
@endsection
