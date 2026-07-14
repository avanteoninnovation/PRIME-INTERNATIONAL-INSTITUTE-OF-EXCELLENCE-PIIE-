<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\OnlineExamAnswer;
use App\Models\OnlineExamProctoringEvent;
use App\Models\OnlineExam;
use App\Models\OnlineExamQuestion;
use App\Models\OnlineExamSubmission;
use App\Models\QuestionBank;
use App\Models\Session;
use App\Models\Subject;
use App\Models\TeacherPermission;
use App\Http\Requests\OnlineExam\CameraReadinessRequest;
use App\Http\Requests\OnlineExam\ManualMarkAnswerRequest;
use App\Http\Requests\OnlineExam\ProctoringEventRequest;
use App\Http\Requests\OnlineExam\SaveOnlineExamAnswerRequest;
use App\Http\Requests\OnlineExam\StartOnlineExamRequest;
use App\Http\Requests\OnlineExam\StoreOnlineExamQuestionRequest;
use App\Http\Requests\OnlineExam\StoreOnlineExamRequest;
use App\Http\Requests\OnlineExam\SubmitOnlineExamRequest;
use App\Http\Requests\OnlineExam\UpdateOnlineExamQuestionRequest;
use App\Http\Requests\OnlineExam\UpdateOnlineExamRequest;
use App\Support\Permissions\OnlineExamAuthorizer;
use App\Support\Permissions\OnlineExamPermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $this->authorize('viewAny', OnlineExam::class);

        $search  = $request->search ?? '';
        $exams   = OnlineExam::forSchool($this->school_id)
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
        if ($id) {
            $exam = $this->findExamOrFail((int) $id);
            $this->authorize('update', $exam);
        } else {
            $this->authorize('create', OnlineExam::class);
            $exam = null;
        }

        $subjects = Subject::where('school_id', $this->school_id)->orderBy('name')->get();
        $classes  = Classes::where('school_id', $this->school_id)->orderBy('name')->get();
        return view('admin.online_exam.modal', compact('exam', 'subjects', 'classes'));
    }

    public function create()
    {
        $this->authorize('create', OnlineExam::class);

        $subjects = Subject::where('school_id', $this->school_id)->orderBy('name')->get();
        $classes  = Classes::where('school_id', $this->school_id)->orderBy('name')->get();

        return view('admin.online_exam.modal', [
            'exam' => null,
            'subjects' => $subjects,
            'classes' => $classes,
        ]);
    }

    public function show($id)
    {
        $exam = $this->findExamOrFail((int) $id);
        $this->authorize('view', $exam);

        $exam->load(['subject', 'classRoom', 'questions', 'submissions.student']);

        return view('admin.online_exam.show', compact('exam'));
    }

    public function edit($id)
    {
        $exam = $this->findExamOrFail((int) $id);
        $this->authorize('update', $exam);

        $subjects = Subject::where('school_id', $this->school_id)->orderBy('name')->get();
        $classes  = Classes::where('school_id', $this->school_id)->orderBy('name')->get();

        return view('admin.online_exam.modal', compact('exam', 'subjects', 'classes'));
    }

    public function store(StoreOnlineExamRequest $request)
    {
        $this->authorize('create', OnlineExam::class);

        $validated = $request->validated();
        $payload = [
            'title' => $validated['title'],
            'instructions' => $validated['instructions'] ?? null,
            'subject_id' => $validated['subject_id'] ?? null,
            'class_id' => $validated['class_id'] ?? null,
            'exam_type' => $validated['exam_type'],
            'start_datetime' => $validated['start_datetime'] ?? null,
            'end_datetime' => $validated['end_datetime'] ?? null,
            'duration_mins' => (int) ($validated['duration_mins'] ?? 0),
            'total_marks' => (int) $validated['total_marks'],
            'pass_mark' => (int) $validated['pass_mark'],
            'max_attempts' => (int) ($validated['max_attempts'] ?? 1),
            'shuffle_questions' => (bool) ($validated['shuffle_questions'] ?? false),
            'shuffle_options' => (bool) ($validated['shuffle_options'] ?? false),
            'allow_previous_navigation' => (bool) ($validated['allow_previous_navigation'] ?? true),
            'result_release_policy' => $validated['result_release_policy'] ?? 'immediate',
            'webcam_required' => (bool) ($validated['webcam_required'] ?? false),
            'fullscreen_required' => (bool) ($validated['fullscreen_required'] ?? false),
            'auto_submit' => (bool) ($validated['auto_submit'] ?? true),
            'workflow_state' => $validated['workflow_state'] ?? 'draft',
            'school_id' => $this->school_id,
            'is_published' => ($validated['workflow_state'] ?? 'draft') === 'published',
            'created_by' => Auth::id(),
            'creator_id' => Auth::id(),
            'updater_id' => Auth::id(),
        ];

        $exam = DB::transaction(fn() => OnlineExam::create($payload));
        AuditLog::record('create', 'Online Exams', "Created exam: {$exam->title}");
        return response()->json(['status' => 'success', 'message' => get_phrase('Exam created'), 'id' => $exam->id]);
    }

    public function update(UpdateOnlineExamRequest $request, $id)
    {
        $exam = $this->findExamOrFail((int) $id);
        $this->authorize('update', $exam);

        $validated = $request->validated();
        DB::transaction(function () use ($exam, $validated) {
            $exam->update([
                'title' => $validated['title'],
                'instructions' => $validated['instructions'] ?? null,
                'subject_id' => $validated['subject_id'] ?? null,
                'class_id' => $validated['class_id'] ?? null,
                'exam_type' => $validated['exam_type'],
                'start_datetime' => $validated['start_datetime'] ?? null,
                'end_datetime' => $validated['end_datetime'] ?? null,
                'duration_mins' => (int) ($validated['duration_mins'] ?? 0),
                'total_marks' => (int) $validated['total_marks'],
                'pass_mark' => (int) $validated['pass_mark'],
                'max_attempts' => (int) ($validated['max_attempts'] ?? $exam->max_attempts),
                'shuffle_questions' => (bool) ($validated['shuffle_questions'] ?? false),
                'shuffle_options' => (bool) ($validated['shuffle_options'] ?? false),
                'allow_previous_navigation' => (bool) ($validated['allow_previous_navigation'] ?? true),
                'result_release_policy' => $validated['result_release_policy'] ?? 'immediate',
                'webcam_required' => (bool) ($validated['webcam_required'] ?? false),
                'fullscreen_required' => (bool) ($validated['fullscreen_required'] ?? false),
                'auto_submit' => (bool) ($validated['auto_submit'] ?? true),
                'workflow_state' => $validated['workflow_state'] ?? $exam->workflow_state,
                'is_published' => ($validated['workflow_state'] ?? $exam->workflow_state) === 'published',
                'updater_id' => Auth::id(),
            ]);
        });

        AuditLog::record('update', 'Online Exams', "Updated exam: {$exam->title}");
        return response()->json(['status' => 'success', 'message' => get_phrase('Exam updated')]);
    }

    public function publish($id)
    {
        $exam = $this->findExamOrFail((int) $id);
        $this->authorize('publish', $exam);

        DB::transaction(function () use ($exam) {
            $exam->refresh();
            if (!$exam->is_published) {
                $errors = $exam->publicationReadinessErrors();
                if (!empty($errors)) {
                    abort(422, implode(' ', $errors));
                }
            }

            $state = $exam->is_published ? 'draft' : 'published';
            $exam->update([
                'is_published' => !$exam->is_published,
                'workflow_state' => $state,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'updater_id' => Auth::id(),
            ]);
        });

        AuditLog::record('update', 'Online Exams', (($exam->is_published ? 'Published' : 'Unpublished') . " exam: {$exam->title}"));
        return redirect()->back()->with('success', get_phrase('Exam status updated'));
    }

    public function unpublish($id)
    {
        $exam = $this->findExamOrFail((int) $id);
        $this->authorize('unpublish', $exam);

        DB::transaction(function () use ($exam) {
            $exam->update([
                'is_published' => 0,
                'workflow_state' => 'draft',
                'updater_id' => Auth::id(),
            ]);
        });

        AuditLog::record('update', 'Online Exams', "Unpublished exam: {$exam->title}");
        return redirect()->back()->with('success', get_phrase('Exam unpublished'));
    }

    public function cancel(Request $request, $id)
    {
        $exam = $this->findExamOrFail((int) $id);
        $this->authorize('cancel', $exam);

        DB::transaction(function () use ($request, $exam) {
            $exam->update([
                'workflow_state' => 'cancelled',
                'is_published' => 0,
                'cancelled_at' => now(),
                'cancellation_reason' => $request->input('reason'),
                'updater_id' => Auth::id(),
            ]);
        });

        AuditLog::record('update', 'Online Exams', "Cancelled exam: {$exam->title}");
        return redirect()->back()->with('success', get_phrase('Exam cancelled'));
    }

    public function lock($id)
    {
        $exam = $this->findExamOrFail((int) $id);
        $this->authorize('update', $exam);

        DB::transaction(function () use ($exam) {
            $exam->update([
                'locked_at' => now(),
                'updater_id' => Auth::id(),
            ]);
        });

        AuditLog::record('update', 'Online Exams', "Locked exam structure: {$exam->title}");
        return redirect()->back()->with('success', get_phrase('Exam locked'));
    }

    public function destroy($id)
    {
        $exam = $this->findExamOrFail((int) $id);
        $this->authorize('delete', $exam);

        DB::transaction(function () use ($exam) {
            $exam->questions()->delete();
            $exam->delete();
        });

        AuditLog::record('delete', 'Online Exams', "Deleted exam: {$exam->title}");
        return redirect()->back()->with('success', get_phrase('Exam deleted'));
    }

    // ── Questions ─────────────────────────────────────────────────────────

    public function questions($exam_id)
    {
        $exam = $this->findExamOrFail((int) $exam_id);
        $this->authorize('manageQuestions', $exam);

        $questions = OnlineExamQuestion::forExam($exam->id)->ordered()->get();
        $bank      = QuestionBank::where('school_id', $this->school_id)
            ->when($exam->subject_id, fn($q) => $q->where('subject_id', $exam->subject_id))
            ->get();
        return view('admin.online_exam.questions', compact('exam', 'questions', 'bank'));
    }

    public function storeQuestion(StoreOnlineExamQuestionRequest $request, $exam_id)
    {
        $exam = $this->findExamOrFail((int) $exam_id);
        $this->authorize('manageQuestions', $exam);

        $validated = $request->validated();

        DB::transaction(function () use ($exam, $validated) {
            $nextSort = (int) OnlineExamQuestion::forExam($exam->id)->max('sort_order') + 1;
            OnlineExamQuestion::create([
                'online_exam_id' => $exam->id,
                'question' => $validated['question'],
                'type' => $validated['type'],
                'option_a' => $validated['option_a'] ?? null,
                'option_b' => $validated['option_b'] ?? null,
                'option_c' => $validated['option_c'] ?? null,
                'option_d' => $validated['option_d'] ?? null,
                'correct_ans' => $validated['correct_ans'] ?? null,
                'marks' => $validated['marks'],
                'sort_order' => $nextSort,
            ]);
        });

        AuditLog::record('create', 'Online Exams', "Created exam question in exam #{$exam->id}");
        return response()->json(['status' => 'success', 'message' => get_phrase('Question added')]);
    }

    public function updateQuestion(UpdateOnlineExamQuestionRequest $request, $id)
    {
        $question = OnlineExamQuestion::with('exam')->findOrFail((int) $id);
        abort_unless($question->exam && (int) $question->exam->school_id === (int) $this->school_id, 404);
        $this->authorize('update', $question);

        $validated = $request->validated();
        DB::transaction(function () use ($question, $validated) {
            $question->update([
                'question' => $validated['question'],
                'type' => $validated['type'],
                'option_a' => $validated['option_a'] ?? null,
                'option_b' => $validated['option_b'] ?? null,
                'option_c' => $validated['option_c'] ?? null,
                'option_d' => $validated['option_d'] ?? null,
                'correct_ans' => $validated['correct_ans'] ?? null,
                'marks' => $validated['marks'],
            ]);
        });

        AuditLog::record('update', 'Online Exams', "Updated exam question #{$question->id}");
        return response()->json(['status' => 'success', 'message' => get_phrase('Question updated')]);
    }

    public function deleteQuestion($id)
    {
        return $this->destroyQuestion($id);
    }

    public function destroyQuestion($id)
    {
        $question = OnlineExamQuestion::with('exam')->findOrFail((int) $id);
        abort_unless($question->exam && (int) $question->exam->school_id === (int) $this->school_id, 404);
        $this->authorize('delete', $question);

        DB::transaction(function () use ($question) {
            $question->delete();
        });

        AuditLog::record('delete', 'Online Exams', "Deleted exam question #{$question->id}");
        return redirect()->back()->with('success', get_phrase('Question deleted'));
    }

    // ── Question Bank ─────────────────────────────────────────────────────

    public function questionBank(Request $request)
    {
        $this->authorize('viewAny', OnlineExam::class);

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
        $this->authorize('create', OnlineExam::class);
        $subjects = Subject::where('school_id', $this->school_id)->orderBy('name')->get();
        return view('admin.online_exam.bank_modal', compact('subjects'));
    }

    public function destroyBankQuestion($id)
    {
        $this->authorize('create', OnlineExam::class);

        $question = QuestionBank::where('school_id', $this->school_id)->findOrFail((int) $id);
        DB::transaction(function () use ($question) {
            $question->delete();
        });

        AuditLog::record('delete', 'Online Exams', "Deleted question bank question #{$id}");
        return redirect()->back()->with('success', get_phrase('Question deleted'));
    }

    public function questionModal($exam_id)
    {
        $exam = $this->findExamOrFail((int) $exam_id);
        $this->authorize('manageQuestions', $exam);
        return view('admin.online_exam.question_modal', compact('exam_id'));
    }

    public function storeBankQuestion(Request $request)
    {
        $this->authorize('create', OnlineExam::class);

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

        DB::transaction(function () use ($validated) {
            QuestionBank::create([
                'school_id' => $this->school_id,
                'subject_id' => $validated['subject_id'] ?? null,
                'question' => $validated['question'],
                'type' => $validated['type'],
                'option_a' => $validated['option_a'] ?? null,
                'option_b' => $validated['option_b'] ?? null,
                'option_c' => $validated['option_c'] ?? null,
                'option_d' => $validated['option_d'] ?? null,
                'correct_ans' => $validated['correct_ans'] ?? null,
                'marks' => $validated['marks'],
                'difficulty' => $validated['difficulty'],
                'created_by' => Auth::id(),
            ]);
        });

        return response()->json(['status' => 'success', 'message' => get_phrase('Question added to bank')]);
    }

    // ── Teacher: online exams ─────────────────────────────────────────────

    public function teacherIndex(Request $request)
    {
        $this->authorize('viewAny', OnlineExam::class);

        $user = Auth::user();
        $authorizer = app(OnlineExamAuthorizer::class);
        $permissionService = app(OnlineExamPermissionService::class);
        $canEditAll = $authorizer->can($user, 'edit_all_online_exams');
        $assignedClassIds = $this->teacherAssignedClassIds((int) $user->id);

        $query = OnlineExam::forSchool($this->school_id)
            ->with(['subject', 'classRoom'])
            ->withCount('questions', 'submissions');

        if (!$canEditAll) {
            $query->where(function ($q) use ($user, $assignedClassIds) {
                $q->where('creator_id', $user->id)
                    ->orWhere('created_by', $user->id);

                if (!empty($assignedClassIds)) {
                    $q->orWhereIn('class_id', $assignedClassIds);
                }
            });
        }

        $search = trim((string) $request->input('title', ''));
        $subjectId = (int) $request->input('subject_id', 0);
        $classId = (int) $request->input('class_id', 0);
        $workflowState = trim((string) $request->input('workflow_state', ''));
        $lifecycleState = trim((string) $request->input('lifecycle_state', ''));
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $tab = trim((string) $request->input('tab', ''));

        $query->when($search !== '', function ($q) use ($search) {
            $q->where('title', 'like', '%' . $search . '%');
        });

        if ($subjectId > 0) {
            $query->where('subject_id', $subjectId);
        }

        if ($classId > 0) {
            $query->where('class_id', $classId);
        }

        if ($workflowState !== '') {
            $query->where('workflow_state', $workflowState);
        }

        if ($lifecycleState !== '') {
            $this->applyLifecycleFilter($query, $lifecycleState);
        }

        if (!empty($dateFrom)) {
            $query->whereDate('start_datetime', '>=', $dateFrom);
        }

        if (!empty($dateTo)) {
            $query->whereDate('end_datetime', '<=', $dateTo);
        }

        if ($tab !== '') {
            $this->applyTabFilter($query, $tab);
        }

        $exams = $query->latest()->paginate(20)->appends($request->all());

        $subjects = $this->teacherAssignableSubjects((int) $user->id, $assignedClassIds);
        $classes = $this->teacherAssignableClasses($assignedClassIds);
        $sessions = Session::where('school_id', $this->school_id)->orderByDesc('id')->get();

        return view('teacher.online_exam.index', compact(
            'exams',
            'subjects',
            'classes',
            'sessions',
            'search',
            'subjectId',
            'classId',
            'workflowState',
            'lifecycleState',
            'dateFrom',
            'dateTo',
            'tab',
            'canEditAll'
        ))->with([
            'canPublish' => $permissionService->has($user, 'publish_online_exams'),
            'canCreate' => $permissionService->has($user, 'create_online_exams'),
            'canManageQuestions' => $permissionService->has($user, 'manage_exam_questions'),
            'canMark' => $permissionService->has($user, 'mark_exam_answers'),
        ]);
    }

    public function teacherCreate()
    {
        $this->authorize('create', OnlineExam::class);

        $user = Auth::user();
        $assignedClassIds = $this->teacherAssignedClassIds((int) $user->id);
        $subjects = $this->teacherAssignableSubjects((int) $user->id, $assignedClassIds);
        $classes = $this->teacherAssignableClasses($assignedClassIds);
        $sessions = Session::where('school_id', $this->school_id)->orderByDesc('id')->get();

        return view('teacher.online_exam.create', [
            'exam' => null,
            'subjects' => $subjects,
            'classes' => $classes,
            'sessions' => $sessions,
            'structureLocked' => false,
            'readinessErrors' => [],
        ]);
    }

    public function teacherStore(StoreOnlineExamRequest $request)
    {
        $this->authorize('create', OnlineExam::class);

        $user = Auth::user();
        $validated = $request->validated();

        $payload = [
            'title' => $validated['title'],
            'instructions' => $validated['instructions'] ?? null,
            'subject_id' => $validated['subject_id'],
            'class_id' => $validated['class_id'] ?? null,
            'exam_type' => $validated['exam_type'],
            'start_datetime' => $validated['start_datetime'],
            'end_datetime' => $validated['end_datetime'],
            'duration_mins' => (int) ($validated['duration_mins'] ?? 0),
            'total_marks' => (int) $validated['total_marks'],
            'pass_mark' => (int) $validated['pass_mark'],
            'max_attempts' => (int) ($validated['max_attempts'] ?? 1),
            'shuffle_questions' => (bool) ($validated['shuffle_questions'] ?? false),
            'shuffle_options' => (bool) ($validated['shuffle_options'] ?? false),
            'allow_previous_navigation' => (bool) ($validated['allow_previous_navigation'] ?? true),
            'result_release_policy' => $validated['result_release_policy'] ?? 'immediate',
            'webcam_required' => (bool) ($validated['webcam_required'] ?? false),
            'fullscreen_required' => (bool) ($validated['fullscreen_required'] ?? false),
            'auto_submit' => (bool) ($validated['auto_submit'] ?? true),
            'workflow_state' => 'draft',
            'is_published' => 0,
            'school_id' => $this->school_id,
            'created_by' => $user->id,
            'creator_id' => $user->id,
            'updater_id' => $user->id,
        ];

        $exam = DB::transaction(fn() => OnlineExam::create($payload));
        AuditLog::record('create', 'Online Exams', "Teacher created exam: {$exam->title}");

        return redirect()->route('teacher.online_exams.edit', $exam->id)->with('success', get_phrase('Exam created as draft.'));
    }

    public function teacherShow(OnlineExam $exam)
    {
        $this->authorize('view', $exam);
        abort_unless((int) $exam->school_id === (int) $this->school_id, 404);

        $exam->load([
            'subject',
            'classRoom',
            'questions',
            'submissions.student',
        ]);

        return view('teacher.online_exam.show', [
            'exam' => $exam,
            'structureLocked' => $exam->isStructurallyLocked(),
            'readinessErrors' => $exam->publicationReadinessErrors(),
        ]);
    }

    public function teacherEdit(OnlineExam $exam)
    {
        $this->authorize('update', $exam);
        abort_unless((int) $exam->school_id === (int) $this->school_id, 404);

        $user = Auth::user();
        $assignedClassIds = $this->teacherAssignedClassIds((int) $user->id);
        $subjects = $this->teacherAssignableSubjects((int) $user->id, $assignedClassIds);
        $classes = $this->teacherAssignableClasses($assignedClassIds);
        $sessions = Session::where('school_id', $this->school_id)->orderByDesc('id')->get();

        return view('teacher.online_exam.edit', [
            'exam' => $exam,
            'subjects' => $subjects,
            'classes' => $classes,
            'sessions' => $sessions,
            'structureLocked' => $exam->isStructurallyLocked(),
            'readinessErrors' => $exam->publicationReadinessErrors(),
        ]);
    }

    public function teacherUpdate(UpdateOnlineExamRequest $request, OnlineExam $exam)
    {
        $this->authorize('update', $exam);
        abort_unless((int) $exam->school_id === (int) $this->school_id, 404);

        $validated = $request->validated();

        DB::transaction(function () use ($exam, $validated) {
            $exam->update([
                'title' => $validated['title'],
                'instructions' => $validated['instructions'] ?? null,
                'subject_id' => $validated['subject_id'],
                'class_id' => $validated['class_id'] ?? null,
                'exam_type' => $validated['exam_type'],
                'start_datetime' => $validated['start_datetime'],
                'end_datetime' => $validated['end_datetime'],
                'duration_mins' => (int) ($validated['duration_mins'] ?? 0),
                'total_marks' => (int) $validated['total_marks'],
                'pass_mark' => (int) $validated['pass_mark'],
                'max_attempts' => (int) ($validated['max_attempts'] ?? $exam->max_attempts),
                'shuffle_questions' => (bool) ($validated['shuffle_questions'] ?? false),
                'shuffle_options' => (bool) ($validated['shuffle_options'] ?? false),
                'allow_previous_navigation' => (bool) ($validated['allow_previous_navigation'] ?? true),
                'result_release_policy' => $validated['result_release_policy'] ?? 'immediate',
                'webcam_required' => (bool) ($validated['webcam_required'] ?? false),
                'fullscreen_required' => (bool) ($validated['fullscreen_required'] ?? false),
                'auto_submit' => (bool) ($validated['auto_submit'] ?? true),
                'updater_id' => Auth::id(),
            ]);
        });

        AuditLog::record('update', 'Online Exams', "Teacher updated exam: {$exam->title}");
        return redirect()->route('teacher.online_exams.edit', $exam->id)->with('success', get_phrase('Exam updated.'));
    }

    public function teacherDestroy(OnlineExam $exam)
    {
        $this->authorize('delete', $exam);
        abort_unless((int) $exam->school_id === (int) $this->school_id, 404);

        if (!in_array((string) $exam->workflow_state, ['draft', 'pending_review'], true)) {
            return redirect()->back()->withErrors(['exam' => get_phrase('Only draft or pending review exams can be deleted.')]);
        }

        if ($exam->submissions()->exists()) {
            return redirect()->back()->withErrors(['exam' => get_phrase('Cannot delete an exam that already has attempts.')]);
        }

        DB::transaction(function () use ($exam) {
            $exam->questions()->delete();
            $exam->delete();
        });

        AuditLog::record('delete', 'Online Exams', "Teacher deleted exam: {$exam->title}");
        return redirect()->route('teacher.online_exams.index')->with('success', get_phrase('Exam deleted.'));
    }

    public function teacherPreview(OnlineExam $exam)
    {
        $this->authorize('view', $exam);
        abort_unless((int) $exam->school_id === (int) $this->school_id, 404);

        $questions = OnlineExamQuestion::forExam($exam->id)
            ->ordered()
            ->get()
            ->map(function (OnlineExamQuestion $question) {
                $question->correct_ans = null;
                return $question;
            });

        return view('teacher.online_exam.preview', compact('exam', 'questions'));
    }

    public function teacherSubmitForReview(OnlineExam $exam)
    {
        $this->authorize('update', $exam);
        abort_unless((int) $exam->school_id === (int) $this->school_id, 404);

        $errors = $exam->publicationReadinessErrors();
        if (!empty($errors)) {
            return redirect()->back()->withErrors(['readiness' => implode(' ', $errors)]);
        }

        DB::transaction(function () use ($exam) {
            $exam->update([
                'workflow_state' => 'pending_review',
                'is_published' => 0,
                'reviewed_at' => now(),
                'updater_id' => Auth::id(),
            ]);
        });

        AuditLog::record('update', 'Online Exams', "Teacher submitted exam for review: {$exam->title}");
        return redirect()->back()->with('success', get_phrase('Exam submitted for review.'));
    }

    public function teacherPublish(OnlineExam $exam)
    {
        $this->authorize('publish', $exam);
        abort_unless((int) $exam->school_id === (int) $this->school_id, 404);

        $errors = $exam->publicationReadinessErrors();
        if (!empty($errors)) {
            return redirect()->back()->withErrors(['readiness' => implode(' ', $errors)]);
        }

        DB::transaction(function () use ($exam) {
            $exam->update([
                'workflow_state' => 'published',
                'is_published' => 1,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'updater_id' => Auth::id(),
            ]);
        });

        AuditLog::record('update', 'Online Exams', "Teacher published exam: {$exam->title}");
        return redirect()->back()->with('success', get_phrase('Exam published.'));
    }

    public function teacherUnpublish(OnlineExam $exam)
    {
        $this->authorize('unpublish', $exam);
        abort_unless((int) $exam->school_id === (int) $this->school_id, 404);

        if ($exam->submissions()->exists()) {
            return redirect()->back()->withErrors(['exam' => get_phrase('Cannot unpublish an exam with attempts.')]);
        }

        DB::transaction(function () use ($exam) {
            $exam->update([
                'workflow_state' => 'draft',
                'is_published' => 0,
                'updater_id' => Auth::id(),
            ]);
        });

        AuditLog::record('update', 'Online Exams', "Teacher unpublished exam: {$exam->title}");
        return redirect()->back()->with('success', get_phrase('Exam unpublished.'));
    }

    public function teacherCancel(Request $request, OnlineExam $exam)
    {
        $this->authorize('cancel', $exam);
        abort_unless((int) $exam->school_id === (int) $this->school_id, 404);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($exam, $validated) {
            $exam->update([
                'workflow_state' => 'cancelled',
                'is_published' => 0,
                'cancelled_at' => now(),
                'cancellation_reason' => $validated['reason'],
                'updater_id' => Auth::id(),
            ]);
        });

        AuditLog::record('update', 'Online Exams', "Teacher cancelled exam: {$exam->title}");
        return redirect()->back()->with('success', get_phrase('Exam cancelled.'));
    }

    public function teacherQuestions(OnlineExam $exam)
    {
        $this->authorize('manageQuestions', $exam);
        abort_unless((int) $exam->school_id === (int) $this->school_id, 404);

        $user = Auth::user();
        $questions = OnlineExamQuestion::forExam($exam->id)->ordered()->get();
        $bank = QuestionBank::visibleToTeacher((int) $user->id, $this->school_id)
            ->when(!empty($exam->subject_id), fn($q) => $q->where('subject_id', $exam->subject_id))
            ->latest('id')
            ->limit(150)
            ->get();

        return view('teacher.online_exam.questions', [
            'exam' => $exam,
            'questions' => $questions,
            'bank' => $bank,
            'questionMarksTotal' => (int) $questions->sum('marks'),
            'structureLocked' => $exam->isStructurallyLocked(),
        ]);
    }

    public function teacherStoreQuestion(StoreOnlineExamQuestionRequest $request, OnlineExam $exam)
    {
        $this->authorize('manageQuestions', $exam);
        abort_unless((int) $exam->school_id === (int) $this->school_id, 404);

        $validated = $request->validated();

        DB::transaction(function () use ($exam, $validated) {
            $nextSort = ((int) OnlineExamQuestion::forExam($exam->id)->max('sort_order')) + 1;
            OnlineExamQuestion::create([
                'online_exam_id' => $exam->id,
                'question' => $validated['question'],
                'type' => $validated['type'],
                'option_a' => $validated['option_a'] ?? null,
                'option_b' => $validated['option_b'] ?? null,
                'option_c' => $validated['option_c'] ?? null,
                'option_d' => $validated['option_d'] ?? null,
                'correct_ans' => $validated['correct_ans'] ?? null,
                'marks' => $validated['marks'],
                'sort_order' => $nextSort,
            ]);
        });

        AuditLog::record('create', 'Online Exams', "Teacher added question in exam #{$exam->id}");
        return redirect()->back()->with('success', get_phrase('Question added.'));
    }

    public function teacherUpdateQuestion(UpdateOnlineExamQuestionRequest $request, OnlineExamQuestion $question)
    {
        $question->loadMissing('exam');
        abort_unless($question->exam && (int) $question->exam->school_id === (int) $this->school_id, 404);
        $this->authorize('update', $question);

        $validated = $request->validated();

        DB::transaction(function () use ($question, $validated) {
            $question->update([
                'question' => $validated['question'],
                'type' => $validated['type'],
                'option_a' => $validated['option_a'] ?? null,
                'option_b' => $validated['option_b'] ?? null,
                'option_c' => $validated['option_c'] ?? null,
                'option_d' => $validated['option_d'] ?? null,
                'correct_ans' => $validated['correct_ans'] ?? null,
                'marks' => $validated['marks'],
            ]);
        });

        AuditLog::record('update', 'Online Exams', "Teacher updated question #{$question->id}");
        return redirect()->back()->with('success', get_phrase('Question updated.'));
    }

    public function teacherDeleteQuestion(OnlineExamQuestion $question)
    {
        $question->loadMissing('exam');
        abort_unless($question->exam && (int) $question->exam->school_id === (int) $this->school_id, 404);
        $this->authorize('delete', $question);

        DB::transaction(function () use ($question) {
            $question->delete();
        });

        AuditLog::record('delete', 'Online Exams', "Teacher deleted question #{$question->id}");
        return redirect()->back()->with('success', get_phrase('Question deleted.'));
    }

    public function teacherReorderQuestions(Request $request, OnlineExam $exam)
    {
        $this->authorize('manageQuestions', $exam);
        abort_unless((int) $exam->school_id === (int) $this->school_id, 404);

        if ($exam->isStructurallyLocked()) {
            return redirect()->back()->withErrors(['questions' => get_phrase('Questions are locked after attempts have started.')]);
        }

        $validated = $request->validate([
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['required', 'integer'],
        ]);

        $allowedIds = OnlineExamQuestion::forExam($exam->id)->pluck('id')->map(fn($id) => (int) $id)->all();
        foreach ($validated['question_ids'] as $questionId) {
            if (!in_array((int) $questionId, $allowedIds, true)) {
                return redirect()->back()->withErrors(['questions' => get_phrase('Invalid question order payload.')]);
            }
        }

        DB::transaction(function () use ($validated) {
            foreach ($validated['question_ids'] as $index => $questionId) {
                OnlineExamQuestion::where('id', (int) $questionId)->update([
                    'sort_order' => $index + 1,
                ]);
            }
        });

        AuditLog::record('update', 'Online Exams', "Teacher reordered questions in exam #{$exam->id}");
        return redirect()->back()->with('success', get_phrase('Question order updated.'));
    }

    public function teacherQuestionBank(Request $request)
    {
        $user = Auth::user();
        $permissionService = app(OnlineExamPermissionService::class);
        abort_unless($permissionService->has($user, 'manage_exam_questions'), 403);

        $assignedClassIds = $this->teacherAssignedClassIds((int) $user->id);
        $search = trim((string) $request->input('search', ''));
        $subjectId = (int) $request->input('subject_id', 0);

        $query = QuestionBank::visibleToTeacher((int) $user->id, $this->school_id)
            ->with('subject')
            ->where(function ($q) use ($assignedClassIds) {
                if (empty($assignedClassIds)) {
                    $q->whereNull('subject_id');
                    return;
                }

                $q->whereNull('subject_id')
                    ->orWhereIn('subject_id', Subject::where('school_id', $this->school_id)
                        ->whereIn('class_id', $assignedClassIds)
                        ->pluck('id'));
            });

        $query->when($search !== '', function ($q) use ($search) {
            $q->where('question', 'like', '%' . $search . '%');
        });

        if ($subjectId > 0) {
            $query->where('subject_id', $subjectId);
        }

        $questions = $query->orderByDesc('id')->paginate(20)->appends($request->all());
        $subjects = $this->teacherAssignableSubjects((int) $user->id, $assignedClassIds);

        return view('teacher.online_exam.question_bank', compact('questions', 'subjects', 'search', 'subjectId'));
    }

    public function teacherImportQuestion(Request $request, OnlineExam $exam)
    {
        $this->authorize('manageQuestions', $exam);
        abort_unless((int) $exam->school_id === (int) $this->school_id, 404);

        if ($exam->isStructurallyLocked()) {
            return redirect()->back()->withErrors(['questions' => get_phrase('Questions are locked after attempts have started.')]);
        }

        $validated = $request->validate([
            'question_bank_ids' => ['required', 'array', 'min:1'],
            'question_bank_ids.*' => ['required', 'integer', 'exists:question_banks,id'],
        ]);

        $user = Auth::user();
        $bankQuestions = QuestionBank::visibleToTeacher((int) $user->id, $this->school_id)
            ->whereIn('id', $validated['question_bank_ids'])
            ->get();

        $imported = 0;
        DB::transaction(function () use ($exam, $bankQuestions, &$imported) {
            $nextSort = ((int) OnlineExamQuestion::forExam($exam->id)->max('sort_order')) + 1;
            foreach ($bankQuestions as $bankQuestion) {
                OnlineExamQuestion::create([
                    'online_exam_id' => $exam->id,
                    'question_bank_id' => $bankQuestion->id,
                    'question' => $bankQuestion->question,
                    'type' => $bankQuestion->type,
                    'option_a' => $bankQuestion->option_a,
                    'option_b' => $bankQuestion->option_b,
                    'option_c' => $bankQuestion->option_c,
                    'option_d' => $bankQuestion->option_d,
                    'correct_ans' => $bankQuestion->correct_ans,
                    'marks' => $bankQuestion->marks,
                    'sort_order' => $nextSort,
                ]);
                $nextSort++;
                $imported++;
            }
        });

        AuditLog::record('create', 'Online Exams', "Teacher imported {$imported} question(s) from bank into exam #{$exam->id}");
        return redirect()->back()->with('success', get_phrase('Questions imported from bank.'));
    }

    public function teacherAttempts(OnlineExam $exam)
    {
        $this->authorize('viewAttempts', $exam);
        abort_unless((int) $exam->school_id === (int) $this->school_id, 404);

        $submissions = OnlineExamSubmission::where('online_exam_id', $exam->id)
            ->where('school_id', $this->school_id)
            ->with('student')
            ->withCount('proctoringEvents')
            ->orderByDesc('submitted_at')
            ->paginate(30);

        return view('teacher.online_exam.attempts', compact('exam', 'submissions'));
    }

    public function teacherResults(OnlineExam $exam)
    {
        $this->authorize('viewAttempts', $exam);
        abort_unless((int) $exam->school_id === (int) $this->school_id, 404);

        $submissions = OnlineExamSubmission::where('online_exam_id', $exam->id)
            ->where('school_id', $this->school_id)
            ->with('student')
            ->orderByDesc('submitted_at')
            ->paginate(30);

        return view('teacher.online_exam.results', compact('exam', 'submissions'));
    }

    public function teacherMarking(Request $request)
    {
        $user = Auth::user();
        $permissionService = app(OnlineExamPermissionService::class);
        abort_unless($permissionService->has($user, 'mark_exam_answers'), 403);

        $authorizer = app(OnlineExamAuthorizer::class);
        $canEditAll = $authorizer->can($user, 'edit_all_online_exams');

        $query = OnlineExamAnswer::query()
            ->with(['submission.student', 'submission.exam', 'question'])
            ->whereHas('submission', function ($submissionQ) use ($canEditAll, $user) {
                $submissionQ->where('school_id', $this->school_id)
                    ->whereHas('exam', function ($examQ) use ($canEditAll, $user) {
                        if (!$canEditAll) {
                            $examQ->where(function ($ownedQ) use ($user) {
                                $ownedQ->where('creator_id', $user->id)
                                    ->orWhere('created_by', $user->id);
                            });
                        }
                    });
            })
            ->whereHas('question', function ($questionQ) {
                $questionQ->whereIn('type', ['short', 'essay', 'fill_blank']);
            });

        $status = trim((string) $request->input('status', 'pending'));
        if ($status === 'marked') {
            $query->whereNotNull('awarded_marks');
        } else {
            $query->whereNull('awarded_marks');
        }

        $answers = $query->orderBy('id')->paginate(25)->appends($request->all());

        return view('teacher.online_exam.marking', compact('answers', 'status'));
    }

    public function teacherMarkAnswer(ManualMarkAnswerRequest $request, OnlineExamAnswer $answer)
    {
        $answer->loadMissing(['submission.exam', 'question']);
        abort_unless((int) $answer->submission->school_id === (int) $this->school_id, 404);
        $this->authorize('mark', $answer);

        if (!in_array($answer->question?->type, ['short', 'essay', 'fill_blank'], true)) {
            return redirect()->back()->withErrors(['answer' => get_phrase('Only written answers can be manually marked.')]);
        }

        $validated = $request->validated();
        DB::transaction(function () use ($answer, $validated) {
            $answer->update([
                'awarded_marks' => $validated['awarded_marks'],
                'marked_by' => Auth::id(),
                'marked_at' => now(),
                'teacher_comment' => $validated['teacher_comment'] ?? null,
            ]);

            $this->recomputeSubmissionScore($answer->submission);
        });

        AuditLog::record('update', 'Online Exams', "Teacher marked answer #{$answer->id}");
        return redirect()->back()->with('success', get_phrase('Answer marked.'));
    }

    public function teacherFinalizeResult(OnlineExamSubmission $submission)
    {
        $submission->loadMissing(['exam', 'answerRows.question']);
        abort_unless((int) $submission->school_id === (int) $this->school_id, 404);
        $this->authorize('grade', $submission);

        $pendingManual = $submission->answerRows
            ->filter(fn(OnlineExamAnswer $answer) => in_array($answer->question?->type, ['short', 'essay', 'fill_blank'], true))
            ->filter(fn(OnlineExamAnswer $answer) => is_null($answer->awarded_marks))
            ->count();

        if ($pendingManual > 0) {
            return redirect()->back()->withErrors(['submission' => get_phrase('Finalize is blocked until all written answers are marked.')]);
        }

        DB::transaction(function () use ($submission) {
            $locked = OnlineExamSubmission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $this->recomputeSubmissionScore($locked);

            $locked->update([
                'status' => OnlineExamSubmission::STATUS_FINALIZED,
                'submitted_via' => $locked->submitted_via ?: 'teacher',
            ]);
        });

        AuditLog::record('update', 'Online Exams', "Teacher finalized result for submission #{$submission->id}");
        return redirect()->back()->with('success', get_phrase('Result finalized.'));
    }

    // ── Student: take exam ─────────────────────────────────────────────────

    public function studentExams(Request $request)
    {
        $this->authorize('viewAny', OnlineExam::class);

        $student_id = Auth::id();
        $school_id  = Auth::user()->school_id;
        $enroll     = Enrollment::where('user_id', $student_id)->where('school_id', $school_id)->first();
        $class_id   = $enroll?->class_id;

        $exams = OnlineExam::visibleToStudent($school_id, $class_id)
            ->get()
            ->map(function ($exam) use ($student_id) {
                $exam->submission = OnlineExamSubmission::where('online_exam_id', $exam->id)
                    ->where('student_id', $student_id)
                    ->orderByDesc('attempt_no')
                    ->first();
                return $exam;
            });

        return view('student.online_exam.list', compact('exams'));
    }

    public function instructions($id)
    {
        $exam = $this->findStudentExamOrFail((int) $id);
        $this->authorize('sit', $exam);

        return response()->json([
            'exam_id' => $exam->id,
            'title' => $exam->title,
            'instructions' => $exam->instructions,
            'duration_mins' => (int) $exam->duration_mins,
            'start_datetime' => optional($exam->start_datetime)->toDateTimeString(),
            'end_datetime' => optional($exam->end_datetime)->toDateTimeString(),
            'webcam_required' => (bool) $exam->webcam_required,
            'fullscreen_required' => (bool) $exam->fullscreen_required,
        ]);
    }

    public function readiness(CameraReadinessRequest $request, $submissionId)
    {
        $submission = $this->findStudentSubmissionOrFail((int) $submissionId);
        $this->authorize('view', $submission);

        DB::transaction(function () use ($request, $submission) {
            $submission->update([
                'camera_consent_at' => $request->boolean('consent_accepted') ? now() : $submission->camera_consent_at,
                'camera_permission_granted' => $request->boolean('permission_granted'),
                'camera_ready_at' => $request->boolean('camera_ready') ? now() : $submission->camera_ready_at,
                'last_activity_at' => now(),
            ]);
        });

        return response()->json(['status' => 'success', 'server_time' => now()->toDateTimeString()]);
    }

    public function start(StartOnlineExamRequest $request, $id)
    {
        $exam = $this->findStudentExamOrFail((int) $id);
        $this->authorize('sit', $exam);

        $submission = DB::transaction(function () use ($request, $exam) {
            $lockedExam = OnlineExam::whereKey($exam->id)->lockForUpdate()->firstOrFail();

            if ($lockedExam->workflow_state !== 'published') {
                abort(422, 'Exam is not published.');
            }

            $studentId = Auth::id();
            $active = OnlineExamSubmission::where('online_exam_id', $lockedExam->id)
                ->where('student_id', $studentId)
                ->where('school_id', $this->school_id)
                ->where('status', OnlineExamSubmission::STATUS_IN_PROGRESS)
                ->lockForUpdate()
                ->first();

            if ($active) {
                abort(422, 'An active attempt already exists.');
            }

            $attemptNo = ((int) OnlineExamSubmission::where('online_exam_id', $lockedExam->id)
                ->where('student_id', $studentId)
                ->lockForUpdate()
                ->max('attempt_no')) + 1;

            if ($attemptNo > (int) $lockedExam->max_attempts) {
                abort(422, 'Maximum attempts exceeded.');
            }

            $startedAt = now();
            $durationExpiry = $startedAt->copy()->addMinutes((int) $lockedExam->duration_mins);
            $expiresAt = $lockedExam->end_datetime && $durationExpiry->gt($lockedExam->end_datetime)
                ? $lockedExam->end_datetime->copy()
                : $durationExpiry;

            $submission = OnlineExamSubmission::create([
                'online_exam_id' => $lockedExam->id,
                'student_id' => $studentId,
                'school_id' => $this->school_id,
                'attempt_no' => $attemptNo,
                'started_at' => $startedAt,
                'expires_at' => $expiresAt,
                'last_activity_at' => $startedAt,
                'status' => OnlineExamSubmission::STATUS_IN_PROGRESS,
                'total_marks_snapshot' => (int) $lockedExam->total_marks,
                'browser_session_token' => Str::uuid()->toString(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'camera_consent_at' => $request->boolean('camera_consent_accepted') ? now() : null,
                'camera_permission_granted' => $request->boolean('camera_ready'),
                'camera_ready_at' => $request->boolean('camera_ready') ? now() : null,
                'fullscreen_started_at' => $request->boolean('fullscreen_ready') ? now() : null,
            ]);

            return $submission;
        });

        AuditLog::record('create', 'Online Exams', "Started attempt #{$submission->id} for exam #{$exam->id}");

        return response()->json([
            'status' => 'success',
            'submission_id' => $submission->id,
            'attempt_no' => $submission->attempt_no,
            'expires_at' => optional($submission->expires_at)->toDateTimeString(),
            'server_time' => now()->toDateTimeString(),
        ]);
    }

    public function resume($submissionId)
    {
        $submission = $this->findStudentSubmissionOrFail((int) $submissionId);
        $this->authorize('view', $submission);

        if ($submission->status !== OnlineExamSubmission::STATUS_IN_PROGRESS) {
            return response()->json(['status' => 'error', 'message' => 'Attempt is not active.'], 422);
        }

        AuditLog::record('update', 'Online Exams', "Resumed attempt #{$submission->id}");

        return response()->json([
            'status' => 'success',
            'submission_id' => $submission->id,
            'expires_at' => optional($submission->expires_at)->toDateTimeString(),
            'server_time' => now()->toDateTimeString(),
        ]);
    }

    public function takeExam($id)
    {
        $exam = $this->findStudentExamOrFail((int) $id);
        $this->authorize('sit', $exam);

        $submission = OnlineExamSubmission::where('online_exam_id', $exam->id)
            ->where('student_id', Auth::id())
            ->where('school_id', $this->school_id)
            ->orderByDesc('attempt_no')
            ->first();

        if (!$submission || $submission->status !== OnlineExamSubmission::STATUS_IN_PROGRESS) {
            return redirect()->route('student.online_exam.instructions', $exam->id);
        }

        if ($submission->isExpired()) {
            return redirect()->route('student.online_exam.timeout_submit', $submission->id);
        }

        $questions = OnlineExamQuestion::forExam($exam->id)
            ->ordered()
            ->get()
            ->map(function (OnlineExamQuestion $q) {
                $q->correct_ans = null;
                return $q;
            });

        return view('student.online_exam.take', [
            'exam' => $exam,
            'questions' => $questions,
            'existing' => $submission,
        ]);
    }

    public function saveAnswer(SaveOnlineExamAnswerRequest $request, $submissionId)
    {
        $submission = $this->findStudentSubmissionOrFail((int) $submissionId);
        $this->authorize('view', $submission);

        $validated = $request->validated();
        $responseData = DB::transaction(function () use ($submission, $validated) {
            $lockedSubmission = OnlineExamSubmission::whereKey($submission->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSubmission->status !== OnlineExamSubmission::STATUS_IN_PROGRESS) {
                abort(422, 'Submission is not active.');
            }

            if ($lockedSubmission->isExpired()) {
                abort(422, 'Submission has expired.');
            }

            $question = OnlineExamQuestion::where('id', (int) $validated['question_id'])
                ->where('online_exam_id', $lockedSubmission->online_exam_id)
                ->first();

            if (!$question) {
                abort(422, 'Question does not belong to this exam.');
            }

            OnlineExamAnswer::updateOrCreate(
                [
                    'submission_id' => $lockedSubmission->id,
                    'question_id' => $question->id,
                ],
                [
                    'selected_option' => $validated['selected_option'] ?? null,
                    'answer_text' => $validated['answer_text'] ?? null,
                ]
            );

            $lockedSubmission->update([
                'last_activity_at' => now(),
            ]);

            return [
                'status' => 'success',
                'submission_id' => $lockedSubmission->id,
                'expires_at' => optional($lockedSubmission->expires_at)->toDateTimeString(),
                'server_time' => now()->toDateTimeString(),
            ];
        });

        return response()->json($responseData);
    }

    public function heartbeat($submissionId)
    {
        $submission = $this->findStudentSubmissionOrFail((int) $submissionId);
        $this->authorize('view', $submission);

        $submission->update(['last_activity_at' => now()]);

        return response()->json([
            'status' => 'ok',
            'server_time' => now()->toDateTimeString(),
            'expires_at' => optional($submission->expires_at)->toDateTimeString(),
            'expired' => $submission->isExpired(),
        ]);
    }

    public function proctoringEvent(ProctoringEventRequest $request, $submissionId)
    {
        $submission = $this->findStudentSubmissionOrFail((int) $submissionId);
        $this->authorize('view', $submission);

        $validated = $request->validated();
        $event = OnlineExamProctoringEvent::create([
            'submission_id' => $submission->id,
            'event_type' => $validated['event_type'],
            'event_time' => $validated['event_time'] ?? now(),
            'metadata' => $validated['metadata'] ?? null,
        ]);

        if (in_array($event->event_type, ['tab_hidden', 'fullscreen_exited', 'camera_stopped'], true)) {
            AuditLog::record('update', 'Online Exams', "Proctoring event {$event->event_type} on submission #{$submission->id}");
        }

        return response()->json([
            'status' => 'success',
            'event_id' => $event->id,
            'server_time' => now()->toDateTimeString(),
        ]);
    }

    public function submitExam(SubmitOnlineExamRequest $request, $id)
    {
        $exam = $this->findStudentExamOrFail((int) $id);
        $submission = OnlineExamSubmission::where('online_exam_id', $exam->id)
            ->where('student_id', Auth::id())
            ->where('school_id', $this->school_id)
            ->where('status', OnlineExamSubmission::STATUS_IN_PROGRESS)
            ->orderByDesc('attempt_no')
            ->firstOrFail();

        return $this->submitBySubmission($submission, 'manual');
    }

    public function timeoutSubmit($submissionId)
    {
        $submission = $this->findStudentSubmissionOrFail((int) $submissionId);
        $this->authorize('submit', $submission);

        return $this->submitBySubmission($submission, 'timeout');
    }

    public function examResult($submission_id)
    {
        $submission = $this->findStudentSubmissionOrFail((int) $submission_id);
        $this->authorize('viewResult', $submission);

        $exam       = $submission->exam;
        $this->authorize('viewResult', $exam);

        $questions  = OnlineExamQuestion::forExam($exam->id)
            ->ordered()
            ->get()
            ->map(function (OnlineExamQuestion $q) {
                $q->correct_ans = null;
                return $q;
            });

        return view('student.online_exam.result', compact('submission', 'exam', 'questions'));
    }

    // ── Submissions (admin view) ───────────────────────────────────────────

    public function submissions($exam_id)
    {
        $exam = $this->findExamOrFail((int) $exam_id);
        $this->authorize('viewAttempts', $exam);

        $submissions = OnlineExamSubmission::where('online_exam_id', $exam->id)
            ->where('school_id', $this->school_id)
            ->with('student')
            ->orderByDesc('submitted_at')
            ->paginate(30);

        return view('admin.online_exam.submissions', compact('exam', 'submissions'));
    }

    public function results($exam_id)
    {
        $exam = $this->findExamOrFail((int) $exam_id);
        $this->authorize('markAnswers', $exam);

        $submissions = OnlineExamSubmission::where('online_exam_id', $exam->id)
            ->where('school_id', $this->school_id)
            ->with('student')
            ->orderByDesc('submitted_at')
            ->paginate(30);

        return view('admin.online_exam.results', compact('exam', 'submissions'));
    }

    public function reviewProctoring($exam_id, $submission_id)
    {
        $exam = $this->findExamOrFail((int) $exam_id);
        $this->authorize('reviewProctoring', $exam);

        $submission = OnlineExamSubmission::where('id', (int) $submission_id)
            ->where('online_exam_id', $exam->id)
            ->where('school_id', $this->school_id)
            ->with('student')
            ->firstOrFail();
        $this->authorize('view', $submission);

        $events = OnlineExamProctoringEvent::forSubmission($submission->id)
            ->chronological()
            ->paginate(100);

        return view('admin.online_exam.proctoring', compact('exam', 'submission', 'events'));
    }

    public function manualMarking(ManualMarkAnswerRequest $request, $answerId)
    {
        $answer = OnlineExamAnswer::with(['submission.exam', 'question'])->findOrFail((int) $answerId);
        abort_unless((int) $answer->submission->school_id === (int) $this->school_id, 404);
        $this->authorize('mark', $answer);

        $validated = $request->validated();
        DB::transaction(function () use ($answer, $validated) {
            $answer->update([
                'awarded_marks' => $validated['awarded_marks'],
                'marked_by' => Auth::id(),
                'marked_at' => now(),
                'teacher_comment' => $validated['teacher_comment'] ?? null,
            ]);

            $this->recomputeSubmissionScore($answer->submission);
        });

        AuditLog::record('update', 'Online Exams', "Manual marking on answer #{$answer->id}");
        return response()->json(['status' => 'success', 'message' => get_phrase('Answer marked')]);
    }

    public function finalizeResult($submissionId)
    {
        $submission = OnlineExamSubmission::where('id', (int) $submissionId)
            ->where('school_id', $this->school_id)
            ->with('exam')
            ->firstOrFail();
        $this->authorize('grade', $submission);

        DB::transaction(function () use ($submission) {
            $locked = OnlineExamSubmission::whereKey($submission->id)->lockForUpdate()->firstOrFail();

            $this->recomputeSubmissionScore($locked);

            $locked->update([
                'status' => OnlineExamSubmission::STATUS_FINALIZED,
                'submitted_via' => $locked->submitted_via ?: 'administrator',
            ]);
        });

        AuditLog::record('update', 'Online Exams', "Finalized result for submission #{$submission->id}");
        return redirect()->back()->with('success', get_phrase('Result finalized'));
    }

    private function submitBySubmission(OnlineExamSubmission $submission, string $submittedVia)
    {
        $this->authorize('submit', $submission);

        $locked = DB::transaction(function () use ($submission, $submittedVia) {
            $locked = OnlineExamSubmission::whereKey($submission->id)
                ->lockForUpdate()
                ->with('exam')
                ->firstOrFail();

            if (!empty($locked->submitted_at) || $locked->status !== OnlineExamSubmission::STATUS_IN_PROGRESS) {
                abort(422, 'Duplicate submission is not allowed.');
            }

            $now = now();
            if ($locked->expires_at && $now->gte($locked->expires_at)) {
                $locked->timeout_at = $locked->timeout_at ?: $now;
                $submittedVia = 'timeout';
            }

            $answers = OnlineExamAnswer::where('submission_id', $locked->id)
                ->with('question')
                ->get();

            $objectiveScore = 0.0;
            $manualScore = 0.0;
            $requiresManual = false;

            foreach ($answers as $answer) {
                $question = $answer->question;
                if (!$question) {
                    continue;
                }

                $isObjective = in_array($question->normalized_type, ['multiple_choice', 'true_false'], true);
                if ($isObjective) {
                    $isCorrect = $this->isObjectiveAnswerCorrect($question, $answer);
                    $awarded = $isCorrect ? (float) $question->marks : 0.0;
                    $objectiveScore += $awarded;
                    $answer->update([
                        'is_correct' => $isCorrect,
                        'awarded_marks' => $awarded,
                    ]);
                    continue;
                }

                $requiresManual = true;
                $manualScore += (float) ($answer->awarded_marks ?? 0);
            }

            $score = $objectiveScore + $manualScore;
            $passed = $score >= (float) ($locked->exam->pass_mark ?? 0);
            $nextStatus = $requiresManual
                ? OnlineExamSubmission::STATUS_PENDING_MANUAL
                : OnlineExamSubmission::STATUS_FINALIZED;

            $locked->update([
                'objective_score' => $objectiveScore,
                'manual_score' => $manualScore,
                'score' => $score,
                'passed' => $passed,
                'submitted_at' => $now,
                'submitted_via' => $submittedVia,
                'status' => $nextStatus,
                'last_activity_at' => $now,
                'timeout_at' => $submittedVia === 'timeout' ? ($locked->timeout_at ?: $now) : $locked->timeout_at,
            ]);

            return $locked;
        });

        $action = $submittedVia === 'timeout' ? 'timeout' : 'submit';
        AuditLog::record($action, 'Online Exams', "Submission #{$locked->id} completed via {$submittedVia}. Score: {$locked->score}");

        return redirect()->route('student.online_exam.result', $locked->id);
    }

    private function recomputeSubmissionScore(OnlineExamSubmission $submission): void
    {
        $submission->loadMissing(['exam', 'answerRows.question']);

        $objective = 0.0;
        $manual = 0.0;
        $needsManual = false;

        foreach ($submission->answerRows as $answer) {
            $question = $answer->question;
            if (!$question) {
                continue;
            }

            if (in_array($question->normalized_type, ['multiple_choice', 'true_false'], true)) {
                $objective += (float) ($answer->awarded_marks ?? 0);
            } else {
                $needsManual = true;
                $manual += (float) ($answer->awarded_marks ?? 0);
            }
        }

        $score = $objective + $manual;
        $passed = $score >= (float) ($submission->exam->pass_mark ?? 0);
        $status = $needsManual ? OnlineExamSubmission::STATUS_PENDING_MANUAL : OnlineExamSubmission::STATUS_FINALIZED;

        $submission->update([
            'objective_score' => $objective,
            'manual_score' => $manual,
            'score' => $score,
            'passed' => $passed,
            'status' => $status,
        ]);
    }

    private function isObjectiveAnswerCorrect(OnlineExamQuestion $question, OnlineExamAnswer $answer): bool
    {
        $expected = strtolower(trim((string) $question->correct_ans));
        $given = strtolower(trim((string) $answer->selected_option));
        return $expected !== '' && $expected === $given;
    }

    private function teacherAssignedClassIds(int $teacherId): array
    {
        return TeacherPermission::where('teacher_id', $teacherId)
            ->where('school_id', $this->school_id)
            ->pluck('class_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function teacherAssignableSubjects(int $teacherId, array $assignedClassIds)
    {
        if (empty($assignedClassIds)) {
            return collect();
        }

        return Subject::where('school_id', $this->school_id)
            ->whereIn('class_id', $assignedClassIds)
            ->orderBy('name')
            ->get();
    }

    private function teacherAssignableClasses(array $assignedClassIds)
    {
        if (empty($assignedClassIds)) {
            return collect();
        }

        return Classes::where('school_id', $this->school_id)
            ->whereIn('id', $assignedClassIds)
            ->orderBy('name')
            ->get();
    }

    private function applyLifecycleFilter($query, string $lifecycleState): void
    {
        $now = now();
        if ($lifecycleState === 'active') {
            $query->where('workflow_state', 'published')
                ->where(function ($q) use ($now) {
                    $q->whereNull('start_datetime')->orWhere('start_datetime', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('end_datetime')->orWhere('end_datetime', '>=', $now);
                });
            return;
        }

        if ($lifecycleState === 'completed') {
            $query->where('workflow_state', 'published')
                ->whereNotNull('end_datetime')
                ->where('end_datetime', '<', $now);
            return;
        }

        if ($lifecycleState === 'cancelled') {
            $query->where('workflow_state', 'cancelled');
        }
    }

    private function applyTabFilter($query, string $tab): void
    {
        $now = now();
        if ($tab === 'drafts') {
            $query->where('workflow_state', 'draft');
            return;
        }

        if ($tab === 'pending_review') {
            $query->where('workflow_state', 'pending_review');
            return;
        }

        if ($tab === 'published') {
            $query->where('workflow_state', 'published');
            return;
        }

        if ($tab === 'active') {
            $query->where('workflow_state', 'published')
                ->where(function ($q) use ($now) {
                    $q->whereNull('start_datetime')->orWhere('start_datetime', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('end_datetime')->orWhere('end_datetime', '>=', $now);
                });
            return;
        }

        if ($tab === 'completed') {
            $query->where('workflow_state', 'published')
                ->whereNotNull('end_datetime')
                ->where('end_datetime', '<', $now);
            return;
        }

        if ($tab === 'cancelled') {
            $query->where('workflow_state', 'cancelled');
        }
    }

    private function findExamOrFail(int $examId): OnlineExam
    {
        return OnlineExam::forSchool($this->school_id)->findOrFail($examId);
    }

    private function findStudentExamOrFail(int $examId): OnlineExam
    {
        $user = Auth::user();
        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('school_id', $this->school_id)
            ->first();

        return OnlineExam::visibleToStudent($this->school_id, $enrollment?->class_id)
            ->findOrFail($examId);
    }

    private function findStudentSubmissionOrFail(int $submissionId): OnlineExamSubmission
    {
        return OnlineExamSubmission::where('id', $submissionId)
            ->where('school_id', $this->school_id)
            ->where('student_id', Auth::id())
            ->with('exam')
            ->firstOrFail();
    }
}
