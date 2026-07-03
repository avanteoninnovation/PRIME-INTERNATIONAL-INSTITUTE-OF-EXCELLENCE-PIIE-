@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Leave Types') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="{{ route('admin.leave.index') }}">{{ get_phrase('Leave') }}</a></li><li><a href="#">{{ get_phrase('Types') }}</a></li></ul>
        </div>
        <div class="export-btn-area">
            <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('admin.leave_types.modal') }}', '{{ get_phrase('Add Leave Type') }}')">{{ get_phrase('Add Type') }}</a>
        </div>
    </div>
</div></div></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="row"><div class="col-12"><div class="eSection-wrap">
    <div class="table-responsive">
        <table class="table eTable">
            <thead><tr><th>#</th><th>{{ get_phrase('Name') }}</th><th>{{ get_phrase('Max Days/Year') }}</th><th>{{ get_phrase('Paid Leave') }}</th><th>{{ get_phrase('Actions') }}</th></tr></thead>
            <tbody>
            @forelse($types as $i => $t)
            <tr>
                <td>{{ $i+1 }}</td>
                <td><strong>{{ $t->name }}</strong></td>
                <td>{{ $t->max_days }}</td>
                <td><span class="badge bg-{{ $t->is_paid ? 'success':'secondary' }}">{{ $t->is_paid ? get_phrase('Paid') : get_phrase('Unpaid') }}</span></td>
                <td>
                    <a href="{{ route('admin.leave_types.destroy', $t->id) }}" class="eBtn eBtn-sm eBtn-danger" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-muted py-4">{{ get_phrase('No leave types defined') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div></div></div>
@endsection
