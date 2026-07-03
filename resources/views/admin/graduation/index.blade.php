@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Graduation') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="#">{{ get_phrase('Academic') }}</a></li><li><a href="#">{{ get_phrase('Graduation') }}</a></li></ul>
        </div>
        <div class="export-btn-area">
            <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('admin.graduation.open_modal') }}', '{{ get_phrase('Add Application') }}')">{{ get_phrase('Add Application') }}</a>
        </div>
    </div>
</div></div></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="row"><div class="col-12"><div class="eSection-wrap">
    <div class="table-responsive">
        <table class="table eTable">
            <thead><tr><th>#</th><th>{{ get_phrase('Student') }}</th><th>{{ get_phrase('Programme') }}</th><th>{{ get_phrase('GPA') }}</th><th>{{ get_phrase('Classification') }}</th><th>{{ get_phrase('Status') }}</th><th>{{ get_phrase('Actions') }}</th></tr></thead>
            <tbody>
            @forelse($applications as $i => $app)
            <tr>
                <td>{{ $applications->firstItem() + $i }}</td>
                <td>{{ optional($app->student)->name }}</td>
                <td>{{ optional($app->programme)->name ?? '—' }}</td>
                <td>{{ $app->gpa ?? '—' }}</td>
                <td><span class="badge bg-info">{{ $app->classification ?? '—' }}</span></td>
                <td><span class="badge bg-{{ $app->status=='graduated'?'success':($app->status=='approved'?'primary':($app->status=='pending'?'warning':'danger')) }}">{{ ucfirst($app->status) }}</span></td>
                <td>
                    @if($app->status=='pending')
                        <a href="{{ route('admin.graduation.approve', $app->id) }}" class="eBtn eBtn-sm eBtn-primary" onclick="return confirm('Approve?')"><i class="bi bi-check-circle"></i></a>
                    @endif
                    @if($app->status=='approved')
                        <a href="{{ route('admin.graduation.graduate', $app->id) }}" class="eBtn eBtn-sm eBtn-success" onclick="return confirm('Mark as Graduated?')"><i class="bi bi-mortarboard"></i></a>
                    @endif
                    <a href="{{ route('admin.graduation.destroy', $app->id) }}" class="eBtn eBtn-sm eBtn-danger" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted py-4">{{ get_phrase('No graduation applications') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $applications->links() }}
</div></div></div>
@endsection
