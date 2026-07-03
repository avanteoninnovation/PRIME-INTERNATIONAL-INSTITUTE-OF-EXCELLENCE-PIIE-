@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ $exam->title }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="{{ route('student.online_exam.list') }}">{{ get_phrase('Exams') }}</a></li><li><a href="#">{{ get_phrase('Take Exam') }}</a></li></ul>
        </div>
        <div class="text-end">
            <span class="badge bg-danger fs-6" id="timer">{{ $exam->duration_minutes }}:00</span>
        </div>
    </div>
</div></div></div>
<div class="row"><div class="col-lg-9"><div class="eSection-wrap">
    @if($exam->instructions)
    <div class="alert alert-info mb-4"><strong>{{ get_phrase('Instructions') }}:</strong> {{ $exam->instructions }}</div>
    @endif
    <form method="POST" action="{{ route('student.online_exam.submit', $exam->id) }}" id="examForm">
        @csrf
        @foreach($questions as $qi => $q)
        <div class="card mb-3">
            <div class="card-body">
                <p><strong>Q{{ $qi+1 }}.</strong> {{ $q->question }}
                   <span class="badge bg-secondary ms-2">{{ $q->marks }} mark(s)</span></p>
                @if($q->type=='mcq')
                    @foreach(['a','b','c','d'] as $opt)
                        @if($q->{'option_'.$opt})
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="answers[{{ $q->id }}]" value="{{ $opt }}" id="q{{ $q->id }}{{ $opt }}">
                            <label class="form-check-label" for="q{{ $q->id }}{{ $opt }}">{{ strtoupper($opt) }}. {{ $q->{'option_'.$opt} }}</label>
                        </div>
                        @endif
                    @endforeach
                @elseif($q->type=='true_false')
                    <div class="form-check"><input class="form-check-input" type="radio" name="answers[{{ $q->id }}]" value="true" id="q{{ $q->id }}t"><label class="form-check-label" for="q{{ $q->id }}t">True</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="answers[{{ $q->id }}]" value="false" id="q{{ $q->id }}f"><label class="form-check-label" for="q{{ $q->id }}f">False</label></div>
                @else
                    <textarea class="form-control eForm-control" name="answers[{{ $q->id }}]" rows="3" placeholder="{{ get_phrase('Your answer...') }}"></textarea>
                @endif
            </div>
        </div>
        @endforeach
        <div class="text-center mt-4">
            <button type="submit" class="eBtn eBtn-primary" onclick="return confirm('Submit exam? You cannot change answers after submission.')">{{ get_phrase('Submit Exam') }}</button>
        </div>
    </form>
</div></div></div>
@endsection
@push('scripts')
<script>
var totalSeconds = {{ $exam->duration_minutes * 60 }};
var timer = setInterval(function(){
    totalSeconds--;
    var m = Math.floor(totalSeconds/60), s = totalSeconds%60;
    document.getElementById('timer').textContent = (m<10?'0':'')+m+':'+(s<10?'0':'')+s;
    if(totalSeconds<=0){ clearInterval(timer); document.getElementById('examForm').submit(); }
}, 1000);
</script>
@endpush
