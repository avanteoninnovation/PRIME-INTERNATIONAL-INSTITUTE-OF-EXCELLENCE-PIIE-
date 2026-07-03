<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Payroll;
use App\Models\SalaryStructure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PDF;

class PayrollController extends Controller
{
    private $school_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->school_id = Auth::user()->school_id;
            return $next($request);
        });
    }

    // ── Salary Structures ─────────────────────────────────────────────────

    public function salaryIndex()
    {
        $structures = SalaryStructure::where('school_id', $this->school_id)
            ->with('user')
            ->orderByDesc('effective_from')
            ->paginate(20);
        $staff = User::where('school_id', $this->school_id)
            ->whereIn('role_id', [2, 3, 6, 7, 8, 9, 10, 11, 12])
            ->orderBy('name')->get();
        return view('admin.payroll.salary_structures', compact('structures', 'staff'));
    }

    public function salaryModal()
    {
        $staff = User::where('school_id', $this->school_id)
            ->whereIn('role_id', [2, 3, 6, 7, 8, 9, 10, 11, 12])
            ->orderBy('name')->get();
        return view('admin.payroll.salary_modal', compact('staff'));
    }

    public function storeSalary(Request $request)
    {
        $validated = $request->validate([
            'user_id'        => 'required|exists:users,id',
            'basic'          => 'required|numeric|min:0',
            'housing'        => 'nullable|numeric|min:0',
            'transport'      => 'nullable|numeric|min:0',
            'medical'        => 'nullable|numeric|min:0',
            'effective_from' => 'required|date',
        ]);
        $validated['school_id'] = $this->school_id;
        SalaryStructure::create($validated);
        AuditLog::record('create', 'Payroll', "Salary structure created for user #{$validated['user_id']}");
        return response()->json(['status' => 'success', 'message' => get_phrase('Salary structure saved')]);
    }

    // ── Payroll ────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $month   = $request->month ?? now()->format('Y-m');
        $period  = $month . '-01';
        $payroll = Payroll::where('school_id', $this->school_id)
            ->where('pay_period', $period)
            ->with('staff')
            ->paginate(30);

        $staff = User::where('school_id', $this->school_id)
            ->whereIn('role_id', [2, 3, 6, 7, 8, 9, 10, 11, 12])
            ->orderBy('name')->get();

        return view('admin.payroll.index', compact('payroll', 'staff', 'month'));
    }

    public function generate(Request $request)
    {
        $request->validate(['month' => 'required|date_format:Y-m']);
        $period    = $request->month . '-01';
        $staff     = User::where('school_id', $this->school_id)
            ->whereIn('role_id', [2, 3, 6, 7, 8, 9, 10, 11, 12])
            ->get();

        $generated = 0;
        foreach ($staff as $s) {
            if (Payroll::where('school_id', $this->school_id)->where('staff_id', $s->id)->where('pay_period', $period)->exists()) {
                continue;
            }
            $struct = SalaryStructure::where('school_id', $this->school_id)
                ->where('user_id', $s->id)
                ->orderByDesc('effective_from')
                ->first();

            $basic       = $struct?->basic ?? 0;
            $allowances  = ($struct?->housing ?? 0) + ($struct?->transport ?? 0) + ($struct?->medical ?? 0);
            $nssf        = round($basic * 0.10, 2); // 10% NSSF
            $tax         = round($basic * 0.05, 2); // simplified PAYE
            $net         = $basic + $allowances - $nssf - $tax;

            Payroll::create([
                'school_id'      => $this->school_id,
                'staff_id'       => $s->id,
                'pay_period'     => $period,
                'basic_salary'   => $basic,
                'allowances'     => $allowances,
                'deductions'     => 0,
                'nssf'           => $nssf,
                'tax'            => $tax,
                'net_pay'        => $net,
                'payment_method' => 'bank',
                'status'         => 'draft',
            ]);
            $generated++;
        }

        AuditLog::record('generate', 'Payroll', "Generated payroll for $generated staff for $period");
        return redirect()->back()->with('success', get_phrase('Payroll generated for') . " $generated " . get_phrase('staff members'));
    }

    public function approve($id)
    {
        $pay = Payroll::where('school_id', $this->school_id)->findOrFail($id);
        $pay->update(['status' => 'approved', 'approved_by' => Auth::id()]);
        AuditLog::record('approve', 'Payroll', "Approved payslip #{$id}");
        return redirect()->back()->with('success', get_phrase('Payslip approved'));
    }

    public function markPaid($id)
    {
        $pay = Payroll::where('school_id', $this->school_id)->findOrFail($id);
        $pay->update(['status' => 'paid', 'paid_at' => now()]);
        AuditLog::record('paid', 'Payroll', "Marked payslip #{$id} as paid");
        return redirect()->back()->with('success', get_phrase('Marked as paid'));
    }

    public function printSlip($id)
    {
        $pay = Payroll::where('school_id', $this->school_id)->with('staff')->findOrFail($id);
        $pdf = PDF::loadView('admin.payroll.payslip_pdf', compact('pay'));
        return $pdf->download("Payslip_{$pay->staff->name}_{$pay->pay_period}.pdf");
    }

    // Staff: view own payslips
    public function staffPayslips()
    {
        $payslips = Payroll::where('school_id', Auth::user()->school_id)
            ->where('staff_id', Auth::id())
            ->orderByDesc('pay_period')
            ->paginate(12);
        return view('teacher.payroll.index', compact('payslips'));
    }
}
