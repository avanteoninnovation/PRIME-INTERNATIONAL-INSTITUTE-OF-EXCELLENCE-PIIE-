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
            <thead><tr><th>#</th><th>{{ get_phrase('Staff') }}</th><th>{{ get_phrase('Leave Type') }}</th><th>{{ get_phrase('From') }}</th><th>{{ get_phrase('To') }}</th><th>{{ get_phrase('Days') }}</th><th>{{ get_phrase('Reason') }}</th><th>{{ get_phrase('Status') }}</th><th>{{ get_phrase('Decision Comment') }}</th><th>{{ get_phrase('Actions') }}</th></tr></thead>
            <tbody>
            @forelse($leaves as $i => $l)
            <tr>
                <td>{{ $leaves->firstItem() + $i }}</td>
                <td>{{ optional($l->user)->name ?? '—' }}</td>
                <td>{{ $l->leave_type }}</td>
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
                <td>
                    @if($l->status=='pending')
                        <button type="button" class="eBtn eBtn-sm eBtn-primary" title="{{ get_phrase('Approve') }}"
                            onclick="openLeaveDecisionModal('approve', {{ $l->id }}, '{{ optional($l->user)->name }}')">
                            <i class="bi bi-check-circle"></i>
                        </button>
                        <button type="button" class="eBtn eBtn-sm eBtn-warning" title="{{ get_phrase('Return') }}"
                            onclick="openLeaveDecisionModal('return', {{ $l->id }}, '{{ optional($l->user)->name }}')">
                            <i class="bi bi-arrow-return-left"></i>
                        </button>
                        <button type="button" class="eBtn eBtn-sm eBtn-danger" title="{{ get_phrase('Deny') }}"
                            onclick="openLeaveDecisionModal('reject', {{ $l->id }}, '{{ optional($l->user)->name }}')">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    @endif
                    <a href="{{ route('admin.leave.destroy', $l->id) }}" class="eBtn eBtn-sm eBtn-danger" title="{{ get_phrase('Delete') }}" onclick="return confirm('{{ get_phrase('Delete this leave request?') }}')"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
            @empty
            <tr><td colspan="10" class="text-center text-muted py-4">{{ get_phrase('No leave records') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $leaves->links() }}
</div></div></div>

{{-- Leave decision modal: shared by Approve / Return / Deny actions --}}
<div class="modal fade" id="leaveDecisionModal" tabindex="-1" aria-labelledby="leaveDecisionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="leaveDecisionForm" method="POST" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="leaveDecisionModalLabel">{{ get_phrase('Confirm Action') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="leaveDecisionMessage"></p>
                    <div class="fpb-7">
                        <label class="eForm-label" id="leaveDecisionCommentLabel">{{ get_phrase('Comment') }}</label>
                        <textarea class="form-control eForm-control" name="comment" id="leaveDecisionComment" rows="3"></textarea>
                        <small class="text-muted" id="leaveDecisionCommentHint"></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ get_phrase('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary" id="leaveDecisionSubmit">{{ get_phrase('Confirm') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const leaveDecisionRoutes = {
        approve: "{{ route('admin.leave.approve', ['id' => '__ID__']) }}",
        return:  "{{ route('admin.leave.return',  ['id' => '__ID__']) }}",
        reject:  "{{ route('admin.leave.reject',  ['id' => '__ID__']) }}",
    };

    const leaveDecisionConfig = {
        approve: {
            title: '{{ get_phrase('Approve Leave') }}',
            message: '{{ get_phrase('Approve leave request for') }}',
            required: false,
            hint: '{{ get_phrase('Optional: add an approval note.') }}',
            btnClass: 'btn-primary',
            btnLabel: '{{ get_phrase('Approve') }}',
        },
        return: {
            title: '{{ get_phrase('Return Leave') }}',
            message: '{{ get_phrase('Return leave request for') }}',
            required: true,
            hint: '{{ get_phrase('Required: explain what needs to be corrected or provided.') }}',
            btnClass: 'btn-warning',
            btnLabel: '{{ get_phrase('Return') }}',
        },
        reject: {
            title: '{{ get_phrase('Deny Leave') }}',
            message: '{{ get_phrase('Deny leave request for') }}',
            required: true,
            hint: '{{ get_phrase('Required: provide the reason for denial.') }}',
            btnClass: 'btn-danger',
            btnLabel: '{{ get_phrase('Deny') }}',
        },
    };

    function openLeaveDecisionModal(action, id, staffName) {
        const cfg = leaveDecisionConfig[action];
        const form = document.getElementById('leaveDecisionForm');
        const comment = document.getElementById('leaveDecisionComment');
        const submitBtn = document.getElementById('leaveDecisionSubmit');

        form.action = leaveDecisionRoutes[action].replace('__ID__', id);
        document.getElementById('leaveDecisionModalLabel').textContent = cfg.title;
        document.getElementById('leaveDecisionMessage').textContent = cfg.message + ' ' + staffName + '?';
        document.getElementById('leaveDecisionCommentHint').textContent = cfg.hint;
        comment.required = cfg.required;
        comment.value = '';

        submitBtn.className = 'btn ' + cfg.btnClass;
        submitBtn.textContent = cfg.btnLabel;

        const modal = new bootstrap.Modal(document.getElementById('leaveDecisionModal'));
        modal.show();
    }

    document.getElementById('leaveDecisionForm').addEventListener('submit', function (e) {
        const comment = document.getElementById('leaveDecisionComment');
        if (comment.required && !comment.value.trim()) {
            e.preventDefault();
            comment.classList.add('is-invalid');
        } else {
            comment.classList.remove('is-invalid');
        }
    });
</script>
@endsection
