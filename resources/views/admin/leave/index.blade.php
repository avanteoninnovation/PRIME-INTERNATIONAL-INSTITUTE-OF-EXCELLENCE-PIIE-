@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Leave Management') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="#">{{ get_phrase('HR') }}</a></li><li><a href="#">{{ get_phrase('Leave') }}</a></li></ul>
        </div>
        <div class="export-btn-area d-flex gap-2">
            <a href="{{ route('admin.leave_types.index') }}" class="export_btn bg-secondary">{{ get_phrase('Leave Types') }}</a>
            <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('admin.leave.open_modal') }}', '{{ get_phrase('Apply Leave') }}')">{{ get_phrase('Apply Leave') }}</a>
        </div>
    </div>
</div></div></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="row"><div class="col-12"><div class="eSection-wrap">
    <div class="table-responsive">
        <table class="table eTable">
            <thead><tr><th>#</th><th>{{ get_phrase('Staff') }}</th><th>{{ get_phrase('Leave Type') }}</th><th>{{ get_phrase('From') }}</th><th>{{ get_phrase('To') }}</th><th>{{ get_phrase('Days') }}</th><th>{{ get_phrase('Status') }}</th><th>{{ get_phrase('Actions') }}</th></tr></thead>
            <tbody>
            @forelse($leaves as $i => $l)
            <tr>
                <td>{{ $leaves->firstItem() + $i }}</td>
                <td>{{ optional($l->user)->name ?? '—' }}</td>
                <td>{{ $l->leave_type }}</td>
                <td>{{ $l->from_date }}</td>
                <td>{{ $l->to_date }}</td>
                <td><span class="badge bg-info">{{ $l->days }}</span></td>
                <td>
                    @if($l->status=='pending')<span class="badge bg-warning">{{ get_phrase('Pending') }}</span>
                    @elseif($l->status=='approved')<span class="badge bg-success">{{ get_phrase('Approved') }}</span>
                    @else<span class="badge bg-danger">{{ get_phrase('Rejected') }}</span>@endif
                </td>
                <td>
                    @if($l->status=='pending')
                        <a href="{{ route('admin.leave.approve', $l->id) }}" class="eBtn eBtn-sm eBtn-primary" onclick="return confirm('Approve?')"><i class="bi bi-check-circle"></i></a>
                        <a href="{{ route('admin.leave.reject', $l->id) }}" class="eBtn eBtn-sm eBtn-warning" onclick="return confirm('Reject?')"><i class="bi bi-x-circle"></i></a>
                    @endif
                    <a href="{{ route('admin.leave.destroy', $l->id) }}" class="eBtn eBtn-sm eBtn-danger" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center text-muted py-4">{{ get_phrase('No leave records') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $leaves->links() }}
</div></div></div>
@endsection
