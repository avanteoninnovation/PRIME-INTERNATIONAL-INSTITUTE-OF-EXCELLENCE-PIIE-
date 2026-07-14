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
use PDF;

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
        return view('admin.transcripts.show', $this->buildTranscriptViewData($id));
    }

    public function downloadPdf($id)
    {
        $data = $this->buildTranscriptViewData($id);
        $studentCode = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($data['student']->code ?: $data['student']->id));
        $pdf = PDF::loadView('admin.transcripts.pdf', $data);

        return $pdf->download('Transcript_' . trim($studentCode, '-') . '.pdf');
    }

    private function buildTranscriptViewData($id): array
    {
        $student = User::where('school_id', $this->school_id)->findOrFail($id);
        $school_id = $this->school_id;

        $enrollment = Enrollment::where('user_id', $id)
            ->where('school_id', $school_id)
            ->latest()
            ->first();

        $programme = $enrollment?->class_id
            ? Programme::whereHas('classes', fn($q) => $q->where('classes.id', $enrollment->class_id))->first()
            : null;

        $exam_categories = ExamCategory::where('school_id', $school_id)->get();

        $subjects = $enrollment?->class_id
            ? Subject::where('class_id', $enrollment->class_id)->where('school_id', $school_id)->get()
            : collect();

        $gradebook = [];
        if ($enrollment) {
            $raw = Gradebook::where('student_id', $id)
                ->where('school_id', $school_id)
                ->get();

            foreach ($raw as $record) {
                $marks_data = is_string($record->marks) ? json_decode($record->marks, true) : [];
                $gradebook[$record->exam_category_id] = $marks_data ?? [];
            }
        }

        $grades = Grade::where('school_id', $school_id)->orderByDesc('mark_from')->get();

        $total_obtained = 0;
        $total_possible = 0;
        foreach ($gradebook as $cat_marks) {
            if (!is_array($cat_marks)) {
                continue;
            }

            foreach ($cat_marks as $mark) {
                $total_obtained += (float) ($mark['obtained'] ?? 0);
                $total_possible += (float) ($mark['total'] ?? 100);
            }
        }

        $overall_percent = $total_possible > 0 ? round($total_obtained / $total_possible * 100, 2) : 0;
        $overall_grade = $this->getGrade($overall_percent, $grades);

        return compact(
            'student',
            'enrollment',
            'programme',
            'exam_categories',
            'subjects',
            'gradebook',
            'grades',
            'overall_percent',
            'overall_grade'
        );
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
