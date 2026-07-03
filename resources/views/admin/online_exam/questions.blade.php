@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Questions') }}: {{ $exam->title }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="{{ route('admin.online_exams.index') }}">{{ get_phrase('Online Exams') }}</a></li><li><a href="#">{{ get_phrase('Questions') }}</a></li></ul>
        </div>
        <div class="export-btn-area">
            <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('admin.online_exams.question_modal', $exam->id) }}', '{{ get_phrase('Add Question') }}')">{{ get_phrase('Add Question') }}</a>
        </div>
    </div>
</div></div></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="row"><div class="col-12"><div class="eSection-wrap">
    @forelse($questions as $i => $q)
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between">
                <div><strong>Q{{ $i+1 }}.</strong> {{ $q->question }}
                    <span class="badge bg-{{ $q->type=='mcq' ? 'primary' : ($q->type=='true_false' ? 'warning' : 'info') }} ms-2">{{ strtoupper($q->type) }}</span>
                    <span class="badge bg-secondary ms-1">{{ $q->marks }} mark(s)</span>
                </div>
                <a href="{{ route('admin.online_exams.questions.destroy', $q->id) }}" class="eBtn eBtn-sm eBtn-danger" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
            </div>
            @if($q->type=='mcq')
            <div class="row mt-2">
                @foreach(['a','b','c','d'] as $opt)
                    @if($q->{'option_'.$opt})
                    <div class="col-6"><small class="text-{{ $q->correct_answer==$opt ? 'success fw-bold' : 'muted' }}">{{ strtoupper($opt) }}: {{ $q->{'option_'.$opt} }}</small></div>
                    @endif
                @endforeach
            </div>
            @elseif($q->type=='true_false')
            <div class="mt-1"><small class="text-success">{{ get_phrase('Answer') }}: {{ ucfirst($q->correct_answer) }}</small></div>
            @endif
        </div>
    </div>
    @empty
    <div class="text-center text-muted py-4">{{ get_phrase('No questions added yet') }}</div>
    @endforelse
</div></div></div>
@endsection
