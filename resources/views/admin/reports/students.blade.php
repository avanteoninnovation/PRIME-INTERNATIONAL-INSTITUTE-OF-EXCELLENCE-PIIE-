@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Student Enrolment Report') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2">
                <li><a href="{{ route('admin.reports.index') }}">{{ get_phrase('Reports') }}</a></li>
                <li><a href="#">{{ get_phrase('Students') }}</a></li>
            </ul>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.reports.export', 'students') }}" class="eBtn eBtn-secondary"><i class="bi bi-download"></i> {{ get_phrase('Export CSV') }}</a>
            <a href="{{ route('admin.reports.index') }}" class="eBtn eBtn-secondary"><i class="bi bi-arrow-left"></i> {{ get_phrase('Back') }}</a>
        </div>
    </div>
</div></div></div>
<div class="row"><div class="col-12"><div class="eSection-wrap">
    <div class="table-responsive">
        <table class="table eTable" style="font-size:12px">
            <thead><tr><th>#</th><th>{{ get_phrase('Name') }}</th><th>{{ get_phrase('Email') }}</th><th>{{ get_phrase('Reg. No') }}</th><th>{{ get_phrase('Enrolled') }}</th><th>{{ get_phrase('Status') }}</th></tr></thead>
            <tbody>
            @forelse($students as $i => $s)
            <tr>
                <td>{{ $students->firstItem() + $i }}</td>
                <td><strong>{{ $s->name }}</strong></td>
                <td>{{ $s->email }}</td>
                <td>{{ $s->code ?? '—' }}</td>
                <td>{{ $s->created_at->format('d M Y') }}</td>
                <td><span class="badge bg-{{ $s->status ? 'success':'secondary' }}">{{ $s->status ? get_phrase('Active'):get_phrase('Inactive') }}</span></td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted py-4">{{ get_phrase('No students found') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $students->links() }}
</div></div></div>
@endsection
