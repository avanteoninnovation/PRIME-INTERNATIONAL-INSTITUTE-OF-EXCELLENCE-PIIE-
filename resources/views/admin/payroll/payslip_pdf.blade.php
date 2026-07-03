<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Payslip — {{ $payroll->staff->name ?? '' }}</title>
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
.header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
.header h2 { margin: 0; font-size: 18px; }
.header p { margin: 2px 0; font-size: 11px; }
.info-table, .salary-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
.info-table td { padding: 4px 8px; }
.salary-table th, .salary-table td { border: 1px solid #ccc; padding: 6px 10px; }
.salary-table th { background: #f0f0f0; text-align: left; }
.total-row { font-weight: bold; background: #e8f5e9; }
.footer { margin-top: 30px; display: flex; justify-content: space-between; }
.sig-line { border-top: 1px solid #333; width: 150px; text-align: center; padding-top: 4px; font-size: 10px; }
</style>
</head>
<body>
<div class="header">
    <h2>{{ $school->school_name ?? 'School Name' }}</h2>
    <p>{{ $school->address ?? '' }}</p>
    <h3 style="margin:10px 0 0">PAYSLIP — {{ $payroll->pay_period?->format('F Y') }}</h3>
</div>
<table class="info-table">
    <tr><td><strong>Employee:</strong> {{ $payroll->staff->name ?? '—' }}</td><td><strong>Pay Period:</strong> {{ $payroll->pay_period?->format('F Y') }}</td></tr>
    <tr><td><strong>Role:</strong> {{ optional($payroll->staff->role)->name ?? '—' }}</td><td><strong>Payment Date:</strong> {{ $payroll->paid_at?->format('d M Y') ?? 'Pending' }}</td></tr>
</table>
<table class="salary-table">
    <thead><tr><th>Earnings</th><th>Amount</th><th>Deductions</th><th>Amount</th></tr></thead>
    <tbody>
        <tr><td>Basic Salary</td><td>{{ number_format($payroll->basic_salary,2) }}</td><td>NSSF (10%)</td><td>{{ number_format($payroll->nssf_deduction,2) }}</td></tr>
        <tr><td>Allowances</td><td>{{ number_format($payroll->allowances,2) }}</td><td>Tax (5%)</td><td>{{ number_format($payroll->tax_deduction,2) }}</td></tr>
        <tr><td>Gross Salary</td><td>{{ number_format($payroll->gross_salary,2) }}</td><td>Total Deductions</td><td>{{ number_format($payroll->total_deductions,2) }}</td></tr>
        <tr class="total-row"><td colspan="2"></td><td>Net Pay</td><td>{{ number_format($payroll->net_salary,2) }}</td></tr>
    </tbody>
</table>
<div class="footer">
    <div class="sig-line">Prepared By</div>
    <div class="sig-line">Approved By</div>
    <div class="sig-line">Employee Signature</div>
</div>
</body>
</html>
