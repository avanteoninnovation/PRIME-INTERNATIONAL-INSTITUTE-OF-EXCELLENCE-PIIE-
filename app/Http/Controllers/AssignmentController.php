<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AuditLog;
use App\Models\Classes;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AssignmentController extends Controller
{
    private $school_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->school_id = Auth::user()->school_id;
            return $next($request);
        });
    }

    // ── Admin / Teacher ────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $search   = $request->search ?? '';
        $assignments = Assignment::where('school_id', $this->school_id)
            ->when($search, fn($q) => $q->where('title', 'like', "%$search%"))
            ->with(['subject'])
            ->withCount('submissions')
            ->latest()
            ->paginate(20);

        $subjects = Subject::where('school_id', $this->school_id)->orderBy('name')->get();
        $classes  = Classes::where('school_id', $this->school_id)->orderBy('name')->get();

        return view('admin.assignment.index', compact('assignments', 'subjects', 'classes', 'search'));
    }

    public function openModal(Request $request)
    {
        $id         = $request->id;
        $assignment = $id ? Assignment::where('school_id', $this->school_id)->findOrFail($id) : null;
        $subjects   = Subject::where('school_id', $this->school_id)->orderBy('name')->get();
        $classes    = Classes::where('school_id', $this->school_id)->orderBy('name')->get();
        return view('admin.assignment.modal', compact('assignment', 'subjects', 'classes'));
    }

    public function store(Request $request)
    {
        Log::info('AssignmentController@store called', ['payload' => $request->all(), 'user_id' => Auth::id()]);
        $validated = $request->validate([
            'title'           => 'required|max:255',
            'subject_id'      => 'nullable|exists:subjects,id',
            'class_id'        => 'nullable|exists:classes,id',
            'instructions'    => 'nullable|string',
            'due_date'        => 'nullable|date',
            'max_marks'       => 'required|integer|min:1',
            'submission_type' => 'required|in:file,text,link,any',
        ]);
        $validated['school_id']   = $this->school_id;
        $validated['teacher_id']  = Auth::id();
        $validated['is_published'] = 1;

        $a = Assignment::create($validated);
        AuditLog::record('create', 'Assignments', "Created assignment: {$a->title}");
        Log::info('Assignment created', ['id' => $a->id, 'attrs' => $a->toArray()]);
        return response()->json(['status' => 'success', 'message' => get_phrase('Assignment created')]);
    }

    public function update(Request $request, $id)
    {
        $assignment = Assignment::where('school_id', $this->school_id)->findOrFail($id);
        $validated  = $request->validate([
            'title'           => 'required|max:255',
            'subject_id'      => 'nullable|exists:subjects,id',
            'class_id'        => 'nullable|exists:classes,id',
            'instructions'    => 'nullable|string',
            'due_date'        => 'nullable|date',
            'max_marks'       => 'required|integer|min:1',
            'submission_type' => 'required|in:file,text,link,any',
        ]);
        $assignment->update($validated);
        return response()->json(['status' => 'success', 'message' => get_phrase('Assignment updated')]);
    }

    public function destroy($id)
    {
        $a = Assignment::where('school_id', $this->school_id)->findOrFail($id);
        AuditLog::record('delete', 'Assignments', "Deleted assignment: {$a->title}");
        $a->delete();
        return redirect()->back()->with('success', get_phrase('Assignment deleted'));
    }

    public function submissions($id)
    {
        $assignment  = Assignment::where('school_id', $this->school_id)->findOrFail($id);
        $submissions = AssignmentSubmission::where('assignment_id', $id)->with('student')->orderByDesc('submitted_at')->paginate(30);
        return view('admin.assignment.submissions', compact('assignment', 'submissions'));
    }

    public function gradeSubmission(Request $request, $submission_id)
    {
        $sub = AssignmentSubmission::findOrFail($submission_id);
        $request->validate(['marks_awarded' => 'required|numeric|min:0', 'feedback' => 'nullable|string']);
        $sub->update(['marks_awarded' => $request->marks_awarded, 'feedback' => $request->feedback, 'status' => 'graded']);
        return redirect()->back()->with('success', get_phrase('Submission graded'));
    }

    // ── Student ────────────────────────────────────────────────────────────

    public function studentList()
    {
        $student_id = Auth::id();
        $school_id  = Auth::user()->school_id;
        $enroll     = \App\Models\Enrollment::where('user_id', $student_id)->where('school_id', $school_id)->first();
        $class_id   = $enroll?->class_id;

        $assignments = Assignment::where('school_id', $school_id)
            ->where('is_published', 1)
            ->where(fn($q) => $q->whereNull('class_id')->orWhere('class_id', $class_id))
            ->with(['subject'])
            ->latest()
            ->get()
            ->map(function ($a) use ($student_id) {
                $a->my_submission = AssignmentSubmission::where('assignment_id', $a->id)
                    ->where('student_id', $student_id)->first();
                return $a;
            });

        return view('student.assignment.list', compact('assignments'));
    }

    public function submitModal($id)
    {
        $school_id  = Auth::user()->school_id;
        $assignment = Assignment::where('school_id', $school_id)->findOrFail($id);
        return view('student.assignment.submit_modal', compact('assignment'));
    }

    public function studentSubmit(Request $request, $id)
    {
        $school_id  = Auth::user()->school_id;
        $assignment = Assignment::where('school_id', $school_id)->where('is_published', 1)->findOrFail($id);
        $student_id = Auth::id();

        $existing = AssignmentSubmission::where('assignment_id', $id)->where('student_id', $student_id)->first();
        if ($existing) {
            return redirect()->back()->with('error', get_phrase('You have already submitted this assignment'));
        }

        $data = [
            'assignment_id' => $id,
            'student_id'    => $student_id,
            'submitted_at'  => now(),
            'status'        => now()->gt($assignment->due_date) ? 'late' : 'submitted',
        ];

        if ($request->hasFile('file')) {
            $file         = $request->file('file');
            $filename     = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('assets/uploads/assignments'), $filename);
            $data['file_path'] = 'assets/uploads/assignments/' . $filename;
        }

        if ($request->filled('submission')) {
            $data['submission'] = $request->submission;
        }

        if ($request->filled('link')) {
            $data['link'] = $request->link;
        }

        AssignmentSubmission::create($data);
        return redirect()->back()->with('success', get_phrase('Assignment submitted successfully'));
    }
}
