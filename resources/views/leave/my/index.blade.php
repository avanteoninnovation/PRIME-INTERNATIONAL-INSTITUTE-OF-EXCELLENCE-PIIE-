@php
    $leaveNavByRole = [
        3  => 'teacher.navigation',
        4  => 'accountant.navigation',
        5  => 'librarian.navigation',
        10 => 'warden.navigation',
    ];
    $leaveLayout = $leaveNavByRole[auth()->user()->role_id] ?? 'admin.navigation';
@endphp
@extends($leaveLayout)
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('My Leave') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="#">{{ get_phrase('HR') }}</a></li><li><a href="#">{{ get_phrase('My Leave') }}</a></li></ul>
        </div>
        <div class="export-btn-area">
            <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('staff.leave.open_modal') }}', '{{ get_phrase('Apply Leave') }}')">{{ get_phrase('Apply Leave') }}</a>
        </div>
    </div>
</div></div></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<div class="row"><div class="col-12"><div class="eSection-wrap">
    <div class="table-responsive">
        <table class="table eTable">
            <thead><tr><th>#</th><th>{{ get_phrase('Leave Type') }}</th><th>{{ get_phrase('From') }}</th><th>{{ get_phrase('To') }}</th><th>{{ get_phrase('Days') }}</th><th>{{ get_phrase('Reason') }}</th><th>{{ get_phrase('Status') }}</th><th>{{ get_phrase('Decision Comment') }}</th></tr></thead>
            <tbody>
            @forelse($leaves as $i => $l)
            <tr>
                <td>{{ $leaves->firstItem() + $i }}</td>
                <td>{{ $l->leave_type ?? '—' }}</td>
                <td>{{ $l->from_date }}</td>
                <td>{{ $l->to_date }}</td>
                <td><span class="badge bg-info">{{ $l->days }}</span></td>
                <td>{{ $l->reason ?? '—' }}</td>
                <td>
                    @if($l->status=='pending')<span class="badge bg-warning">{{ get_phrase('Pending') }}</span>
                    @elseif($l->status=='approved')<span class="badge bg-success">{{ get_phrase('Approved') }}</span>
                    @elseif($l->status=='returned')<span class="badge bg-info">{{ get_phrase('Returned') }}</span>
                    @else<span class="badge bg-danger">{{ get_phrase('Denied') }}</span>@endif
                </td>
                <td>
                    @if($l->admin_comment)
                        <div>{{ $l->admin_comment }}</div>
                        <small class="text-muted">
                            {{ get_phrase('By') }} {{ optional($l->approver)->name ?? '—' }},
                            {{ optional($l->updated_at)->format('d M Y, h:i A') }}
                        </small>
                    @else
                        —
                    @endif
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
