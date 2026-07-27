<?php

namespace App\Http\Controllers;

use App\Models\Gradebook;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Dedicated CAT/EXAM marks entry for Programme-linked Courses. Deliberately
 * separate from AdminController::markAdd/CommonController::markUpdate (the
 * K-12 single-scalar-mark Gradebook screens) — those are untouched. This
 * reuses the same `gradebooks` table/model, just with class_id/section_id/
 * exam_category_id left null (a HEI course has no class/section, and CAT+
 * EXAM are two components of one sitting rather than two exam categories),
 * and writes each subject's mark as {cats, exam, obtained, total} instead
 * of a bare scalar — the shape TranscriptController already expects.
 */
class CourseMarksController extends Controller
{
    private $school_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->school_id = Auth::user()->school_id;
            return $next($request);
        });
    }

    private function findCourse($id): Subject
    {
        return Subject::where('school_id', $this->school_id)->whereNotNull('programme_id')->findOrFail($id);
    }

    private function sessionId(): ?int
    {
        return optional(School::find($this->school_id))->running_session;
    }

    private function gradebookFor(int $studentId, ?int $sessionId): Gradebook
    {
        return Gradebook::firstOrNew([
            'student_id'       => $studentId,
            'school_id'        => $this->school_id,
            'session_id'       => $sessionId,
            'class_id'         => null,
            'section_id'       => null,
            'exam_category_id' => null,
        ]);
    }

    public function edit($id)
    {
        $course = $this->findCourse($id);
        $sessionId = $this->sessionId();

        $students = StudentProfile::where('school_id', $this->school_id)
            ->where('programme_id', $course->programme_id)
            ->with('user')
            ->get();

        $marksByStudent = [];
        foreach ($students as $profile) {
            $gradebook = $this->gradebookFor($profile->user_id, $sessionId);
            $marks = is_string($gradebook->marks) ? json_decode($gradebook->marks, true) : [];
            $marksByStudent[$profile->user_id] = $marks[$course->id] ?? null;
        }

        return view('admin.course.marks', compact('course', 'students', 'marksByStudent'));
    }

    public function update(Request $request, $id)
    {
        $course = $this->findCourse($id);
        $sessionId = $this->sessionId();

        $studentIds = StudentProfile::where('school_id', $this->school_id)
            ->where('programme_id', $course->programme_id)
            ->pluck('user_id');

        $validated = $request->validate([
            'cats.*' => "nullable|integer|min:0|max:{$course->cats_marks}",
            'exam.*' => "nullable|integer|min:0|max:{$course->exam_marks}",
        ]);

        foreach ($studentIds as $studentId) {
            if (! $request->has("cats.$studentId") && ! $request->has("exam.$studentId")) {
                continue;
            }

            $cats = (int) ($validated['cats'][$studentId] ?? 0);
            $exam = (int) ($validated['exam'][$studentId] ?? 0);

            $gradebook = $this->gradebookFor($studentId, $sessionId);
            $marks = is_string($gradebook->marks) ? (json_decode($gradebook->marks, true) ?? []) : [];
            $marks[$course->id] = [
                'cats'     => $cats,
                'exam'     => $exam,
                'obtained' => $cats + $exam,
                'total'    => 100,
            ];
            $gradebook->marks = json_encode($marks);
            $gradebook->save();
        }

        return redirect()->route('admin.courses.marks', $course->id)->with('success', get_phrase('Marks saved successfully'));
    }
}
