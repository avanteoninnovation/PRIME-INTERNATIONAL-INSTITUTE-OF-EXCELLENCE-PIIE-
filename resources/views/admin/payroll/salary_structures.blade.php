@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Salary Structures') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="{{ route('admin.payroll.index') }}">{{ get_phrase('Payroll') }}</a></li><li><a href="#">{{ get_phrase('Salary Structures') }}</a></li></ul>
        </div>
        <div class="export-btn-area">
            <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('admin.salary_structures.modal') }}', '{{ get_phrase('Set Salary') }}')">{{ get_phrase('Set Salary') }}</a>
        </div>
    </div>
</div></div></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="row"><div class="col-12"><div class="eSection-wrap">
    <div class="table-responsive">
        <table class="table eTable">
            <thead><tr><th>#</th><th>{{ get_phrase('Staff') }}</th><th>{{ get_phrase('Basic Salary') }}</th><th>{{ get_phrase('Allowances') }}</th><th>{{ get_phrase('Effective From') }}</th><th>{{ get_phrase('Notes') }}</th></tr></thead>
            <tbody>
            @forelse($structures as $i => $ss)
            <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ optional($ss->user)->name }}</td>
                <td>{{ number_format($ss->basic_salary,2) }}</td>
                <td>{{ number_format($ss->allowances,2) }}</td>
                <td>{{ $ss->effective_from?->format('d M Y') }}</td>
                <td>{{ $ss->notes ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted py-4">{{ get_phrase('No salary structures configured') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div></div></div>
@endsection
