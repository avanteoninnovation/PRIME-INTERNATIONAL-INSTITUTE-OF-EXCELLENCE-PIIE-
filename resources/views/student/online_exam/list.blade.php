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
                    @php $submission = $exam->submissions->where('student_id', auth()->id())->first(); @endphp
                    @if($submission)
                        <a href="{{ route('student.online_exam.result', $exam->id) }}" class="eBtn eBtn-sm eBtn-primary w-100 mt-2">{{ get_phrase('View Result') }}</a>
                    @elseif($exam->start_datetime && now()->lt($exam->start_datetime))
                        <button class="eBtn eBtn-sm eBtn-secondary w-100 mt-2" disabled>{{ get_phrase('Not Started Yet') }}</button>
                    @else
                        <a href="{{ route('student.online_exam.take', $exam->id) }}" class="eBtn eBtn-sm eBtn-primary w-100 mt-2">{{ get_phrase('Start Exam') }}</a>
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
