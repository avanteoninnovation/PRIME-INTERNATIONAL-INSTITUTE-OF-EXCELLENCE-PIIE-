@extends('admin.navigation')
@section('content')
<div class="mainSection-title">
    <div class="row"><div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
            <div class="d-flex flex-column">
                <h4>{{ get_phrase('Admissions Agents') }}</h4>
                <ul class="d-flex align-items-center eBreadcrumb-2">
                    <li><a href="{{ route('admin.hei_admissions.index') }}">{{ get_phrase('Admissions') }}</a></li>
                    <li><a href="#">{{ get_phrase('Agents') }}</a></li>
                </ul>
            </div>
            <div class="export-btn-area d-flex gap-2">
                <a href="{{ route('admin.admissions_agents.export') }}" class="export_btn export_btn-outline"><i class="bi bi-download"></i> {{ get_phrase('Export CSV') }}</a>
                <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('admin.admissions_agents.open_modal') }}', '{{ get_phrase('Add Agent') }}')">{{ get_phrase('Add Agent') }}</a>
            </div>
        </div>
    </div></div>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="row"><div class="col-12"><div class="eSection-wrap">
    <div class="table-responsive">
        <table class="table eTable">
            <thead><tr><th>#</th><th>{{ get_phrase('Name') }}</th><th>{{ get_phrase('Email') }}</th><th>{{ get_phrase('Phone') }}</th><th>{{ get_phrase('Commission') }}</th><th>{{ get_phrase('Status') }}</th><th>{{ get_phrase('Referrals') }}</th><th>{{ get_phrase('Actions') }}</th></tr></thead>
            <tbody>
            @forelse($agents as $i => $a)
            <tr>
                <td>{{ $agents->firstItem() + $i }}</td>
                <td><strong>{{ $a->name }}</strong></td>
                <td>{{ $a->email ?? '—' }}</td>
                <td>{{ $a->phone ?? '—' }}</td>
                <td>{{ $a->commission_pct }}%</td>
                <td><span class="badge bg-{{ $a->is_active ? 'success' : 'secondary' }}">{{ $a->is_active ? get_phrase('Active') : get_phrase('Inactive') }}</span></td>
                <td>{{ \App\Models\Admission::where('agent_id', $a->id)->count() }}</td>
                <td>
                    <a href="javascript:;" class="eBtn eBtn-sm eBtn-primary" onclick="rightModal('{{ route('admin.admissions_agents.open_modal', ['id' => $a->id]) }}', '{{ get_phrase('Edit Agent') }}')"><i class="bi bi-pencil"></i></a>
                    <a href="{{ route('admin.admissions_agents.destroy', $a->id) }}" class="eBtn eBtn-sm eBtn-danger" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center text-muted py-4">{{ get_phrase('No agents found') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $agents->links() }}
</div></div></div>
@endsection
