<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Gradebook;
use App\Models\GraduationApplication;
use App\Models\Programme;
use App\Models\StudentFeeManager;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PDF;

class GraduationController extends Controller
{
    private $school_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->school_id = Auth::user()->school_id;
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $year   = $request->year ?? date('Y');
        $status = $request->status ?? '';
        $apps   = GraduationApplication::where('school_id', $this->school_id)
            ->when($year, fn($q) => $q->where('ceremony_year', $year))
            ->when($status, fn($q) => $q->where('status', $status))
            ->with(['student', 'programme'])
            ->latest()
            ->paginate(20);

        $programmes = Programme::where('school_id', $this->school_id)->orderBy('name')->get();
        $years      = range(date('Y'), date('Y') - 5);
        return view('admin.graduation.index', compact('apps', 'programmes', 'year', 'status', 'years'));
    }

    public function openApplyModal(Request $request)
    {
        $id         = $request->id;
        $app        = $id ? GraduationApplication::where('school_id', $this->school_id)->findOrFail($id) : null;
        $programmes = Programme::where('school_id', $this->school_id)->where('is_active', 1)->orderBy('name')->get();
        $students   = User::where('school_id', $this->school_id)->where('role_id', 4)->orderBy('name')->get();
        return view('admin.graduation.modal', compact('app', 'programmes', 'students'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id'    => 'required|exists:users,id',
            'programme_id'  => 'nullable|exists:programmes,id',
            'cgpa'          => 'nullable|numeric|min:0|max:5',
            'ceremony_year' => 'required|integer|min:2000|max:2100',
        ]);

        $validated['school_id']         = $this->school_id;
        $validated['classification']    = $this->classify($validated['cgpa'] ?? 0);
        $validated['fees_cleared']      = $request->has('fees_cleared') ? 1 : 0;
        $validated['academics_cleared'] = $request->has('academics_cleared') ? 1 : 0;
        $validated['status']            = 'pending';

        $app = GraduationApplication::create($validated);
        AuditLog::record('create', 'Graduation', "Graduation application created for student #{$app->student_id}");
        return response()->json(['status' => 'success', 'message' => get_phrase('Application submitted')]);
    }

    public function approve($id)
    {
        $app = GraduationApplication::where('school_id', $this->school_id)->findOrFail($id);
        $app->update(['status' => 'approved', 'reviewed_by' => Auth::id()]);
        AuditLog::record('update', 'Graduation', "Approved graduation for student #{$app->student_id}");
        return redirect()->back()->with('success', get_phrase('Application approved'));
    }

    public function graduate($id)
    {
        $app = GraduationApplication::where('school_id', $this->school_id)->findOrFail($id);
        $app->update(['status' => 'graduated', 'reviewed_by' => Auth::id()]);
        AuditLog::record('update', 'Graduation', "Marked as graduated: student #{$app->student_id}");
        return redirect()->back()->with('success', get_phrase('Marked as graduated'));
    }

    public function destroy($id)
    {
        $app = GraduationApplication::where('school_id', $this->school_id)->findOrFail($id);
        AuditLog::record('delete', 'Graduation', "Deleted graduation app #{$id}");
        $app->delete();
        return redirect()->back()->with('success', get_phrase('Application deleted'));
    }

    // Student: apply for graduation
    public function studentApply()
    {
        $school_id  = Auth::user()->school_id;
        $student_id = Auth::id();
        $app        = GraduationApplication::where('school_id', $school_id)->where('student_id', $student_id)->first();
        $programmes = Programme::where('school_id', $school_id)->where('is_active', 1)->orderBy('name')->get();
        return view('student.graduation.apply', compact('app', 'programmes'));
    }

    public function studentStore(Request $request)
    {
        $school_id  = Auth::user()->school_id;
        $student_id = Auth::id();

        if (GraduationApplication::where('school_id', $school_id)->where('student_id', $student_id)->exists()) {
            return redirect()->back()->with('error', get_phrase('You have already submitted a graduation application'));
        }

        $request->validate(['programme_id' => 'nullable|exists:programmes,id', 'ceremony_year' => 'required|integer']);

        GraduationApplication::create([
            'school_id'      => $school_id,
            'student_id'     => $student_id,
            'programme_id'   => $request->programme_id,
            'ceremony_year'  => $request->ceremony_year,
            'status'         => 'pending',
        ]);

        return redirect()->back()->with('success', get_phrase('Graduation application submitted'));
    }

    private function classify(float $cgpa): string
    {
        if ($cgpa >= 3.6) return 'First Class';
        if ($cgpa >= 3.0) return 'Upper Second';
        if ($cgpa >= 2.0) return 'Lower Second';
        if ($cgpa >= 1.0) return 'Pass';
        return 'Fail';
    }
}
