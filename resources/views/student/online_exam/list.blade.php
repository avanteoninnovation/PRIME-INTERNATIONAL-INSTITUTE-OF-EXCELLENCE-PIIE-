@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Online Exams') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="#">{{ get_phrase('Examination') }}</a></li><li><a href="#">{{ get_phrase('Online Exams') }}</a></li></ul>
        </div>
    </div>
</div></div></div>
<div class="row"><div class="col-12"><div class="eSection-wrap">
    <div class="row g-3">
        @forelse($exams as $exam)
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6>{{ $exam->title }}</h6>
                    <div class="text-muted small mb-2">{{ optional($exam->subject)->name }}</div>
                    <div class="d-flex gap-2 mb-2">
                        <span class="badge bg-info"><i class="bi bi-clock"></i> {{ $exam->duration_minutes }} min</span>
                        <span class="badge bg-secondary">Pass: {{ $exam->pass_mark }}%</span>
                    </div>
                    @if($exam->start_datetime)
                    <div class="small text-muted">{{ get_phrase('Starts') }}: {{ $exam->start_datetime->format('d M Y H:i') }}</div>
                    @endif
                    @if($exam->instructions)
                    <p class="small mt-2">{{ Str::limit($exam->instructions, 80) }}</p>
                    @endif
                    @php $latestSubmission = $exam->submission; @endphp
                    @if($latestSubmission && $latestSubmission->status === 'in_progress')
                        {{-- Was mid-attempt (e.g. disconnected) — must resume the same
                             attempt, never "View Result" on an exam that isn't finished. --}}
                        <a href="{{ route('student.online_exam.take', $exam->id) }}" class="eBtn eBtn-sm eBtn-warning w-100 mt-2">{{ get_phrase('Resume Exam') }}</a>
                    @elseif($latestSubmission)
                        @if($latestSubmission->isResultVisible())
                            <a href="{{ route('student.online_exam.result', $latestSubmission->id) }}" class="eBtn eBtn-sm eBtn-primary w-100 mt-2">{{ get_phrase('View Result') }}</a>
                        @else
                            <button class="eBtn eBtn-sm eBtn-secondary w-100 mt-2" disabled>{{ get_phrase('Results Not Released Yet') }}</button>
                        @endif
                        @if($exam->attempts_used < $exam->max_attempts && (!$exam->end_datetime || now()->lt($exam->end_datetime)))
                            <a href="{{ route('student.online_exam.instructions', $exam->id) }}" class="eBtn eBtn-sm eBtn-dark w-100 mt-2">{{ get_phrase('Attempt Again') }}</a>
                        @endif
                    @elseif($exam->start_datetime && now()->lt($exam->start_datetime))
                        <button class="eBtn eBtn-sm eBtn-secondary w-100 mt-2" disabled>{{ get_phrase('Not Started Yet') }}</button>
                    @elseif($exam->end_datetime && now()->gt($exam->end_datetime))
                        <button class="eBtn eBtn-sm eBtn-secondary w-100 mt-2" disabled>{{ get_phrase('Exam Has Ended') }}</button>
                    @else
                        <a href="{{ route('student.online_exam.instructions', $exam->id) }}" class="eBtn eBtn-sm eBtn-primary w-100 mt-2">{{ get_phrase('Start Exam') }}</a>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center text-muted py-4">{{ get_phrase('No exams available') }}</div>
        @endforelse
    </div>
</div></div></div>
@endsection
