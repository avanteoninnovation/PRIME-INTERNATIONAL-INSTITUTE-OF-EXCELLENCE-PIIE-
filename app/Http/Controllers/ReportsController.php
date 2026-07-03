<?php

namespace App\Http\Controllers;

use App\Models\Classes;
use App\Models\DailyAttendances;
use App\Models\Enrollment;
use App\Models\Gradebook;
use App\Models\OnlineExamSubmission;
use App\Models\Programme;
use App\Models\StudentFeeManager;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    private $school_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->school_id = Auth::user()->school_id;
            return $next($request);
        });
    }

    public function index()
    {
        $school_id = $this->school_id;

        // ── Student stats ──────────────────────────────────────
        $total_students  = User::where('school_id', $school_id)->where('role_id', 7)->count();
        $active_students = User::where('school_id', $school_id)->where('role_id', 7)->where('status', 1)->count();
        $total_staff     = User::where('school_id', $school_id)->whereIn('role_id', [2, 3, 10, 11, 12, 13])->count();

        // By programme — count students enrolled in classes linked to each programme
        $programmes = Programme::where('school_id', $school_id)->where('is_active', 1)
            ->get()
            ->map(function ($p) use ($school_id) {
                // Students whose enrollment class_id is in a class with this programme's subjects
                $class_ids = \App\Models\Subject::where('school_id', $school_id)
                    ->where('programme_id', $p->id)
                    ->pluck('class_id')->unique();
                $p->student_count = $class_ids->isNotEmpty()
                    ? Enrollment::where('school_id', $school_id)->whereIn('class_id', $class_ids)->count()
                    : 0;
                return $p;
            })
            ->sortByDesc('student_count');

        // Gender breakdown
        $gender_stats = User::where('school_id', $school_id)
            ->where('role_id', 7)
            ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(student_info, '$.gender')) as gender, COUNT(*) as count")
            ->groupBy('gender')
            ->get();

        // ── Finance stats ──────────────────────────────────────
        $fee_collected = StudentFeeManager::where('school_id', $school_id)
            ->where('status', 'paid')
            ->sum('paid_amount');
        $fee_invoiced  = StudentFeeManager::where('school_id', $school_id)->sum('total_amount');
        $fee_outstanding = max(0, $fee_invoiced - $fee_collected);

        // Monthly collection (last 6 months)
        $monthly_collections = StudentFeeManager::where('school_id', $school_id)
            ->where('status', 'paid')
            ->selectRaw("DATE_FORMAT(FROM_UNIXTIME(timestamp), '%Y-%m') as month, SUM(paid_amount) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->limit(6)
            ->get();

        // ── Attendance stats ───────────────────────────────────
        $today_ts     = strtotime(date('Y-m-d'));
        $today_present = DailyAttendances::where('school_id', $school_id)
            ->where('status', 'present')
            ->where('timestamp', '>=', $today_ts)
            ->where('timestamp', '<', $today_ts + 86400)
            ->count();
        $total_enrolled = Enrollment::where('school_id', $school_id)->count();

        // ── Exam stats ─────────────────────────────────────────
        $total_submissions = 0;
        $passed_submissions = 0;
        if (class_exists(OnlineExamSubmission::class)) {
            $total_submissions  = OnlineExamSubmission::whereHas('exam', fn($q) => $q->where('school_id', $school_id))->count();
            $passed_submissions = OnlineExamSubmission::whereHas('exam', fn($q) => $q->where('school_id', $school_id))->where('passed', 1)->count();
        }

        return view('admin.reports.index', compact(
            'total_students', 'active_students', 'total_staff',
            'programmes', 'gender_stats',
            'fee_collected', 'fee_invoiced', 'fee_outstanding', 'monthly_collections',
            'today_present', 'total_enrolled',
            'total_submissions', 'passed_submissions'
        ));
    }

    public function studentsReport(Request $request)
    {
        $school_id = $this->school_id;
        $students  = User::where('school_id', $school_id)
            ->where('role_id', 7)
            ->with(['enrollment'])
            ->orderBy('name')
            ->paginate(30);
        return view('admin.reports.students', compact('students'));
    }

    public function financeReport(Request $request)
    {
        $school_id = $this->school_id;
        $from = $request->from ?? date('Y-01-01');
        $to   = $request->to   ?? date('Y-12-31');
        $from_ts = strtotime($from);
        $to_ts   = strtotime($to) + 86400;

        $invoices = StudentFeeManager::where('school_id', $school_id)
            ->where('timestamp', '>=', $from_ts)
            ->where('timestamp', '<', $to_ts)
            ->orderByDesc('timestamp')
            ->paginate(30);

        $totals = StudentFeeManager::where('school_id', $school_id)
            ->where('timestamp', '>=', $from_ts)
            ->where('timestamp', '<', $to_ts)
            ->selectRaw('SUM(total_amount) as invoiced, SUM(paid_amount) as collected')
            ->first();

        return view('admin.reports.finance', compact('invoices', 'totals', 'from', 'to'));
    }

    public function attendanceReport(Request $request)
    {
        $school_id = $this->school_id;
        $classes   = Classes::where('school_id', $school_id)->get();
        $class_id  = $request->class_id ?? ($classes->first()?->id ?? null);

        $date_from = $request->from ?? date('Y-m-01');
        $date_to   = $request->to   ?? date('Y-m-d');
        $from_ts   = strtotime($date_from);
        $to_ts     = strtotime($date_to) + 86400;

        $attendance = DailyAttendances::where('school_id', $school_id)
            ->where('class_id', $class_id)
            ->where('timestamp', '>=', $from_ts)
            ->where('timestamp', '<', $to_ts)
            ->selectRaw('student_id, SUM(status="present") as present_days, SUM(status="absent") as absent_days, COUNT(*) as total_days')
            ->groupBy('student_id')
            ->with('student')
            ->get();

        return view('admin.reports.attendance', compact('classes', 'class_id', 'date_from', 'date_to', 'attendance'));
    }

    public function examsReport(Request $request)
    {
        $school_id = $this->school_id;
        $submissions = collect();
        if (class_exists(OnlineExamSubmission::class)) {
            $submissions = OnlineExamSubmission::whereHas('exam', fn($q) => $q->where('school_id', $school_id))
                ->with(['exam', 'student'])
                ->orderByDesc('created_at')
                ->paginate(30);
        }
        return view('admin.reports.exams', compact('submissions'));
    }

    public function export($type)
    {
        // Simple CSV export
        $school_id = $this->school_id;
        $filename  = "{$type}_report_" . date('Y-m-d') . ".csv";

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($type, $school_id) {
            $out = fopen('php://output', 'w');
            if ($type === 'students') {
                fputcsv($out, ['#', 'Name', 'Email', 'Code', 'Status', 'Enrolled']);
                User::where('school_id', $school_id)->where('role_id', 7)->orderBy('name')
                    ->each(function ($s, $i) use ($out) {
                        fputcsv($out, [$i+1, $s->name, $s->email, $s->code, $s->status ? 'Active' : 'Inactive', $s->created_at->format('Y-m-d')]);
                    });
            } elseif ($type === 'finance') {
                fputcsv($out, ['#', 'Title', 'Total', 'Paid', 'Status']);
                StudentFeeManager::where('school_id', $school_id)->orderByDesc('id')
                    ->each(function ($f, $i) use ($out) {
                        fputcsv($out, [$i+1, $f->title, $f->total_amount, $f->paid_amount, $f->status]);
                    });
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}
