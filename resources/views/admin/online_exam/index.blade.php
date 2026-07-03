@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Online Exams / CBT') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="#">{{ get_phrase('Examination') }}</a></li><li><a href="#">{{ get_phrase('Online Exams') }}</a></li></ul>
        </div>
        <div class="export-btn-area d-flex gap-2">
            <a href="{{ route('admin.question_bank.index') }}" class="export_btn bg-secondary">{{ get_phrase('Question Bank') }}</a>
            <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('admin.online_exams.open_modal') }}', '{{ get_phrase('Create Exam') }}')">{{ get_phrase('Create Exam') }}</a>
        </div>
    </div>
</div></div></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="row"><div class="col-12"><div class="eSection-wrap">
    <div class="table-responsive">
        <table class="table eTable">
            <thead><tr><th>#</th><th>{{ get_phrase('Title') }}</th><th>{{ get_phrase('Subject') }}</th><th>{{ get_phrase('Duration') }}</th><th>{{ get_phrase('Start') }}</th><th>{{ get_phrase('Status') }}</th><th>{{ get_phrase('Actions') }}</th></tr></thead>
            <tbody>
            @forelse($exams as $i => $exam)
            <tr>
                <td>{{ $exams->firstItem() + $i }}</td>
                <td><strong>{{ $exam->title }}</strong></td>
                <td>{{ optional($exam->subject)->name ?? '—' }}</td>
                <td>{{ $exam->duration_minutes }} min</td>
                <td>{{ $exam->start_datetime ? $exam->start_datetime->format('d M Y H:i') : '—' }}</td>
                <td>
                    @if($exam->is_published)
                        <span class="badge bg-success">{{ get_phrase('Published') }}</span>
                    @else
                        <span class="badge bg-secondary">{{ get_phrase('Draft') }}</span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('admin.online_exams.questions', $exam->id) }}" class="eBtn eBtn-sm eBtn-primary"><i class="bi bi-list-ol"></i> {{ get_phrase('Questions') }}</a>
                    <a href="{{ route('admin.online_exams.submissions', $exam->id) }}" class="eBtn eBtn-sm eBtn-warning"><i class="bi bi-people"></i></a>
                    @if(!$exam->is_published)
                        <a href="{{ route('admin.online_exams.publish', $exam->id) }}" class="eBtn eBtn-sm eBtn-primary" onclick="return confirm('Publish?')"><i class="bi bi-send"></i></a>
                    @endif
                    <a href="javascript:;" class="eBtn eBtn-sm eBtn-primary" onclick="rightModal('{{ route('admin.online_exams.open_modal', ['id'=>$exam->id]) }}', '{{ get_phrase('Edit Exam') }}')"><i class="bi bi-pencil"></i></a>
                    <a href="{{ route('admin.online_exams.destroy', $exam->id) }}" class="eBtn eBtn-sm eBtn-danger" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted py-4">{{ get_phrase('No exams created') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $exams->links() }}
</div></div></div>
@endsection
