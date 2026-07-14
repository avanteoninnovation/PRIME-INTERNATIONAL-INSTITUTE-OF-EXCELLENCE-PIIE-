@extends('teacher.navigation')

@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12 d-flex justify-content-between align-items-center">
    <h4>{{ get_phrase('Question Bank') }}</h4>
    <a class="export_btn" href="{{ route('teacher.online_exams.index') }}">{{ get_phrase('Back') }}</a>
</div></div></div>

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
        <thead><tr><th>#</th><th>{{ get_phrase('Question') }}</th><th>{{ get_phrase('Subject') }}</th><th>{{ get_phrase('Type') }}</th><th>{{ get_phrase('Marks') }}</th></tr></thead>
        <tbody>
        @forelse($questions as $i => $q)
            <tr>
                <td>{{ $questions->firstItem() + $i }}</td>
                <td>{{ $q->question }}</td>
                <td>{{ optional($q->subject)->name ?? '—' }}</td>
                <td>{{ strtoupper($q->type) }}</td>
                <td>{{ $q->marks }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted">{{ get_phrase('No questions available') }}</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $questions->links() }}
</div>
@endsection
