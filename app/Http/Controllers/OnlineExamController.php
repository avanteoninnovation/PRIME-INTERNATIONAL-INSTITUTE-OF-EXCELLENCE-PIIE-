<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Classes;
use App\Models\OnlineExam;
use App\Models\OnlineExamQuestion;
use App\Models\OnlineExamSubmission;
use App\Models\QuestionBank;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OnlineExamController extends Controller
{
    private $school_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->school_id = Auth::user()->school_id;
            return $next($request);
        });
    }

    // ── Exams (admin) ─────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $search  = $request->search ?? '';
        $exams   = OnlineExam::where('school_id', $this->school_id)
            ->when($search, fn($q) => $q->where('title', 'like', "%$search%"))
            ->with(['subject'])
            ->withCount('questions', 'submissions')
            ->latest()
            ->paginate(20);

        $subjects = Subject::where('school_id', $this->school_id)->orderBy('name')->get();
        $classes  = Classes::where('school_id', $this->school_id)->orderBy('name')->get();

        return view('admin.online_exam.index', compact('exams', 'subjects', 'classes', 'search'));
    }

    public function openModal(Request $request)
    {
        $id       = $request->id;
        $exam     = $id ? OnlineExam::where('school_id', $this->school_id)->findOrFail($id) : null;
        $subjects = Subject::where('school_id', $this->school_id)->orderBy('name')->get();
        $classes  = Classes::where('school_id', $this->school_id)->orderBy('name')->get();
        return view('admin.online_exam.modal', compact('exam', 'subjects', 'classes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'          => 'required|max:255',
            'subject_id'     => 'nullable|exists:subjects,id',
            'class_id'       => 'nullable|exists:classes,id',
            'exam_type'      => 'required',
            'start_datetime' => 'nullable|date',
            'end_datetime'   => 'nullable|date',
            'duration_mins'  => 'required|integer|min:1',
            'total_marks'    => 'required|integer|min:1',
            'pass_mark'      => 'required|integer|min:0',
            'instructions'   => 'nullable|string',
        ]);
        $validated['school_id']    = $this->school_id;
        $validated['is_published'] = 0;
        $validated['created_by']   = Auth::id();

        $exam = OnlineExam::create($validated);
        AuditLog::record('create', 'Online Exams', "Created exam: {$exam->title}");
        return response()->json(['status' => 'success', 'message' => get_phrase('Exam created'), 'id' => $exam->id]);
    }

    public function update(Request $request, $id)
    {
        $exam      = OnlineExam::where('school_id', $this->school_id)->findOrFail($id);
        $validated = $request->validate([
            'title'          => 'required|max:255',
            'subject_id'     => 'nullable|exists:subjects,id',
            'class_id'       => 'nullable|exists:classes,id',
            'exam_type'      => 'required',
            'start_datetime' => 'nullable|date',
            'end_datetime'   => 'nullable|date',
            'duration_mins'  => 'required|integer|min:1',
            'total_marks'    => 'required|integer|min:1',
            'pass_mark'      => 'required|integer|min:0',
            'instructions'   => 'nullable|string',
        ]);
        $exam->update($validated);
        return response()->json(['status' => 'success', 'message' => get_phrase('Exam updated')]);
    }

    public function publish($id)
    {
        $exam = OnlineExam::where('school_id', $this->school_id)->findOrFail($id);
        $exam->update(['is_published' => !$exam->is_published]);
        AuditLog::record('update', 'Online Exams', "Toggled publish for exam: {$exam->title}");
        return redirect()->back()->with('success', get_phrase('Exam status updated'));
    }

    public function destroy($id)
    {
        $exam = OnlineExam::where('school_id', $this->school_id)->findOrFail($id);
        AuditLog::record('delete', 'Online Exams', "Deleted exam: {$exam->title}");
        $exam->questions()->delete();
        $exam->delete();
        return redirect()->back()->with('success', get_phrase('Exam deleted'));
    }

    // ── Questions ─────────────────────────────────────────────────────────

    public function questions($exam_id)
    {
        $exam      = OnlineExam::where('school_id', $this->school_id)->findOrFail($exam_id);
        $questions = OnlineExamQuestion::where('online_exam_id', $exam_id)->orderBy('sort_order')->get();
        $bank      = QuestionBank::where('school_id', $this->school_id)
            ->when($exam->subject_id, fn($q) => $q->where('subject_id', $exam->subject_id))
            ->get();
        return view('admin.online_exam.questions', compact('exam', 'questions', 'bank'));
    }

    public function storeQuestion(Request $request, $exam_id)
    {
        OnlineExam::where('school_id', $this->school_id)->findOrFail($exam_id);
        $validated = $request->validate([
            'question'    => 'required|string',
            'type'        => 'required|in:mcq,true_false,short,essay',
            'option_a'    => 'nullable|string',
            'option_b'    => 'nullable|string',
            'option_c'    => 'nullable|string',
            'option_d'    => 'nullable|string',
            'correct_ans' => 'nullable|string|max:5',
            'marks'       => 'required|integer|min:1',
        ]);
        $validated['online_exam_id'] = $exam_id;
        $validated['sort_order']     = OnlineExamQuestion::where('online_exam_id', $exam_id)->max('sort_order') + 1;
        OnlineExamQuestion::create($validated);
        return response()->json(['status' => 'success', 'message' => get_phrase('Question added')]);
    }

    public function destroyQuestion($id)
    {
        OnlineExamQuestion::findOrFail($id)->delete();
        return redirect()->back()->with('success', get_phrase('Question deleted'));
    }

    // ── Question Bank ─────────────────────────────────────────────────────

    public function questionBank(Request $request)
    {
        $search   = $request->search ?? '';
        $questions = QuestionBank::where('school_id', $this->school_id)
            ->when($search, fn($q) => $q->where('question', 'like', "%$search%"))
            ->orderByDesc('id')
            ->paginate(20);
        $subjects = Subject::where('school_id', $this->school_id)->orderBy('name')->get();
        return view('admin.online_exam.question_bank', compact('questions', 'subjects', 'search'));
    }

    public function bankModal()
    {
        $subjects = Subject::where('school_id', $this->school_id)->orderBy('name')->get();
        return view('admin.online_exam.bank_modal', compact('subjects'));
    }

    public function destroyBankQuestion($id)
    {
        QuestionBank::where('school_id', $this->school_id)->findOrFail($id)->delete();
        return redirect()->back()->with('success', get_phrase('Question deleted'));
    }

    public function questionModal($exam_id)
    {
        $exam = OnlineExam::where('school_id', $this->school_id)->findOrFail($exam_id);
        return view('admin.online_exam.question_modal', compact('exam_id'));
    }

    public function storeBankQuestion(Request $request)
    {
        $validated = $request->validate([
            'subject_id'  => 'nullable|exists:subjects,id',
            'question'    => 'required|string',
            'type'        => 'required|in:mcq,true_false,short,essay',
            'option_a'    => 'nullable|string',
            'option_b'    => 'nullable|string',
            'option_c'    => 'nullable|string',
            'option_d'    => 'nullable|string',
            'correct_ans' => 'nullable|string|max:5',
            'marks'       => 'required|integer|min:1',
            'difficulty'  => 'required|in:easy,medium,hard',
        ]);
        $validated['school_id']  = $this->school_id;
        $validated['created_by'] = Auth::id();
        QuestionBank::create($validated);
        return response()->json(['status' => 'success', 'message' => get_phrase('Question added to bank')]);
    }

    // ── Student: take exam ─────────────────────────────────────────────────

    public function studentExams()
    {
        $student_id = Auth::id();
        $school_id  = Auth::user()->school_id;
        $enroll     = \App\Models\Enrollment::where('user_id', $student_id)->where('school_id', $school_id)->first();
        $class_id   = $enroll?->class_id;

        $exams = OnlineExam::where('school_id', $school_id)
            ->where('is_published', 1)
            ->where(fn($q) => $q->whereNull('class_id')->orWhere('class_id', $class_id))
            ->get()
            ->map(function ($exam) use ($student_id) {
                $exam->submission = OnlineExamSubmission::where('online_exam_id', $exam->id)
                    ->where('student_id', $student_id)
                    ->first();
                return $exam;
            });

        return view('student.online_exam.list', compact('exams'));
    }

    public function takeExam($id)
    {
        $school_id = Auth::user()->school_id;
        $exam      = OnlineExam::where('school_id', $school_id)->where('is_published', 1)->findOrFail($id);
        $existing  = OnlineExamSubmission::where('online_exam_id', $id)->where('student_id', Auth::id())->first();

        if ($existing && $existing->status !== 'in_progress') {
            return redirect()->route('student.online_exam.result', $existing->id);
        }

        if (!$existing) {
            $existing = OnlineExamSubmission::create([
                'online_exam_id' => $id,
                'student_id'     => Auth::id(),
                'school_id'      => $school_id,
                'started_at'     => now(),
                'status'         => 'in_progress',
            ]);
        }

        $questions = OnlineExamQuestion::where('online_exam_id', $id)->orderBy('sort_order')->get();
        return view('student.online_exam.take', compact('exam', 'questions', 'existing'));
    }

    public function submitExam(Request $request, $id)
    {
        $school_id  = Auth::user()->school_id;
        $exam       = OnlineExam::where('school_id', $school_id)->findOrFail($id);
        $submission = OnlineExamSubmission::where('online_exam_id', $id)
            ->where('student_id', Auth::id())
            ->where('status', 'in_progress')
            ->firstOrFail();

        $answers   = $request->answers ?? [];
        $score     = 0;
        $questions = OnlineExamQuestion::where('online_exam_id', $id)->get();

        foreach ($questions as $q) {
            if (in_array($q->type, ['mcq', 'true_false'])) {
                $given = $answers[$q->id] ?? null;
                if ($given && strtolower($given) === strtolower($q->correct_ans)) {
                    $score += $q->marks;
                }
            }
        }

        $submission->update([
            'answers'      => $answers,
            'score'        => $score,
            'submitted_at' => now(),
            'status'       => 'submitted',
        ]);

        AuditLog::record('submit', 'Online Exams', "Student " . Auth::id() . " submitted exam #{$id}. Score: $score");
        return redirect()->route('student.online_exam.result', $submission->id);
    }

    public function examResult($submission_id)
    {
        $submission = OnlineExamSubmission::where('student_id', Auth::id())->findOrFail($submission_id);
        $exam       = $submission->exam;
        $questions  = OnlineExamQuestion::where('online_exam_id', $exam->id)->orderBy('sort_order')->get();
        return view('student.online_exam.result', compact('submission', 'exam', 'questions'));
    }

    // ── Submissions (admin view) ───────────────────────────────────────────

    public function submissions($exam_id)
    {
        $exam        = OnlineExam::where('school_id', $this->school_id)->findOrFail($exam_id);
        $submissions = OnlineExamSubmission::where('online_exam_id', $exam_id)
            ->with('student')
            ->orderByDesc('submitted_at')
            ->paginate(30);
        return view('admin.online_exam.submissions', compact('exam', 'submissions'));
    }
}
