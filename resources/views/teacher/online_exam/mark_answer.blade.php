@extends('teacher.navigation')

@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12 d-flex justify-content-between align-items-center">
    <h4>{{ get_phrase('Mark Answer') }}</h4>
    <a class="export_btn" href="{{ route('teacher.online_exams.marking') }}">{{ get_phrase('Back to Queue') }}</a>
</div></div></div>

<div class="eSection-wrap">
    <p>{{ get_phrase('Use the marking queue to mark and progress to next answer.') }}</p>
    <a class="eBtn eBtn-primary" href="{{ route('teacher.online_exams.marking') }}">{{ get_phrase('Open Marking Queue') }}</a>
</div>
@endsection
