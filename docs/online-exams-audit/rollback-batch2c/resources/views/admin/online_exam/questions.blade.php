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
                <form method="POST" action="{{ route('admin.online_exams.questions.destroy', $q->id) }}" class="d-inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="eBtn eBtn-sm eBtn-danger" type="submit"><i class="bi bi-trash"></i></button></form>
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
            @if(!$exam->isStructurallyLocked())
            <details class="mt-3"><summary class="btn btn-sm btn-outline-secondary">{{ get_phrase('Edit question') }}</summary>
                <form method="POST" action="{{ route('admin.online_exams.questions.update', $q->id) }}" class="row g-2 mt-2">
                    @csrf
                    <input type="hidden" name="question_id" value="{{ $q->id }}">
                    <div class="col-md-5"><input class="form-control" name="question" value="{{ $q->question }}" required></div>
                    <div class="col-md-2"><select class="form-select" name="type"><option value="mcq" @selected($q->type === 'mcq')>MCQ</option><option value="true_false" @selected($q->type === 'true_false')>True/False</option><option value="short" @selected($q->type === 'short')>Short</option><option value="essay" @selected($q->type === 'essay')>Essay</option></select></div>
                    <div class="col-md-1"><input class="form-control" type="number" name="marks" min="0.1" step="0.1" value="{{ $q->marks }}" required></div>
                    <div class="col-md-2">@if($q->type === 'true_false')<select class="form-select" name="correct_ans"><option value="true" @selected(strtolower($q->correct_ans) === 'true')>True</option><option value="false" @selected(strtolower($q->correct_ans) === 'false')>False</option></select>@else<input class="form-control" name="correct_ans" value="{{ $q->correct_ans }}" placeholder="Correct key">@endif</div>
                    <div class="col-md-12"><button class="eBtn eBtn-sm eBtn-primary" type="submit">{{ get_phrase('Save') }}</button></div>
                </form>
            </details>
            @endif
        </div>
    </div>
    @empty
    <div class="text-center text-muted py-4">{{ get_phrase('No questions added yet') }}</div>
    @endforelse
</div></div></div>
@endsection
