<?php

namespace App\Http\Controllers;

use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\ExamCategory;
use App\Models\Grade;
use App\Models\Gradebook;
use App\Models\Programme;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TranscriptController extends Controller
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
        $search   = $request->search ?? '';
        $students = collect();

        if ($search) {
            $students = User::where('school_id', $this->school_id)
                ->where('role_id', 7) // student
                ->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                })
                ->orderBy('name')
                ->get();
        }

        return view('admin.transcripts.index', compact('students', 'search'));
    }

    public function search(Request $request)
    {
        return $this->index($request);
    }

    public function show($id)
    {
        $student  = User::where('school_id', $this->school_id)->findOrFail($id);
        $school_id = $this->school_id;

        // Get current session
        $session_id = DB::table('sessions')->where('school_id', $school_id)->where('status', 1)->value('id');

        // Enrollment info
        $enrollment = Enrollment::where('user_id', $id)->where('school_id', $school_id)->latest()->first();
        $programme  = $enrollment?->class_id
            ? Programme::whereHas('classes', fn($q) => $q->where('classes.id', $enrollment->class_id))->first()
            : null;

        // All exam categories for this session
        $exam_categories = ExamCategory::where('school_id', $school_id)->get();

        // Subjects for the student's class
        $subjects = $enrollment?->class_id
            ? Subject::where('class_id', $enrollment->class_id)->where('school_id', $school_id)->get()
            : collect();

        // Marks per exam category
        $gradebook = [];
        if ($enrollment) {
            $raw = Gradebook::where('student_id', $id)
                ->where('school_id', $school_id)
                ->get();
            foreach ($raw as $r) {
                $marks_data = is_string($r->marks) ? json_decode($r->marks, true) : [];
                $gradebook[$r->exam_category_id] = $marks_data ?? [];
            }
        }

        // Grade scale for display
        $grades = Grade::where('school_id', $school_id)->orderByDesc('mark_from')->get();

        // Calculate overall average
        $total_obtained = 0;
        $total_possible = 0;
        foreach ($gradebook as $cat_marks) {
            if (is_array($cat_marks)) {
                foreach ($cat_marks as $m) {
                    $total_obtained += (float)($m['obtained'] ?? 0);
                    $total_possible += (float)($m['total'] ?? 100);
                }
            }
        }
        $overall_percent = $total_possible > 0 ? round($total_obtained / $total_possible * 100, 2) : 0;
        $overall_grade   = $this->getGrade($overall_percent, $grades);

        return view('admin.transcripts.show', compact(
            'student', 'enrollment', 'programme', 'exam_categories',
            'subjects', 'gradebook', 'grades', 'overall_percent', 'overall_grade'
        ));
    }

    public function downloadPdf($id)
    {
        // Redirect to show with print parameter (use browser print for now)
        return redirect()->route('admin.transcripts.show', $id)->with('print', true);
    }

    private function getGrade(float $percent, $grades): ?Grade
    {
        foreach ($grades as $g) {
            if ($percent >= $g->mark_from && $percent <= $g->mark_upto) {
                return $g;
            }
        }
        return null;
    }
}
