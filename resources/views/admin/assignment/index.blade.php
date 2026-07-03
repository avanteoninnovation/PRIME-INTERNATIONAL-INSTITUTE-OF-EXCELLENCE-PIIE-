@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Assignments') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="#">{{ get_phrase('Academic') }}</a></li><li><a href="#">{{ get_phrase('Assignments') }}</a></li></ul>
        </div>
        <div class="export-btn-area">
            <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('admin.assignments.open_modal') }}', '{{ get_phrase('Create Assignment') }}')">{{ get_phrase('Create Assignment') }}</a>
        </div>
    </div>
</div></div></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="row"><div class="col-12"><div class="eSection-wrap">
    <div class="table-responsive">
        <table class="table eTable">
            <thead><tr><th>#</th><th>{{ get_phrase('Title') }}</th><th>{{ get_phrase('Subject') }}</th><th>{{ get_phrase('Teacher') }}</th><th>{{ get_phrase('Due Date') }}</th><th>{{ get_phrase('Max Marks') }}</th><th>{{ get_phrase('Submissions') }}</th><th>{{ get_phrase('Actions') }}</th></tr></thead>
            <tbody>
            @forelse($assignments as $i => $a)
            <tr>
                <td>{{ $assignments->firstItem() + $i }}</td>
                <td><strong>{{ $a->title }}</strong></td>
                <td>{{ optional($a->subject)->name ?? '—' }}</td>
                <td>{{ optional($a->teacher)->name ?? '—' }}</td>
                <td class="{{ $a->due_date && $a->due_date->isPast() ? 'text-danger' : '' }}">{{ $a->due_date?->format('d M Y') }}</td>
                <td>{{ $a->total_marks }}</td>
                <td><span class="badge bg-info">{{ $a->submissions_count ?? 0 }}</span></td>
                <td>
                    <a href="{{ route('admin.assignments.submissions', $a->id) }}" class="eBtn eBtn-sm eBtn-primary"><i class="bi bi-inbox"></i></a>
                    <a href="javascript:;" class="eBtn eBtn-sm eBtn-warning" onclick="rightModal('{{ route('admin.assignments.open_modal', ['id'=>$a->id]) }}', '{{ get_phrase('Edit') }}')"><i class="bi bi-pencil"></i></a>
                    <a href="{{ route('admin.assignments.destroy', $a->id) }}" class="eBtn eBtn-sm eBtn-danger" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center text-muted py-4">{{ get_phrase('No assignments created') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $assignments->links() }}
</div></div></div>
@endsection
