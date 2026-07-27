@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Payroll') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="#">{{ get_phrase('HR') }}</a></li><li><a href="#">{{ get_phrase('Payroll') }}</a></li></ul>
        </div>
        <div class="export-btn-area d-flex gap-2">
            <a href="{{ route('admin.payroll.export', ['month' => $month]) }}" class="export_btn bg-secondary"><i class="bi bi-download"></i> {{ get_phrase('Export CSV') }}</a>
            <a href="{{ route('admin.salary_structures.index') }}" class="export_btn bg-secondary">{{ get_phrase('Salary Structures') }}</a>
            <a href="{{ route('admin.payroll.generate') }}" class="export_btn" onclick="return confirm('Generate payroll for current period?')">{{ get_phrase('Generate Payroll') }}</a>
        </div>
    </div>
</div></div></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="row mb-3">
    <div class="col-md-3"><div class="eSection-wrap text-center"><h6>{{ get_phrase('Total Payroll') }}</h6><h4>{{ number_format($stats['total'],2) }}</h4></div></div>
    <div class="col-md-3"><div class="eSection-wrap text-center"><h6>{{ get_phrase('Pending') }}</h6><h4 class="text-warning">{{ $stats['pending'] }}</h4></div></div>
    <div class="col-md-3"><div class="eSection-wrap text-center"><h6>{{ get_phrase('Approved') }}</h6><h4 class="text-info">{{ $stats['approved'] }}</h4></div></div>
    <div class="col-md-3"><div class="eSection-wrap text-center"><h6>{{ get_phrase('Paid') }}</h6><h4 class="text-success">{{ $stats['paid'] }}</h4></div></div>
</div>
<div class="row"><div class="col-12"><div class="eSection-wrap">
    <div class="table-responsive">
        <table class="table eTable">
            <thead><tr><th>#</th><th>{{ get_phrase('Staff') }}</th><th>{{ get_phrase('Period') }}</th><th>{{ get_phrase('Gross') }}</th><th>{{ get_phrase('Deductions') }}</th><th>{{ get_phrase('Net Pay') }}</th><th>{{ get_phrase('Status') }}</th><th>{{ get_phrase('Actions') }}</th></tr></thead>
            <tbody>
            @forelse($payrolls as $i => $p)
            <tr>
                <td>{{ $payrolls->firstItem() + $i }}</td>
                <td>{{ optional($p->staff)->name }}</td>
                <td>{{ $p->pay_period?->format('M Y') }}</td>
                <td>{{ number_format($p->basic_salary + $p->allowances,2) }}</td>
                <td class="text-danger">{{ number_format($p->deductions + $p->tax + $p->nssf,2) }}</td>
                <td><strong>{{ number_format($p->net_pay,2) }}</strong></td>
                <td><span class="badge bg-{{ $p->status=='paid'?'success':($p->status=='approved'?'primary':'warning') }}">{{ ucfirst($p->status) }}</span></td>
                <td>
                    @if($p->status=='draft')
                        <a href="{{ route('admin.payroll.approve', $p->id) }}" class="eBtn eBtn-sm eBtn-primary" onclick="return confirm('Approve?')"><i class="bi bi-check-circle"></i></a>
                    @endif
                    @if($p->status=='approved')
                        <a href="{{ route('admin.payroll.paid', $p->id) }}" class="eBtn eBtn-sm eBtn-success" onclick="return confirm('Mark as Paid?')"><i class="bi bi-cash"></i></a>
                    @endif
                    <a href="{{ route('admin.payroll.print', $p->id) }}" target="_blank" class="eBtn eBtn-sm eBtn-warning"><i class="bi bi-printer"></i></a>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center text-muted py-4">{{ get_phrase('No payroll records. Click Generate Payroll.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $payrolls->links() }}
</div></div></div>
@endsection
