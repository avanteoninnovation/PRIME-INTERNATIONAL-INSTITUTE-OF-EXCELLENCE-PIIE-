@extends('teacher.navigation')

@section('content')
<div class="mainSection-title">
    <div class="row">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4>{{ $exam->title }}</h4>
            <div class="d-flex gap-2">
                <a class="export_btn bg-secondary" href="{{ route('teacher.online_exams.preview', $exam->id) }}">{{ get_phrase('Preview') }}</a>
                <a class="export_btn" href="{{ route('teacher.online_exams.edit', $exam->id) }}">{{ get_phrase('Edit') }}</a>
            </div>
        </div>
    </div>
</div>

@if(!empty($readinessErrors))
    <div class="alert alert-warning">
        <strong>{{ get_phrase('Readiness issues') }}:</strong>
        <ul class="mb-0 mt-2">@foreach($readinessErrors as $issue)<li>{{ $issue }}</li>@endforeach</ul>
    </div>
@endif

<div class="eSection-wrap">
    <div class="row">
        <div class="col-md-6">
            <p><strong>{{ get_phrase('Subject') }}:</strong> {{ optional($exam->subject)->name ?? '—' }}</p>
            <p><strong>{{ get_phrase('Class') }}:</strong> {{ optional($exam->classRoom)->name ?? '—' }}</p>
            <p><strong>{{ get_phrase('Workflow') }}:</strong> {{ ucfirst(str_replace('_', ' ', $exam->workflow_state ?? 'draft')) }}</p>
            <p><strong>{{ get_phrase('Lifecycle') }}:</strong> {{ ucfirst(str_replace('_', ' ', $exam->lifecycle_status)) }}</p>
        </div>
        <div class="col-md-6">
            <p><strong>{{ get_phrase('Duration') }}:</strong> {{ $exam->duration_mins }} {{ get_phrase('minutes') }}</p>
            <p><strong>{{ get_phrase('Total Marks') }}:</strong> {{ $exam->total_marks }}</p>
            <p><strong>{{ get_phrase('Pass Mark') }}:</strong> {{ $exam->pass_mark }}</p>
            <p><strong>{{ get_phrase('Questions') }}:</strong> {{ $exam->questions->count() }}</p>
            <p><strong>{{ get_phrase('Attempts') }}:</strong> {{ $exam->submissions->count() }}</p>
        </div>
    </div>

    <hr>
    <h6>{{ get_phrase('Instructions') }}</h6>
    <p>{{ $exam->instructions ?: '—' }}</p>
</div>
@endsection
