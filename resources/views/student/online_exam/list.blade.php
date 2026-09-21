@extends('student.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12"><div class="d-flex justify-content-between align-items-center flex-wrap gr-15"><div class="d-flex flex-column"><h4>{{ get_phrase('Online Exams') }}</h4><ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="#">{{ get_phrase('Examination') }}</a></li><li><a href="#">{{ get_phrase('Online Exams') }}</a></li></ul></div></div></div></div></div>
<div class="row"><div class="col-12">
    <div class="eSection-wrap mb-3"><div class="d-flex justify-content-between align-items-center flex-wrap gap-2"><h5 class="mb-0">{{ get_phrase('Available Exams') }}</h5><span class="text-muted small">{{ get_phrase('Eligible exams you can currently start') }}</span></div></div>
    <div class="row g-3 mb-4">
        @forelse($availableExams as $exam)
            @include('student.online_exam._card', ['exam' => $exam])
        @empty
            <div class="col-12"><div class="eSection-wrap text-center text-muted py-4">{{ get_phrase('No available exams') }}</div></div>
        @endforelse
    </div>
    <div class="eSection-wrap mb-3"><div class="d-flex justify-content-between align-items-center flex-wrap gap-2"><h5 class="mb-0">{{ get_phrase('My Attempts & Results') }}</h5><span class="text-muted small">{{ get_phrase('Submitted attempts remain here after the exam closes') }}</span></div></div>
    <div class="row g-3">
        @forelse($attemptedExams as $exam)
            @include('student.online_exam._card', ['exam' => $exam])
        @empty
            <div class="col-12"><div class="eSection-wrap text-center text-muted py-4">{{ get_phrase('No submitted attempts yet') }}</div></div>
        @endforelse
    </div>
</div></div>
@endsection
