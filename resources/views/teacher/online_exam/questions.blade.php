@extends('teacher.navigation')

@section('content')
<div class="mainSection-title">
    <div class="row">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4>{{ get_phrase('Question Management') }}: {{ $exam->title }}</h4>
            <div class="d-flex gap-2">
                <a class="export_btn bg-secondary" href="{{ route('teacher.online_exams.preview', $exam->id) }}">{{ get_phrase('Preview') }}</a>
                <a class="export_btn" href="{{ route('teacher.online_exams.edit', $exam->id) }}">{{ get_phrase('Back') }}</a>
            </div>
        </div>
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div class="alert alert-info">
    {{ get_phrase('Question marks') }}: <strong>{{ $questionMarksTotal }}</strong> / <strong>{{ $exam->total_marks }}</strong>
</div>

@if(!$structureLocked)
<div class="eSection-wrap mb-3">
    <h6>{{ get_phrase('Add Question') }}</h6>
    @include('teacher.online_exam.question_form')
</div>

<div class="eSection-wrap mb-3">
    <h6>{{ get_phrase('Import From Question Bank') }}</h6>
    <form method="POST" action="{{ route('teacher.online_exams.question_bank.import', $exam->id) }}">
        @csrf
        <div class="table-responsive" style="max-height: 220px; overflow-y:auto;">
            <table class="table eTable">
                <thead><tr><th>{{ get_phrase('Pick') }}</th><th>{{ get_phrase('Question') }}</th><th>{{ get_phrase('Type') }}</th><th>{{ get_phrase('Marks') }}</th></tr></thead>
                <tbody>
                @forelse($bank as $b)
                    <tr>
                        <td><input type="checkbox" name="question_bank_ids[]" value="{{ $b->id }}"></td>
                        <td>{{ $b->question }}</td>
                        <td>{{ strtoupper($b->type) }}</td>
                        <td>{{ $b->marks }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted text-center">{{ get_phrase('No question bank entries available') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <button class="eBtn eBtn-secondary" type="submit">{{ get_phrase('Import Selected') }}</button>
    </form>
</div>
@endif

<div class="eSection-wrap">
    <h6>{{ get_phrase('Exam Questions') }}</h6>
    <form method="POST" action="{{ route('teacher.online_exams.questions.reorder', $exam->id) }}">
        @csrf
        <div class="table-responsive">
            <table class="table eTable">
                <thead><tr><th>{{ get_phrase('Order') }}</th><th>{{ get_phrase('Question') }}</th><th>{{ get_phrase('Type') }}</th><th>{{ get_phrase('Marks') }}</th><th>{{ get_phrase('Actions') }}</th></tr></thead>
                <tbody>
                @forelse($questions as $question)
                    <tr>
                        <td>
                            <input type="hidden" name="question_ids[]" value="{{ $question->id }}">
                            {{ $question->sort_order }}
                        </td>
                        <td>{{ $question->question }}</td>
                        <td>{{ strtoupper($question->type) }}</td>
                        <td>{{ $question->marks }}</td>
                        <td>
                            @if(!$structureLocked)
                                <button type="button" class="eBtn eBtn-sm eBtn-primary" data-bs-toggle="collapse" data-bs-target="#edit_q_{{ $question->id }}">{{ get_phrase('Edit') }}</button>
                                <form method="POST" action="{{ route('teacher.online_exams.questions.destroy', $question->id) }}" class="d-inline" onsubmit="return confirm('{{ get_phrase('Delete question?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="eBtn eBtn-sm eBtn-danger" type="submit">{{ get_phrase('Delete') }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @if(!$structureLocked)
                    <tr class="collapse" id="edit_q_{{ $question->id }}">
                        <td colspan="5">
                            <form method="POST" action="{{ route('teacher.online_exams.questions.update', $question->id) }}" class="row g-2">
                                @csrf
                                @method('PUT')
                                <div class="col-md-4"><input class="form-control eForm-control" name="question" value="{{ $question->question }}" required></div>
                                <div class="col-md-2">
                                    <select class="form-select eForm-select" name="type">
                                        @foreach(['multiple_choice' => 'MCQ', 'true_false' => 'True/False', 'fill_blank' => 'Fill Blank', 'short_answer' => 'Short Answer', 'essay' => 'Essay'] as $typeValue => $label)
                                            <option value="{{ $typeValue }}" {{ $question->normalized_type === str_replace('multiple_choice', 'multiple_choice', $typeValue) ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-1"><input class="form-control eForm-control" name="marks" value="{{ $question->marks }}" type="number" min="0.1" step="0.1" required></div>
                                <div class="col-md-1"><input class="form-control eForm-control" name="correct_ans" value="{{ $question->correct_ans }}" placeholder="Correct"></div>
                                <div class="col-md-1"><input class="form-control eForm-control" name="option_a" value="{{ $question->option_a }}" placeholder="A"></div>
                                <div class="col-md-1"><input class="form-control eForm-control" name="option_b" value="{{ $question->option_b }}" placeholder="B"></div>
                                <div class="col-md-1"><input class="form-control eForm-control" name="option_c" value="{{ $question->option_c }}" placeholder="C"></div>
                                <div class="col-md-1"><input class="form-control eForm-control" name="option_d" value="{{ $question->option_d }}" placeholder="D"></div>
                                <div class="col-md-12"><button class="eBtn eBtn-sm eBtn-primary" type="submit">{{ get_phrase('Save') }}</button></div>
                            </form>
                        </td>
                    </tr>
                    @endif
                @empty
                    <tr><td colspan="5" class="text-muted text-center">{{ get_phrase('No questions yet') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if(!$structureLocked && $questions->count() > 1)
            <button class="eBtn eBtn-secondary" type="submit">{{ get_phrase('Save Current Order') }}</button>
        @endif
    </form>
</div>
@endsection
