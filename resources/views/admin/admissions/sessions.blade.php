@extends('admin.navigation')
@section('content')
<div class="mainSection-title">
    <div class="row"><div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
            <div class="d-flex flex-column">
                <h4>{{ get_phrase('Intake Sessions') }}</h4>
                <ul class="d-flex align-items-center eBreadcrumb-2">
                    <li><a href="{{ route('admin.hei_admissions.index') }}">{{ get_phrase('Admissions') }}</a></li>
                    <li><a href="#">{{ get_phrase('Intake Sessions') }}</a></li>
                </ul>
            </div>
            <div class="export-btn-area">
                <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('admin.intake_sessions.open_modal') }}', '{{ get_phrase('New Session') }}')">{{ get_phrase('Add Session') }}</a>
            </div>
        </div>
    </div></div>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="row"><div class="col-12"><div class="eSection-wrap">
    <div class="table-responsive">
        <table class="table eTable">
            <thead><tr><th>#</th><th>{{ get_phrase('Name') }}</th><th>{{ get_phrase('Open') }}</th><th>{{ get_phrase('Close') }}</th><th>{{ get_phrase('App Fee') }}</th><th>{{ get_phrase('Status') }}</th><th>{{ get_phrase('Applications') }}</th><th>{{ get_phrase('Actions') }}</th></tr></thead>
            <tbody>
            @forelse($sessions as $i => $s)
            <tr>
                <td>{{ $sessions->firstItem() + $i }}</td>
                <td><strong>{{ $s->name }}</strong></td>
                <td>{{ $s->open_date ?? '—' }}</td>
                <td>{{ $s->close_date ?? '—' }}</td>
                <td>{{ number_format($s->application_fee, 0) }}</td>
                <td><span class="badge bg-{{ $s->is_open ? 'success' : 'secondary' }}">{{ $s->is_open ? get_phrase('Open') : get_phrase('Closed') }}</span></td>
                <td>{{ $s->admissions()->count() }}</td>
                <td>
                    <a href="javascript:;" class="eBtn eBtn-sm eBtn-primary" onclick="rightModal('{{ route('admin.intake_sessions.open_modal', ['id' => $s->id]) }}', '{{ get_phrase('Edit Session') }}')"><i class="bi bi-pencil"></i></a>
                    <a href="{{ route('admin.intake_sessions.destroy', $s->id) }}" class="eBtn eBtn-sm eBtn-danger" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center text-muted py-4">{{ get_phrase('No intake sessions') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $sessions->links() }}
</div></div></div>
@endsection
