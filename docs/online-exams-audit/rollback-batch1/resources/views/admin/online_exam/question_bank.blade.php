@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Question Bank') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="{{ route('admin.online_exams.index') }}">{{ get_phrase('Online Exams') }}</a></li><li><a href="#">{{ get_phrase('Question Bank') }}</a></li></ul>
        </div>
        <div class="export-btn-area">
            <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('admin.question_bank.modal') }}', '{{ get_phrase('Add to Bank') }}')">{{ get_phrase('Add Question') }}</a>
        </div>
    </div>
</div></div></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="row"><div class="col-12"><div class="eSection-wrap">
    <div class="table-responsive">
        <table class="table eTable">
            <thead><tr><th>#</th><th>{{ get_phrase('Question') }}</th><th>{{ get_phrase('Subject') }}</th><th>{{ get_phrase('Type') }}</th><th>{{ get_phrase('Difficulty') }}</th><th>{{ get_phrase('Marks') }}</th><th>{{ get_phrase('Actions') }}</th></tr></thead>
            <tbody>
            @forelse($questions as $i => $q)
            <tr>
                <td>{{ $questions->firstItem() + $i }}</td>
                <td>{{ Str::limit($q->question, 60) }}</td>
                <td>{{ optional($q->subject)->name ?? '—' }}</td>
                <td><span class="badge bg-primary">{{ strtoupper($q->type) }}</span></td>
                <td><span class="badge bg-{{ $q->difficulty=='hard'?'danger':($q->difficulty=='medium'?'warning':'success') }}">{{ ucfirst($q->difficulty) }}</span></td>
                <td>{{ $q->marks }}</td>
                <td>
                    <a href="{{ route('admin.question_bank.delete', $q->id) }}" class="eBtn eBtn-sm eBtn-danger" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted py-4">{{ get_phrase('Question bank is empty') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $questions->links() }}
</div></div></div>
@endsection
