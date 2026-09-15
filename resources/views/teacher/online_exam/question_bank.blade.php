@extends('teacher.navigation')

@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12 d-flex justify-content-between align-items-center flex-wrap gr-15">
    <h4>{{ get_phrase('Question Bank') }}</h4>
    <div class="d-flex gap-2">
        <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('teacher.online_exams.question_bank.import_modal') }}', '{{ get_phrase('Import Questions') }}')">{{ get_phrase('Import Questions') }}</a>
        <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('teacher.online_exams.question_bank.modal') }}', '{{ get_phrase('Add to Bank') }}')">{{ get_phrase('Add Question') }}</a>
        <a class="export_btn" href="{{ route('teacher.online_exams.index') }}">{{ get_phrase('Back') }}</a>
    </div>
</div></div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if(session('import_warnings') && count(session('import_warnings')))
    <div class="alert alert-warning">
        <strong>{{ get_phrase('Warnings') }}:</strong>
        <ul class="mb-0">
            @foreach(session('import_warnings') as $w)<li>{{ $w }}</li>@endforeach
        </ul>
    </div>
@endif
@if(session('import_errors') && count(session('import_errors')))
    <div class="alert alert-danger">
        <strong>{{ get_phrase('Skipped rows') }}:</strong>
        <ul class="mb-0">
            @foreach(session('import_errors') as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="eSection-wrap mb-3">
    <form method="GET" action="{{ route('teacher.online_exams.question_bank') }}" class="row g-2">
        <div class="col-md-4"><input class="form-control eForm-control" name="search" value="{{ $search }}" placeholder="{{ get_phrase('Search question') }}"></div>
        <div class="col-md-3">
            <select class="form-select eForm-select" name="subject_id">
                <option value="">{{ get_phrase('All subjects') }}</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}" {{ (string) $subjectId === (string) $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><button class="eBtn eBtn-primary" type="submit">{{ get_phrase('Filter') }}</button></div>
    </form>
</div>

<div class="eSection-wrap">
    <table class="table eTable">
        <thead><tr><th>#</th><th>{{ get_phrase('Question') }}</th><th>{{ get_phrase('Subject') }}</th><th>{{ get_phrase('Type') }}</th><th>{{ get_phrase('Marks') }}</th><th>{{ get_phrase('Actions') }}</th></tr></thead>
        <tbody>
        @forelse($questions as $i => $q)
            <tr>
                <td>{{ $questions->firstItem() + $i }}</td>
                <td>{{ $q->question }}</td>
                <td>{{ optional($q->subject)->name ?? '—' }}</td>
                <td>{{ strtoupper($q->type) }}</td>
                <td>{{ $q->marks }}</td>
                <td>
                    @if((int) $q->created_by === (int) auth()->id())
                        <a href="{{ route('teacher.online_exams.question_bank.delete', $q->id) }}" class="eBtn eBtn-sm eBtn-danger" onclick="return confirm('{{ get_phrase('Delete?') }}')"><i class="bi bi-trash"></i></a>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted">{{ get_phrase('No questions available') }}</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $questions->links() }}
</div>
@endsection
