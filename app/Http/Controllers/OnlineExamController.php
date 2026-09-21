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
use App\Models\OnlineExamUserNotification;
use App\Models\QuestionBank;
use App\Models\QuestionTopic;
use App\Models\QuestionTag;
use App\Models\Programme;
use App\Models\Session;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherPermission;
use App\Models\TeacherProgrammeAssignment;
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
use App\Support\OnlineExams\QuestionContract;
use App\Support\OnlineExams\OnlineExamPortalNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

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
            ->with(['subject', 'submissions.student', 'submissions.answerRows', 'submissions.proctoringEvents', 'submissions.exam.questions'])
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
        $programmes = Programme::where('school_id', $this->school_id)->where('is_active', 1)->orderBy('name')->get();
        $sessions = $this->academicSessionsForSelection();
        return view('admin.online_exam.modal', compact('exam', 'subjects', 'classes', 'programmes', 'sessions'));
    }

    public function create()
    {
        $this->authorize('create', OnlineExam::class);

        $subjects = Subject::where('school_id', $this->school_id)->orderBy('name')->get();
        $classes  = Classes::where('school_id', $this->school_id)->orderBy('name')->get();
        $programmes = Programme::where('school_id', $this->school_id)->where('is_active', 1)->orderBy('name')->get();
        $sessions = $this->academicSessionsForSelection();

        return view('admin.online_exam.modal', [
            'exam' => null,
            'subjects' => $subjects,
            'classes' => $classes,
            'programmes' => $programmes,
            'sessions' => $sessions,
        ]);
    }

    public function show($id)
    {
        $exam = $this->findExamOrFail((int) $id);
        $this->authorize('view', $exam);

        $exam->load(['subject', 'classRoom', 'questions', 'submissions.student']);
        $readinessErrors = $exam->publicationReadinessErrors();
        return view('admin.online_exam.show', compact('exam', 'readinessErrors'));
    }

    public function edit($id)
    {
        $exam = $this->findExamOrFail((int) $id);
        $this->authorize('update', $exam);

        $subjects = Subject::where('school_id', $this->school_id)->orderBy('name')->get();
        $classes  = Classes::where('school_id', $this->school_id)->orderBy('name')->get();
        $programmes = Programme::where('school_id', $this->school_id)->where('is_active', 1)->orderBy('name')->get();
        $sessions = $this->academicSessionsForSelection();

        return view('admin.online_exam.modal', compact('exam', 'subjects', 'classes', 'programmes', 'sessions'));
    }

    public function store(StoreOnlineExamRequest $request)
    {
        $this->authorize('create', OnlineExam::class);

        $validated = $request->validated();
        if (($validated['workflow_state'] ?? 'draft') === 'published') {
            abort(422, 'Add and validate questions before publishing an exam.');
        }
        $payload = [
            'title' => $validated['title'],
            'instructions' => $validated['instructions'] ?? null,
            'subject_id' => $validated['subject_id'] ?? null,
            'class_id' => $validated['class_id'] ?? null,
            'programme_id' => $validated['programme_id'] ?? null,
            'session_id' => $validated['session_id'] ?? null,
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
        if (!Schema::hasColumn('online_exams', 'programme_id')) unset($payload['programme_id']);
        if (!Schema::hasColumn('online_exams', 'session_id')) unset($payload['session_id']);

        $exam = DB::transaction(fn() => OnlineExam::create($payload));
        AuditLog::record('create', 'Online Exams', "Created exam: {$exam->title}");
        return redirect()->route('admin.online_exams.index')->with('success', get_phrase('Exam created'));
    }

    public function update(UpdateOnlineExamRequest $request, $id)
    {
        $exam = $this->findExamOrFail((int) $id);
        $this->authorize('update', $exam);

        $validated = $request->validated();
        DB::transaction(function () use ($exam, $validated) {
            $payload = [
                'title' => $validated['title'],
                'instructions' => $validated['instructions'] ?? null,
                'subject_id' => $validated['subject_id'] ?? null,
                'class_id' => $validated['class_id'] ?? null,
                'programme_id' => $validated['programme_id'] ?? null,
                'session_id' => $validated['session_id'] ?? null,
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
            ];
            if (!Schema::hasColumn('online_exams', 'programme_id')) unset($payload['programme_id']);
            if (!Schema::hasColumn('online_exams', 'session_id')) unset($payload['session_id']);
            $exam->update($payload);
            if (($validated['workflow_state'] ?? $exam->workflow_state) === 'published') {
                $exam->refresh()->load('questions');
                $errors = $exam->publicationReadinessErrors();
                if (!empty($errors)) {
                    abort(422, implode(' ', $errors));
                }
            }
        });

        AuditLog::record('update', 'Online Exams', "Updated exam: {$exam->title}");
        return redirect()->back()->with('success', get_phrase('Exam updated'));
    }

    public function publish(Request $request, $id)
    {
        $exam = $this->findExamOrFail((int) $id);
        $this->authorize('publish', $exam);
        $wasPublished = (bool) $exam->is_published;

        $readinessErrors = $exam->fresh()->publicationReadinessErrors();
        if (!empty($readinessErrors)) {
            return $this->publicationReadinessFailure($request, $readinessErrors);
        }

        $this->publishExam($exam);
        $exam->refresh();

        AuditLog::record('update', 'Online Exams', (($exam->is_published ? 'Published' : 'Unpublished') . " exam: {$exam->title}"));

        if (!$wasPublished && $exam->is_published) {
            OnlineExamPortalNotifier::teacher('exam_approved', 'Exam Approved', 'Your exam "' . $exam->title . '" was approved and published.', $exam, Auth::id(), 'exam-approved:' . $exam->id);
            OnlineExamPortalNotifier::eligibleStudents($exam, 'exam_published', 'Exam Available', 'A new exam, "' . $exam->title . '", is now available.', Auth::id(), 'exam-published:' . $exam->id);
            \App\Support\OnlineExams\OnlineExamAnnouncementNotifier::examPublished($exam->fresh());
        }
        return redirect()->back()->with('success', get_phrase('Exam status updated'));
    }

    public function unpublish($id)
    {
        $exam = $this->findExamOrFail((int) $id);
        $this->authorize('unpublish', $exam);

        if ($exam->submissions()->whereIn('status', [OnlineExamSubmission::STATUS_IN_PROGRESS, OnlineExamSubmission::STATUS_SUBMITTED, OnlineExamSubmission::STATUS_PENDING_MANUAL])->exists()) {
            return redirect()->back()->withErrors(['exam' => get_phrase('Cannot unpublish an exam with active or pending attempts.')]);
        }

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

        if ($exam->submissions()->where('status', OnlineExamSubmission::STATUS_IN_PROGRESS)->exists()) {
            return redirect()->back()->withErrors(['exam' => get_phrase('Cannot cancel an exam while candidates are active.')]);
        }

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
        $questionMarksTotal = (int) $questions->sum('marks');
        return view('admin.online_exam.questions', compact('exam', 'questions', 'bank', 'questionMarksTotal'));
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
                'question_schema_version' => $validated['question_schema_version'] ?? null,
                'question_config' => $validated['question_config'] ?? null,
                'marking_config' => $validated['marking_config'] ?? null,
                'marks' => $validated['marks'],
                'sort_order' => $nextSort,
            ]);
        });

        AuditLog::record('create', 'Online Exams', "Created exam question in exam #{$exam->id}");
        return redirect()->back()->with('success', get_phrase('Question added'));
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
                'question_schema_version' => $validated['question_schema_version'] ?? null,
                'question_config' => $validated['question_config'] ?? null,
                'marking_config' => $validated['marking_config'] ?? null,
                'marks' => $validated['marks'],
            ]);
        });

        AuditLog::record('update', 'Online Exams', "Updated exam question #{$question->id}");
        return redirect()->back()->with('success', get_phrase('Question updated'));
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

    private function assertQuestionMetadataAdmin(): void
    {
        $user = Auth::user();
        $permissions = app(OnlineExamPermissionService::class);
        abort_unless($user && $permissions->has($user, 'manage_exam_questions') && $permissions->has($user, 'edit_all_online_exams'), 403);
    }

    public function questionMetadata()
    {
        $this->assertQuestionMetadataAdmin();
        $subjects = Subject::where('school_id', $this->school_id)->orderBy('name')->get();
        $topics = QuestionTopic::where('school_id', $this->school_id)->with('subject')->orderBy('name')->get();
        $tags = QuestionTag::where('school_id', $this->school_id)->orderBy('name')->get();
        return view('admin.online_exam.question_metadata', compact('subjects', 'topics', 'tags'));
    }

    public function storeQuestionTopic(Request $request)
    {
        $this->assertQuestionMetadataAdmin();
        $data = $request->validate(['subject_id' => ['required', Rule::exists('subjects', 'id')->where(fn ($q) => $q->where('school_id', $this->school_id))], 'name' => ['required', 'string', 'max:150']]);
        $name = trim($data['name']);
        abort_if($name === '', 422, 'Topic name is required.');
        abort_if(QuestionTopic::where('school_id', $this->school_id)->where('subject_id', $data['subject_id'])->whereNull('parent_id')->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists(), 422, 'That Topic already exists for this Course.');
        QuestionTopic::create(['school_id' => $this->school_id, 'subject_id' => $data['subject_id'], 'name' => $name, 'is_active' => true, 'created_by' => Auth::id()]);
        return redirect()->back()->with('success', get_phrase('Topic created'));
    }

    public function storeQuestionSubtopic(Request $request)
    {
        $this->assertQuestionMetadataAdmin();
        $data = $request->validate(['subject_id' => ['required', Rule::exists('subjects', 'id')->where(fn ($q) => $q->where('school_id', $this->school_id))], 'parent_id' => ['required', 'integer'], 'name' => ['required', 'string', 'max:150']]);
        $parent = QuestionTopic::where('school_id', $this->school_id)->where('subject_id', $data['subject_id'])->whereNull('parent_id')->findOrFail($data['parent_id']);
        $name = trim($data['name']); abort_if($name === '', 422, 'Subtopic name is required.');
        abort_if(QuestionTopic::where('school_id', $this->school_id)->where('subject_id', $parent->subject_id)->where('parent_id', $parent->id)->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists(), 422, 'That Subtopic already exists under this Topic.');
        QuestionTopic::create(['school_id' => $this->school_id, 'subject_id' => $parent->subject_id, 'parent_id' => $parent->id, 'name' => $name, 'is_active' => true, 'created_by' => Auth::id()]);
        return redirect()->back()->with('success', get_phrase('Subtopic created'));
    }

    public function updateQuestionTopic(Request $request, $id)
    {
        $this->assertQuestionMetadataAdmin(); $topic = QuestionTopic::where('school_id', $this->school_id)->findOrFail((int) $id);
        $data = $request->validate(['name' => ['required', 'string', 'max:150'], 'subject_id' => ['sometimes', 'integer']]);
        $name = trim($data['name']); $subjectId = (int) ($data['subject_id'] ?? $topic->subject_id);
        abort_unless(Subject::where('school_id', $this->school_id)->whereKey($subjectId)->exists(), 422);
        abort_if($topic->parent_id !== null && !QuestionTopic::whereKey($topic->parent_id)->where('subject_id', $subjectId)->exists(), 422, 'Subtopic parent and Course must remain consistent.');
        $hasRefs = QuestionBank::where('school_id', $this->school_id)->where(fn ($q) => $q->where('topic_id', $topic->id)->orWhere('subtopic_id', $topic->id))->exists();
        abort_if($hasRefs && $subjectId !== (int) $topic->subject_id, 422, 'A referenced taxonomy item cannot be moved to another Course.');
        abort_if(QuestionTopic::where('school_id', $this->school_id)->where('subject_id', $subjectId)->where('parent_id', $topic->parent_id)->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->where('id', '<>', $topic->id)->exists(), 422, 'That taxonomy name already exists here.');
        $topic->update(['name' => $name, 'subject_id' => $subjectId]); return redirect()->back()->with('success', get_phrase('Topic updated'));
    }

    public function toggleQuestionTopic($id)
    {
        $this->assertQuestionMetadataAdmin(); $topic = QuestionTopic::where('school_id', $this->school_id)->findOrFail((int) $id); $topic->update(['is_active' => !$topic->is_active]); return redirect()->back()->with('success', get_phrase('Topic status updated'));
    }

    public function storeQuestionTag(Request $request)
    {
        $this->assertQuestionMetadataAdmin(); $data = $request->validate(['name' => ['required', 'string', 'max:100']]); $name = trim($data['name']); $normalized = mb_strtolower(preg_replace('/\s+/', ' ', $name));
        abort_if($normalized === '', 422, 'Tag name is required.'); abort_if(QuestionTag::where('school_id', $this->school_id)->where('normalized_name', $normalized)->exists(), 422, 'That Tag already exists in this school.');
        QuestionTag::create(['school_id' => $this->school_id, 'name' => $name, 'normalized_name' => $normalized, 'is_active' => true, 'created_by' => Auth::id()]); return redirect()->back()->with('success', get_phrase('Tag created'));
    }

    public function updateQuestionTag(Request $request, $id)
    {
        $this->assertQuestionMetadataAdmin(); $tag = QuestionTag::where('school_id', $this->school_id)->findOrFail((int) $id); $data = $request->validate(['name' => ['required', 'string', 'max:100']]); $name = trim($data['name']); $normalized = mb_strtolower(preg_replace('/\s+/', ' ', $name));
        abort_if(QuestionTag::where('school_id', $this->school_id)->where('normalized_name', $normalized)->where('id', '<>', $tag->id)->exists(), 422, 'That Tag already exists in this school.'); $tag->update(['name' => $name, 'normalized_name' => $normalized]); return redirect()->back()->with('success', get_phrase('Tag updated'));
    }

    public function toggleQuestionTag($id)
    {
        $this->assertQuestionMetadataAdmin(); $tag = QuestionTag::where('school_id', $this->school_id)->findOrFail((int) $id); $tag->update(['is_active' => !$tag->is_active]); return redirect()->back()->with('success', get_phrase('Tag status updated'));
    }

    public function questionBank(Request $request)
    {
        $this->authorize('viewAny', OnlineExam::class);

        $search   = $request->search ?? '';
        $subjectId = (int) $request->input('subject_id', 0); $programmeId = (int) $request->input('programme_id', 0); $sessionId = (int) $request->input('session_id', 0); $topicId = (int) $request->input('topic_id', 0); $subtopicId = (int) $request->input('subtopic_id', 0); $tagId = (int) $request->input('tag_id', 0); $type = (string) $request->input('type', ''); $difficulty = (string) $request->input('difficulty', ''); $status = $request->input('status', '');
        $questions = QuestionBank::where('school_id', $this->school_id)
            ->when($search, fn($q) => $q->where('question', 'like', "%$search%"))
            ->when($subjectId, fn($q) => $q->where('subject_id', $subjectId))
            ->when($programmeId, fn($q) => $q->where('programme_id', $programmeId))
            ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->when($topicId, fn($q) => $q->where('topic_id', $topicId))
            ->when($subtopicId, fn($q) => $q->where('subtopic_id', $subtopicId))
            ->when($tagId, fn($q) => $q->whereHas('tags', fn($t) => $t->where('question_tags.id', $tagId)))
            ->when($type !== '', fn($q) => $q->where('type', $type))
            ->when($difficulty !== '', fn($q) => $q->where('difficulty', $difficulty))
            ->when($status !== '', fn($q) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(20);
        $subjects = Subject::where('school_id', $this->school_id)->orderBy('name')->get();
        $programmes = Programme::where('school_id',$this->school_id)->where('is_active',1)->orderBy('name')->get(); $sessions = Session::where('school_id',$this->school_id)->orderByDesc('id')->get();
        $topics = QuestionTopic::where('school_id',$this->school_id)->where('is_active',1)->whereNull('parent_id')->orderBy('name')->get(); $subtopics = QuestionTopic::where('school_id',$this->school_id)->where('is_active',1)->whereNotNull('parent_id')->orderBy('name')->get(); $tags = QuestionTag::where('school_id',$this->school_id)->where('is_active',1)->orderBy('name')->get();
        return view('admin.online_exam.question_bank', compact('questions', 'subjects', 'search', 'subjectId', 'programmes', 'sessions', 'topics', 'subtopics', 'tags', 'programmeId', 'sessionId', 'topicId', 'subtopicId', 'tagId', 'type', 'difficulty', 'status'));
    }

    public function bankModal(Request $request)
    {
        $this->authorize('create', OnlineExam::class);
        $subjects = Subject::where('school_id', $this->school_id)->orderBy('name')->get();
        $programmes = Programme::where('school_id',$this->school_id)->where('is_active',1)->orderBy('name')->get(); $sessions = Session::where('school_id',$this->school_id)->orderByDesc('id')->get();
        $topics = QuestionTopic::where('school_id',$this->school_id)->where('is_active',1)->whereNull('parent_id')->orderBy('name')->get();
        $subtopics = QuestionTopic::where('school_id',$this->school_id)->where('is_active',1)->whereNotNull('parent_id')->orderBy('name')->get();
        $tags = QuestionTag::where('school_id',$this->school_id)->where('is_active',1)->orderBy('name')->get();
        $question = $request->filled('id')
            ? QuestionBank::where('school_id', $this->school_id)->findOrFail((int) $request->input('id'))
            : null;
        return view('admin.online_exam.bank_modal', compact('subjects', 'question', 'programmes', 'sessions', 'topics', 'subtopics', 'tags'));
    }

    public function destroyBankQuestion($id)
    {
        $question = QuestionBank::where('school_id', $this->school_id)->findOrFail((int) $id);
        abort_unless((int) $question->school_id === (int) $this->school_id, 404);
        $user = Auth::user();
        $privileged = app(OnlineExamPermissionService::class)->has($user, 'manage_exam_questions')
            && app(OnlineExamPermissionService::class)->has($user, 'edit_all_online_exams');
        abort_unless($privileged || (int) ($question->created_by ?? $question->creator_id) === (int) $user->id, 403);
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
        $this->normalizeBankStructuredOptions($request);

        $validated = $request->validate([
            'subject_id'  => ['nullable', \Illuminate\Validation\Rule::exists('subjects', 'id')->where(fn ($q) => $q->where('school_id', $this->school_id))],
            'programme_id' => ['nullable', Rule::exists('programmes','id')->where(fn($q)=>$q->where('school_id',$this->school_id))],
            'session_id' => ['nullable', Rule::exists('sessions','id')->where(fn($q)=>$q->where('school_id',$this->school_id))],
            'topic_id' => ['nullable','integer'], 'subtopic_id' => ['nullable','integer'], 'tag_ids' => ['nullable','array','max:20'], 'tag_ids.*' => ['integer'], 'status' => ['nullable','in:draft,active,retired,archived'],
            'question'    => 'required|string',
            'type'        => 'required|in:mcq,true_false,short,essay,multiple_select,numeric,matching,ordering',
            'option_a'    => 'nullable|string',
            'option_b'    => 'nullable|string',
            'option_c'    => 'nullable|string',
            'option_d'    => 'nullable|string',
            'correct_ans' => 'nullable|string|max:255',
            'correct_answer_tf' => 'nullable|string|in:true,false',
            'marks'       => 'required|integer|min:1|max:127',
            'difficulty'  => 'required|in:easy,medium,hard',
            'structured_options' => 'nullable|array|max:8',
            'structured_options.*.id' => 'required|string|max:32|regex:/^[a-z][a-z0-9_-]*$/i',
            'structured_options.*.label' => 'required|string|max:1000',
            'correct_option_ids' => 'nullable|array|max:8',
            'correct_option_ids.*' => 'required|string|max:32',
            'numeric_target' => 'nullable', 'numeric_tolerance' => 'nullable',
            'structured_blanks' => 'nullable|array|max:16',
            'structured_blanks.*.id' => 'required|string|max:32|regex:/^[a-z][a-z0-9_-]*$/i',
            'structured_blanks.*.accepted_answers' => 'required|array|max:8',
            'structured_blanks.*.accepted_answers.*' => 'required|string|max:255',
            'case_sensitive' => 'nullable|boolean', 'trim_whitespace' => 'nullable|boolean',
            'structured_pairs' => 'nullable|array|max:16', 'structured_pairs.*.left_id'=>'required|string|max:32', 'structured_pairs.*.left_text'=>'required|string|max:1000', 'structured_pairs.*.right_id'=>'required|string|max:32', 'structured_pairs.*.right_text'=>'required|string|max:1000',
            'structured_order_items' => 'nullable|array|max:16', 'structured_order_items.*.id'=>'required|string|max:32', 'structured_order_items.*.text'=>'required|string|max:1000',
        ]);
        if (in_array($validated['type'], ['multiple_select', 'numeric','matching','ordering'], true) || ($validated['type'] === 'fill_blank' && !empty($validated['structured_blanks']))) {
            $structured = $this->structuredBankFields($validated);
            $validated = array_merge($validated, $structured);
        } else {
            $validated['correct_ans'] = $this->canonicalizeBankAnswer($validated);
        }
        unset($validated['correct_answer_tf']);
        $this->validateQuestionBankAcademicMetadata($validated);

        DB::transaction(function () use ($validated) {
            $question = QuestionBank::create([
                'school_id' => $this->school_id,
                'subject_id' => $validated['subject_id'] ?? null,
                ...$this->questionBankMetadataPayload($validated),
                'question' => $validated['question'],
                'type' => $validated['type'],
                'option_a' => $validated['option_a'] ?? null,
                'option_b' => $validated['option_b'] ?? null,
                'option_c' => $validated['option_c'] ?? null,
                'option_d' => $validated['option_d'] ?? null,
                'correct_ans' => $validated['correct_ans'] ?? null,
                'question_schema_version' => $validated['question_schema_version'] ?? null,
                'question_config' => $validated['question_config'] ?? null,
                'marking_config' => $validated['marking_config'] ?? null,
                'marks' => $validated['marks'],
                'difficulty' => $validated['difficulty'],
                'created_by' => Auth::id(),
            ]);
            if (Schema::hasTable('question_bank_tag') && !empty($validated['tag_ids'])) $question->tags()->sync($validated['tag_ids']);
        });

        return redirect()->back()->with('success', get_phrase('Question added to bank'));
    }

    public function updateBankQuestion(Request $request, $id)
    {
        $question = QuestionBank::where('school_id', $this->school_id)->findOrFail((int) $id);
        $user = Auth::user();
        $privileged = app(OnlineExamPermissionService::class)->has($user, 'manage_exam_questions')
            && app(OnlineExamPermissionService::class)->has($user, 'edit_all_online_exams');
        abort_unless($privileged || (int) ($question->created_by ?? 0) === (int) $user->id, 403);
        $this->normalizeBankStructuredOptions($request);
        $validated = $request->validate([
            'subject_id'  => ['nullable', Rule::exists('subjects', 'id')->where(fn ($q) => $q->where('school_id', $this->school_id))],
            'programme_id' => ['nullable', Rule::exists('programmes','id')->where(fn($q)=>$q->where('school_id',$this->school_id))], 'session_id' => ['nullable', Rule::exists('sessions','id')->where(fn($q)=>$q->where('school_id',$this->school_id))],
            'topic_id' => ['nullable','integer'], 'subtopic_id' => ['nullable','integer'], 'tag_ids' => ['nullable','array','max:20'], 'tag_ids.*' => ['integer'], 'status' => ['nullable','in:draft,active,retired,archived'],
            'question'    => ['required', 'string'],
            'type'        => ['required', 'in:mcq,true_false,short,essay,multiple_select,numeric,matching,ordering'],
            'option_a'    => ['nullable', 'string'], 'option_b' => ['nullable', 'string'],
            'option_c'    => ['nullable', 'string'], 'option_d' => ['nullable', 'string'],
            'correct_ans' => ['nullable', 'string', 'max:255'],
            'correct_answer_tf' => ['nullable', 'string', 'in:true,false'],
            'marks'       => ['required', 'integer', 'min:1', 'max:127'],
            'difficulty'  => ['required', 'in:easy,medium,hard'],
            'structured_options' => ['nullable', 'array', 'max:8'],
            'structured_options.*.id' => ['required', 'string', 'max:32', 'regex:/^[a-z][a-z0-9_-]*$/i'],
            'structured_options.*.label' => ['required', 'string', 'max:1000'],
            'correct_option_ids' => ['nullable', 'array', 'max:8'],
            'correct_option_ids.*' => ['required', 'string', 'max:32'],
            'numeric_target' => ['nullable'], 'numeric_tolerance' => ['nullable'],
            'structured_blanks' => ['nullable', 'array', 'max:16'],
            'structured_blanks.*.id' => ['required', 'string', 'max:32', 'regex:/^[a-z][a-z0-9_-]*$/i'],
            'structured_blanks.*.accepted_answers' => ['required', 'array', 'max:8'],
            'structured_blanks.*.accepted_answers.*' => ['required', 'string', 'max:255'],
            'case_sensitive' => ['nullable', 'boolean'], 'trim_whitespace' => ['nullable', 'boolean'],
            'structured_pairs' => ['nullable','array','max:16'], 'structured_pairs.*.left_id'=>['required','string','max:32'], 'structured_pairs.*.left_text'=>['required','string','max:1000'], 'structured_pairs.*.right_id'=>['required','string','max:32'], 'structured_pairs.*.right_text'=>['required','string','max:1000'],
            'structured_order_items' => ['nullable','array','max:16'], 'structured_order_items.*.id'=>['required','string','max:32'], 'structured_order_items.*.text'=>['required','string','max:1000'],
        ]);
        if (in_array($validated['type'], ['multiple_select', 'numeric','matching','ordering'], true) || ($validated['type'] === 'fill_blank' && !empty($validated['structured_blanks']))) {
            $validated = array_merge($validated, $this->structuredBankFields($validated));
        } else {
            $validated['correct_ans'] = $this->canonicalizeBankAnswer($validated);
        }
        unset($validated['correct_answer_tf']);
        $this->validateQuestionBankAcademicMetadata($validated);
        $updateData = $validated + ['subject_id' => $validated['subject_id'] ?? null];
        if (!Schema::hasColumn('question_banks', 'programme_id')) foreach (['programme_id','session_id','topic_id','subtopic_id','tag_ids','status'] as $key) unset($updateData[$key]);
        unset($updateData['tag_ids']);
        $question->update($updateData);
        if (Schema::hasTable('question_bank_tag') && array_key_exists('tag_ids', $validated)) $question->tags()->sync($validated['tag_ids'] ?? []);
        return redirect()->route('admin.question_bank.index')->with('success', get_phrase('Question bank item updated'));
    }

    // ── Teacher: online exams ─────────────────────────────────────────────

    public function adminImportQuestion(Request $request, OnlineExam $exam)
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
        $bankQuestions = QuestionBank::where('school_id', $this->school_id)
            ->whereIn('id', $validated['question_bank_ids'])->get();
        $imported = 0;
        DB::transaction(function () use ($exam, $bankQuestions, &$imported) {
            $nextSort = ((int) OnlineExamQuestion::forExam($exam->id)->max('sort_order')) + 1;
            foreach ($bankQuestions as $bankQuestion) {
                OnlineExamQuestion::create([
                    'online_exam_id' => $exam->id, 'question_bank_id' => $bankQuestion->id,
                    'question' => $bankQuestion->question, 'type' => $this->snapshotStorageType($bankQuestion),
                    'option_a' => $bankQuestion->option_a, 'option_b' => $bankQuestion->option_b,
                    'option_c' => $bankQuestion->option_c, 'option_d' => $bankQuestion->option_d,
                'correct_ans' => \App\Support\OnlineExams\AnswerKey::normalize($bankQuestion->normalized_type, $bankQuestion->correct_ans, ['a' => $bankQuestion->option_a, 'b' => $bankQuestion->option_b, 'c' => $bankQuestion->option_c, 'd' => $bankQuestion->option_d]),
                    'question_schema_version' => $bankQuestion->question_schema_version,
                    'question_config' => $bankQuestion->question_config,
                    'marking_config' => $bankQuestion->marking_config,
                    'marks' => $bankQuestion->marks,
                    'sort_order' => $nextSort++,
                ]);
                $imported++;
            }
        });
        AuditLog::record('create', 'Online Exams', "Admin imported {$imported} question(s) from bank into exam #{$exam->id}");
        return redirect()->back()->with('success', get_phrase('Questions imported from bank.'));
    }

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
        $programmeId = (int) $request->input('programme_id', 0); $sessionId = (int) $request->input('session_id', 0); $status = $request->input('status', '');
        $programmeId = (int) $request->input('programme_id', 0); $sessionId = (int) $request->input('session_id', 0); $status = $request->input('status', '');
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
        if ($programmeId > 0) $query->where('programme_id', $programmeId);
        if ($sessionId > 0) $query->where('session_id', $sessionId);
        if ($status !== '') $query->where('status', $status);
        if ($programmeId > 0) $query->where('programme_id', $programmeId);
        if ($sessionId > 0) $query->where('session_id', $sessionId);
        if ($status !== '') $query->where('status', $status);

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
        $programmes = Programme::where('school_id',$this->school_id)->where('is_active',1)->whereIn('id', TeacherProgrammeAssignment::where('school_id',$this->school_id)->where('teacher_id',$user->id)->pluck('programme_id'))->orderBy('name')->get();
        $sessions = Session::where('school_id',$this->school_id)->orderByDesc('id')->get();
        $programmes = Programme::where('school_id',$this->school_id)->where('is_active',1)->whereIn('id', TeacherProgrammeAssignment::where('school_id',$this->school_id)->where('teacher_id',$user->id)->pluck('programme_id'))->orderBy('name')->get(); $sessions = Session::where('school_id',$this->school_id)->orderByDesc('id')->get();
        $classes = $this->teacherAssignableClasses($assignedClassIds);
        $sessions = Session::where('school_id', $this->school_id)->orderByDesc('id')->get();
        $topics = QuestionTopic::where('school_id',$this->school_id)->where('is_active',1)->whereNull('parent_id')->orderBy('name')->get(); $subtopics = QuestionTopic::where('school_id',$this->school_id)->where('is_active',1)->whereNotNull('parent_id')->orderBy('name')->get(); $tags = QuestionTag::where('school_id',$this->school_id)->where('is_active',1)->orderBy('name')->get();

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
        $programmes = $this->teacherAssignableProgrammes((int) $user->id);
        $sessions = $this->academicSessionsForSelection();

        return view('teacher.online_exam.create', [
            'exam' => null,
            'subjects' => $subjects,
            'classes' => $classes,
            'sessions' => $sessions,
            'programmes' => $programmes,
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
            'programme_id' => $validated['programme_id'] ?? null,
            'session_id' => $validated['session_id'] ?? null,
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
        if (!Schema::hasColumn('online_exams', 'programme_id')) unset($payload['programme_id']);
        if (!Schema::hasColumn('online_exams', 'session_id')) unset($payload['session_id']);

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
        $programmes = $this->teacherAssignableProgrammes((int) $user->id);
        $sessions = $this->academicSessionsForSelection();

        return view('teacher.online_exam.edit', [
            'exam' => $exam,
            'subjects' => $subjects,
            'classes' => $classes,
            'sessions' => $sessions,
            'programmes' => $programmes,
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
            $payload = [
                'title' => $validated['title'],
                'instructions' => $validated['instructions'] ?? null,
                'subject_id' => $validated['subject_id'],
                'class_id' => $validated['class_id'] ?? null,
                'programme_id' => $validated['programme_id'] ?? null,
                'session_id' => $validated['session_id'] ?? null,
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
            ];
            if (!Schema::hasColumn('online_exams', 'programme_id')) unset($payload['programme_id']);
            if (!Schema::hasColumn('online_exams', 'session_id')) unset($payload['session_id']);
            $exam->update($payload);
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
        OnlineExamPortalNotifier::admins('exam_submitted_for_review', 'Exam Awaiting Review', Auth::user()->name . ' submitted "' . $exam->title . '" for review.', $exam, Auth::id(), 'exam-review:' . $exam->id);
        return redirect()->back()->with('success', get_phrase('Exam submitted for review.'));
    }

    public function markPortalNotificationRead(OnlineExamUserNotification $notification)
    {
        $user = Auth::user();
        abort_unless($user && (int) $notification->school_id === (int) $user->school_id && (int) $notification->user_id === (int) $user->id, 404);

        if (!$notification->read_at) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        return redirect()->to($notification->action_url ?: url()->previous());
    }

    public function teacherPublish(Request $request, OnlineExam $exam)
    {
        abort(403, 'Only an administrator may publish an exam.');
    }

    public function teacherUnpublish(OnlineExam $exam)
    {
        abort(403, 'Only an administrator may change official publication.');
    }

    public function teacherCancel(Request $request, OnlineExam $exam)
    {
        $this->authorize('cancel', $exam);
        abort_unless((int) $exam->school_id === (int) $this->school_id, 404);

        if ($exam->submissions()->where('status', OnlineExamSubmission::STATUS_IN_PROGRESS)->exists()) {
            return redirect()->back()->withErrors(['exam' => get_phrase('Cannot cancel an exam while candidates are active.')]);
        }

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
                'question_schema_version' => $validated['question_schema_version'] ?? null,
                'question_config' => $validated['question_config'] ?? null,
                'marking_config' => $validated['marking_config'] ?? null,
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
                'question_schema_version' => $validated['question_schema_version'] ?? null,
                'question_config' => $validated['question_config'] ?? null,
                'marking_config' => $validated['marking_config'] ?? null,
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
        $programmeId = (int) $request->input('programme_id', 0);
        $sessionId = (int) $request->input('session_id', 0);
        $status = (string) $request->input('status', '');
        $topicId = (int) $request->input('topic_id', 0);
        $subtopicId = (int) $request->input('subtopic_id', 0);
        $tagId = (int) $request->input('tag_id', 0);
        $type = (string) $request->input('type', '');
        $difficulty = (string) $request->input('difficulty', '');

        $query = $this->teacherQuestionBankQuery($user, $assignedClassIds)
            ->with('subject');

        $query->when($search !== '', function ($q) use ($search) {
            $q->where('question', 'like', '%' . $search . '%');
        });

        if ($subjectId > 0) {
            $query->where('subject_id', $subjectId);
        }
        $query->when($programmeId > 0, fn($q) => $q->where('programme_id', $programmeId))
            ->when($sessionId > 0, fn($q) => $q->where('session_id', $sessionId))
            ->when($topicId > 0, fn($q) => $q->where('topic_id', $topicId))
            ->when($subtopicId > 0, fn($q) => $q->where('subtopic_id', $subtopicId))
            ->when($tagId > 0, fn($q) => $q->whereHas('tags', fn($t) => $t->where('question_tags.id', $tagId)))
            ->when($type !== '', fn($q) => $q->where('type', $type))
            ->when($difficulty !== '', fn($q) => $q->where('difficulty', $difficulty))
            ->when($status !== '', fn($q) => $q->where('status', $status));

        $questions = $query->orderByDesc('id')->paginate(20)->appends($request->all());
        $subjects = $this->teacherAssignableSubjects((int) $user->id, $assignedClassIds);
        $programmes = Programme::where('school_id', $this->school_id)->where('is_active', 1)
            ->whereIn('id', TeacherProgrammeAssignment::where('school_id', $this->school_id)->where('teacher_id', $user->id)->pluck('programme_id'))
            ->orderBy('name')->get();
        $sessions = Session::where('school_id', $this->school_id)->orderByDesc('id')->get();
        $topics = QuestionTopic::where('school_id',$this->school_id)->where('is_active',1)->whereNull('parent_id')->orderBy('name')->get();
        $subtopics = QuestionTopic::where('school_id',$this->school_id)->where('is_active',1)->whereNotNull('parent_id')->orderBy('name')->get();
        $tags = QuestionTag::where('school_id',$this->school_id)->where('is_active',1)->orderBy('name')->get();

        return view('teacher.online_exam.question_bank', compact('questions', 'subjects', 'search', 'subjectId', 'programmes', 'sessions', 'programmeId', 'sessionId', 'topicId', 'subtopicId', 'tagId', 'type', 'difficulty', 'status', 'topics', 'subtopics', 'tags'))
            ->with('canCreateBankQuestion', $permissionService->has($user, 'manage_exam_questions'));
    }

    public function teacherStoreBankQuestion(Request $request)
    {
        $user = Auth::user();
        $permissionService = app(OnlineExamPermissionService::class);
        abort_unless($permissionService->has($user, 'manage_exam_questions'), 403);
        $this->normalizeBankStructuredOptions($request);

        $validated = $request->validate([
            'subject_id'  => ['required', 'integer', Rule::exists('subjects', 'id')->where(fn ($q) => $q->where('school_id', $this->school_id))],
            'programme_id' => ['nullable', Rule::exists('programmes','id')->where(fn($q)=>$q->where('school_id',$this->school_id))], 'session_id' => ['nullable', Rule::exists('sessions','id')->where(fn($q)=>$q->where('school_id',$this->school_id))],
            'topic_id' => ['nullable','integer'], 'subtopic_id' => ['nullable','integer'], 'tag_ids' => ['nullable','array','max:20'], 'tag_ids.*' => ['integer'], 'status' => ['nullable','in:draft,active,retired,archived'],
            'question'    => ['required', 'string'],
            'type'        => ['required', 'in:mcq,true_false,short,essay,multiple_select,numeric,matching,ordering'],
            'option_a'    => ['nullable', 'string'],
            'option_b'    => ['nullable', 'string'],
            'option_c'    => ['nullable', 'string'],
            'option_d'    => ['nullable', 'string'],
            'correct_ans' => ['nullable', 'string', 'max:255'],
            'correct_answer_tf' => ['nullable', 'string', 'in:true,false'],
            'marks'       => ['required', 'integer', 'min:1', 'max:127'],
            'difficulty'  => ['required', 'in:easy,medium,hard'],
            'structured_options' => ['nullable', 'array', 'max:8'],
            'structured_options.*.id' => ['required', 'string', 'max:32', 'regex:/^[a-z][a-z0-9_-]*$/i'],
            'structured_options.*.label' => ['required', 'string', 'max:1000'],
            'correct_option_ids' => ['nullable', 'array', 'max:8'],
            'correct_option_ids.*' => ['required', 'string', 'max:32'],
            'numeric_target' => ['nullable'], 'numeric_tolerance' => ['nullable'],
            'structured_blanks' => ['nullable', 'array', 'max:16'],
            'structured_blanks.*.id' => ['required', 'string', 'max:32', 'regex:/^[a-z][a-z0-9_-]*$/i'],
            'structured_blanks.*.accepted_answers' => ['required', 'array', 'max:8'],
            'structured_blanks.*.accepted_answers.*' => ['required', 'string', 'max:255'],
            'case_sensitive' => ['nullable', 'boolean'], 'trim_whitespace' => ['nullable', 'boolean'],
            'structured_pairs' => ['nullable','array','max:16'], 'structured_pairs.*.left_id'=>['required','string','max:32'], 'structured_pairs.*.left_text'=>['required','string','max:1000'], 'structured_pairs.*.right_id'=>['required','string','max:32'], 'structured_pairs.*.right_text'=>['required','string','max:1000'],
            'structured_order_items' => ['nullable','array','max:16'], 'structured_order_items.*.id'=>['required','string','max:32'], 'structured_order_items.*.text'=>['required','string','max:1000'],
        ]);
        if (in_array($validated['type'], ['multiple_select', 'numeric','matching','ordering'], true) || ($validated['type'] === 'fill_blank' && !empty($validated['structured_blanks']))) {
            $validated = array_merge($validated, $this->structuredBankFields($validated));
        } else {
            $validated['correct_ans'] = $this->canonicalizeBankAnswer($validated);
        }
        unset($validated['correct_answer_tf']);
        $this->validateQuestionBankAcademicMetadata($validated, $user);

        if (!$permissionService->teacherCanUseSubject($user, (int) $validated['subject_id'])) {
            return redirect()->back()->withErrors(['subject_id' => get_phrase('You are not assigned to the selected subject/class.')])->withInput();
        }

        $bankQuestion = QuestionBank::create([
            'school_id' => $this->school_id,
            'subject_id' => $validated['subject_id'],
                ...$this->questionBankMetadataPayload($validated),
            'question' => $validated['question'],
            'type' => $validated['type'],
            'option_a' => $validated['option_a'] ?? null,
            'option_b' => $validated['option_b'] ?? null,
            'option_c' => $validated['option_c'] ?? null,
            'option_d' => $validated['option_d'] ?? null,
            'correct_ans' => $validated['correct_ans'] ?? null,
            'question_schema_version' => $validated['question_schema_version'] ?? null,
            'question_config' => $validated['question_config'] ?? null,
            'marking_config' => $validated['marking_config'] ?? null,
            'marks' => $validated['marks'],
            'difficulty' => $validated['difficulty'],
            'created_by' => $user->id,
        ]);
        if (Schema::hasTable('question_bank_tag') && !empty($validated['tag_ids'])) $bankQuestion->tags()->sync($validated['tag_ids']);

        AuditLog::record('create', 'Online Exams', "Teacher added question bank item");
        return redirect()->route('teacher.online_exams.question_bank')->with('success', get_phrase('Question added to bank.'));
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
        $bankQuestions = $this->teacherQuestionBankQuery($user, $this->teacherAssignedClassIds((int) $user->id))
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
                    'type' => $this->snapshotStorageType($bankQuestion),
                    'option_a' => $bankQuestion->option_a,
                    'option_b' => $bankQuestion->option_b,
                    'option_c' => $bankQuestion->option_c,
                    'option_d' => $bankQuestion->option_d,
                    'correct_ans' => \App\Support\OnlineExams\AnswerKey::normalize($bankQuestion->normalized_type, $bankQuestion->correct_ans, ['a' => $bankQuestion->option_a, 'b' => $bankQuestion->option_b, 'c' => $bankQuestion->option_c, 'd' => $bankQuestion->option_d]),
                    'question_schema_version' => $bankQuestion->question_schema_version,
                    'question_config' => $bankQuestion->question_config,
                    'marking_config' => $bankQuestion->marking_config,
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
            ->with(['student', 'exam.questions', 'answerRows'])
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
            ->with(['student', 'exam.questions', 'answerRows'])
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
        $assignedClassIds = $this->teacherAssignedClassIds((int) $user->id);

        $query = OnlineExamAnswer::query()
            ->with(['submission.student', 'submission.exam.questions', 'submission.answerRows', 'question'])
            ->whereHas('submission', function ($submissionQ) use ($canEditAll, $user, $assignedClassIds) {
                $submissionQ->where('school_id', $this->school_id)
                    ->whereHas('exam', function ($examQ) use ($canEditAll, $assignedClassIds, $user) {
                        if (!$canEditAll) {
                            $examQ->where(function ($accessQ) use ($assignedClassIds, $user) {
                                $accessQ->where('creator_id', $user->id)
                                    ->orWhere('created_by', $user->id);
                                if (!empty($assignedClassIds)) {
                                    $accessQ->orWhereIn('class_id', $assignedClassIds);
                                }
                            });
                        }
                    });
            })
            ->whereHas('question', function ($questionQ) {
                \App\Support\OnlineExams\OnlineExamMarking::manualQuestions($questionQ);
            });

        \App\Support\OnlineExams\OnlineExamMarking::responses($query);
        $query->whereHas('submission', fn ($q) => $q->whereIn('status', ['submitted', 'timed_out', 'pending_manual_marking']));
        $status = trim((string) $request->input('status', 'pending'));
        if ($status === 'marked') {
            $query->whereNotNull('awarded_marks')->whereNotNull('marked_at')->whereNotNull('marked_by');
        } else {
            \App\Support\OnlineExams\OnlineExamMarking::unmarked($query);
        }

        $answers = $query->orderBy('id')->paginate(25)->appends($request->all());

        return view('teacher.online_exam.marking', compact('answers', 'status'));
    }

    public function teacherMarkAnswer(ManualMarkAnswerRequest $request, OnlineExamAnswer $answer)
    {
        $this->markSubmissionAnswer($answer, $request->validated());
        return redirect()->back()->with('success', get_phrase('Answer marked.'));
    }

    public function teacherFinalizeResult(OnlineExamSubmission $submission)
    {
        $this->finalizeSubmission($submission, 'teacher');
        $submission->load('exam');
        OnlineExamPortalNotifier::admins('marking_submitted_for_review', 'Marking Awaiting Review', Auth::user()->name . ' submitted marking for "' . $submission->exam->title . '".', $submission->exam, Auth::id(), 'marking-review:' . $submission->id, $submission->id);
        return redirect()->back()->with('success', get_phrase('Marking submitted for Admin review.'));
    }

    // Student: take exam.
    public function studentExams(Request $request)
    {
        $this->authorize('viewAny', OnlineExam::class);

        $student_id = Auth::id();
        $school_id  = Auth::user()->school_id;
        // A student can have more than one current academic relationship.
        // Using only the first enrollment can hide an otherwise eligible exam.
        $classIds = Enrollment::where('user_id', $student_id)
            ->where('school_id', $school_id)
            ->pluck('class_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $sessionIds = Enrollment::where('user_id', $student_id)
            ->where('school_id', $school_id)
            ->pluck('session_id')->filter()->map(fn($id) => (int) $id)->unique()->values()->all();
        $programmeId = Schema::hasTable('student_profiles')
            ? (int) (StudentProfile::where('user_id', $student_id)->where('school_id', $school_id)->value('programme_id') ?? 0)
            : 0;

        $classConstraint = function ($query) use ($classIds) {
            $query->whereNull('class_id');
            if ($classIds) {
                $query->orWhereIn('class_id', $classIds);
            }
        };

        $eligibleExams = OnlineExam::forSchool($school_id)
            ->published()
            ->where($classConstraint)
            ->where(function ($query) use ($programmeId) {
                $query->whereNull('programme_id');
                if ($programmeId) $query->orWhere('programme_id', $programmeId);
            })
            ->where(function ($query) use ($sessionIds) {
                $query->whereNull('session_id');
                if ($sessionIds) $query->orWhereIn('session_id', $sessionIds);
            })
            ->with(['subject', 'questions'])
            ->get();

        // History is driven by the student's own submissions, not the exam's
        // current date window. Ended and released attempts remain visible,
        // while school and current class eligibility remain enforced.
        $submissions = OnlineExamSubmission::forSchool($school_id)
            ->forStudent($student_id)
            ->with(['exam.subject', 'exam.questions', 'answerRows'])
            ->orderByDesc('attempt_no')
            ->get()
            ->filter(function ($submission) use ($classIds, $programmeId) {
                $exam = $submission->exam;
                return $exam
                    && ($exam->class_id === null || in_array((int) $exam->class_id, $classIds, true))
                    && ($exam->programme_id === null || (int) $exam->programme_id === $programmeId);
            });

        $latestByExam = $submissions->groupBy('online_exam_id')->map->first();
        $attemptCounts = $submissions->groupBy('online_exam_id')->map->count();
        $historyExamIds = $latestByExam->keys()->all();
        $historyExams = $historyExamIds
            ? OnlineExam::forSchool($school_id)->whereIn('id', $historyExamIds)
                ->where(function ($query) use ($classIds) {
                    $query->whereNull('class_id');
                    if ($classIds) $query->orWhereIn('class_id', $classIds);
                })
                ->where(function ($query) use ($programmeId) {
                    $query->whereNull('programme_id');
                    if ($programmeId) $query->orWhere('programme_id', $programmeId);
                })
                ->with(['subject', 'questions'])->get()
            : collect();

        $attach = function ($exam) use ($latestByExam, $attemptCounts) {
            $exam->submission = $latestByExam->get($exam->id);
            $exam->attempts_used = (int) ($attemptCounts->get($exam->id) ?? 0);
            return $exam;
        };

        $historyExams = $historyExams->map($attach)->keyBy('id');
        $availableExams = $eligibleExams
            ->map($attach)
            ->reject(fn ($exam) => $exam->submission !== null)
            ->values();

        foreach ($eligibleExams as $exam) {
            if ($exam->submission) {
                $historyExams->put($exam->id, $exam);
            }
        }

        return view('student.online_exam.list', [
            'availableExams' => $availableExams,
            'attemptedExams' => $historyExams->values(),
            // Retain the legacy variable for extensions that still consume it.
            'exams' => $availableExams->concat($historyExams->values())->unique('id')->values(),
        ]);
    }
    /**
     * The pre-exam screen: rules, attempt count, and (when the exam
     * requires it) the fullscreen/webcam readiness steps that
     * StartOnlineExamRequest will insist on before start() succeeds.
     *
     * Used to be a bare JSON endpoint with no page consuming it — a student
     * clicking "Start Exam" from the list would end up here via a normal
     * browser redirect (see takeExam()) and land on raw JSON with no way
     * to actually proceed. Nothing else in the app called this endpoint
     * expecting JSON (grepped for it before changing), so switching it to
     * a real page is safe.
     */
    public function instructions($id)
    {
        $exam = $this->findStudentExamOrFail((int) $id);
        $this->authorize('sit', $exam);

        $activeSubmission = OnlineExamSubmission::where('online_exam_id', $exam->id)
            ->where('student_id', Auth::id())
            ->where('school_id', $this->school_id)
            ->where('status', OnlineExamSubmission::STATUS_IN_PROGRESS)
            ->first();

        // Already mid-attempt (e.g. came back after a disconnect) — no need
        // to see the rules again, resume straight into the exam.
        if ($activeSubmission) {
            return redirect()->route('student.online_exam.take', $exam->id);
        }

        $attemptsUsed = OnlineExamSubmission::where('online_exam_id', $exam->id)
            ->where('student_id', Auth::id())
            ->count();

        return view('student.online_exam.instructions', [
            'exam' => $exam,
            'attemptsUsed' => $attemptsUsed,
            'questionCount' => OnlineExamQuestion::forExam($exam->id)->count(),
            'withinWindow' => $this->withinExamWindow($exam),
        ]);
    }

    /**
     * Every student sits an exam within the same scheduled [start_datetime,
     * end_datetime] window — nobody can start early, and starting requires
     * enough of the window left to actually attempt it. Checked both here
     * (so the instructions page can explain *why* Start is unavailable) and
     * again in start() (the actual gate — instructions is just the message).
     */
    private function withinExamWindow(OnlineExam $exam): bool
    {
        return $exam->isWithinScheduledWindow();
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

            // The scheduled [start_datetime, end_datetime] window is already
            // enforced by StartOnlineExamRequest::withValidator() before this
            // method body ever runs — see withinExamWindow(), used here only
            // by instructions() to explain *why* Start is unavailable ahead
            // of the click.
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
            $scheduledEnd = $lockedExam->scheduledEndAt();
            $expiresAt = $scheduledEnd && $durationExpiry->gt($scheduledEnd)
                ? $scheduledEnd->copy()
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

        if ($submission->isExpired()) {
            try {
                $this->submitBySubmission($submission, 'timeout');
            } catch (InvalidArgumentException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => get_phrase('We could not submit your exam because one saved answer could not be validated. Your saved answers have been preserved. Please contact the examiner or administrator if the problem continues.'),
                ], 422);
            }
            return response()->json(['status' => 'error', 'message' => 'Attempt expired and was finalized.'], 422);
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
            // timeout-submit is a POST-only route; redirect() always issues a
            // GET, so redirecting a browser at it (the previous behaviour)
            // produced a 405 instead of actually finalising the attempt.
            // Calling the same finalisation logic directly does what the
            // redirect was trying to and returns its result (a redirect to
            // the results page) straight through.
            return $this->timeoutSubmit($submission->id);
        }

        $questions = OnlineExamQuestion::forExam($exam->id)
            ->ordered()
            ->get()
            ->map(function (OnlineExamQuestion $q) {
                $q->correct_ans = null;
                return $q;
            });

        $questions = $this->orderQuestionsForAttempt($questions, $exam, $submission);

        // Sanitize the model before it reaches the student view. Structured
        // marking rules and legacy answer keys are server-only data.
        $questions->each(function (OnlineExamQuestion $question) use ($exam, $submission) {
            $public = \App\Support\OnlineExams\QuestionContract::publicProjection($question);
            if ($exam->shuffle_options && count($public['options'] ?? []) > 1) {
                usort($public['options'], fn ($a, $b) => crc32($submission->id . '-o-' . $question->id . '-' . $a['id']) <=> crc32($submission->id . '-o-' . $question->id . '-' . $b['id']));
            }
            // Matching choices are always deterministically reordered: leaving
            // them in authoring/pair order could reveal the answer even when
            // ordinary MCQ option shuffling is disabled.
            if (count($public['right_items'] ?? []) > 1) {
                usort($public['right_items'], fn ($a, $b) => crc32($submission->id . '-r-' . $question->id . '-' . $a['id']) <=> crc32($submission->id . '-r-' . $question->id . '-' . $b['id']));
            }
            if (count($public['items'] ?? []) > 1) {
                usort($public['items'], fn ($a, $b) => crc32($submission->id . '-i-' . $question->id . '-' . $a['id']) <=> crc32($submission->id . '-i-' . $question->id . '-' . $b['id']));
                $correctOrder = array_values((QuestionContract::normalize($question, true)['marking']['correct_order'] ?? []));
                $presentedOrder = array_column($public['items'], 'id');
                if ($correctOrder === $presentedOrder) {
                    [$public['items'][0], $public['items'][1]] = [$public['items'][1], $public['items'][0]];
                }
            }
            $question->setAttribute('public_question', $public);
            $question->makeHidden(['correct_ans', 'marking_config']);
            $question->setAttribute('correct_ans', null);
        });

        $existingAnswers = OnlineExamAnswer::where('submission_id', $submission->id)
            ->get()
            ->keyBy('question_id');

        $serverAnswers = $existingAnswers->mapWithKeys(function (OnlineExamAnswer $answer) {
            return [$answer->question_id => [
                'selected_option' => $answer->selected_option,
                'answer_text' => $answer->answer_text,
                'answer_payload' => $answer->answer_schema_version !== null
                    ? \App\Support\OnlineExams\QuestionContract::decode($answer->answer_payload, 'answer_payload') : null,
                'answer_revision' => $answer->answer_revision,
                'updated_at' => optional($answer->updated_at)->toIso8601String(),
            ]];
        })->all();

        $optionOrders = $questions->mapWithKeys(function (OnlineExamQuestion $q) use ($exam, $submission) {
            return [$q->id => $this->orderOptionsForAttempt($q, $exam, $submission)];
        });

        return view('student.online_exam.take', [
            'exam' => $exam,
            'submission' => $submission,
            'questions' => $questions,
            'existingAnswers' => $existingAnswers,
            'serverAnswers' => $serverAnswers,
            'optionOrders' => $optionOrders,
            'remainingSeconds' => $submission->remainingSeconds(),
            'expiresAt' => optional($submission->expires_at)->toIso8601String(),
            'serverTime' => now()->toIso8601String(),
        ]);
    }

    /**
     * Question order for one attempt. Deliberately not a random shuffle on
     * every page load (a student refreshing mid-attempt would see the
     * questions rearrange, losing their place) — ordering by a hash of
     * (submission, question) is pseudo-random per attempt but perfectly
     * stable across reloads and resumes of that same attempt.
     */
    private function orderQuestionsForAttempt($questions, OnlineExam $exam, OnlineExamSubmission $submission)
    {
        if (!$exam->shuffle_questions) {
            return $questions;
        }

        return $questions
            ->sortBy(fn (OnlineExamQuestion $q) => crc32($submission->id . '-q-' . $q->id))
            ->values();
    }

    /**
     * Display order for one question's options, keyed by the real option
     * letter (a/b/c/d) so the submitted value is always the true option
     * regardless of the order it was shown in — shuffling is purely a
     * display concern, never touches how an answer is stored or graded.
     * Same stability rationale as orderQuestionsForAttempt().
     */
    private function orderOptionsForAttempt(OnlineExamQuestion $question, OnlineExam $exam, OnlineExamSubmission $submission): array
    {
        $options = [];
        foreach (['a', 'b', 'c', 'd'] as $key) {
            $text = $question->{'option_' . $key};
            if ($text !== null && $text !== '') {
                $options[$key] = $text;
            }
        }

        if (!$exam->shuffle_options || count($options) < 2) {
            return $options;
        }

        $keys = array_keys($options);
        usort($keys, fn ($a, $b) => crc32($submission->id . '-o-' . $question->id . '-' . $a) <=> crc32($submission->id . '-o-' . $question->id . '-' . $b));

        $ordered = [];
        foreach ($keys as $key) {
            $ordered[$key] = $options[$key];
        }

        return $ordered;
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

            abort_unless((int) $lockedSubmission->student_id === (int) Auth::id()
                && (int) $lockedSubmission->school_id === (int) $this->school_id
                && $lockedSubmission->exam
                && (int) $lockedSubmission->exam->school_id === (int) $this->school_id, 403);

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

            // The parent lock serializes saves even when the answer does not yet exist.
            // Finalization takes the same lock; keep all checks and writes inside it.
            $answer = OnlineExamAnswer::where('submission_id', $lockedSubmission->id)
                ->where('question_id', $question->id)->lockForUpdate()->first();
            $revision = (int) $validated['answer_revision'];
            $payload = [
                'selected_option' => $validated['selected_option'] ?? null,
                'answer_text' => $validated['answer_text'] ?? null,
            ];
            if (array_key_exists('answer_payload', $validated) && $validated['answer_payload'] !== null && $validated['answer_payload'] !== '') {
                $canonicalAnswer = \App\Support\OnlineExams\AnswerContract::fromRequest($validated, $question);
                $payload['answer_schema_version'] = \App\Support\OnlineExams\AnswerContract::STRUCTURED_VERSION;
                $payload['answer_payload'] = \App\Support\OnlineExams\AnswerContract::encode($canonicalAnswer);
            } elseif (array_key_exists('answer_payload', $validated)) {
                $payload['answer_schema_version'] = null;
                $payload['answer_payload'] = null;
            }
            $status = 'success';
            if ($answer && $revision <= $answer->answer_revision) {
                $identical = $answer->selected_option === $payload['selected_option']
                    && $answer->answer_text === $payload['answer_text']
                    && ($answer->answer_payload ?? null) === ($payload['answer_payload'] ?? null);
                if ($revision < $answer->answer_revision || !$identical) {
                    return [
                        'status' => $revision < $answer->answer_revision ? 'stale' : 'conflict',
                        'submission_id' => $lockedSubmission->id,
                        'question_id' => $question->id,
                        'answer_revision' => $answer->answer_revision,
                        'selected_option' => $answer->selected_option,
                        'answer_text' => $answer->answer_text,
                    ];
                }
                $status = 'idempotent';
            } else {
                $answer = $answer ?: new OnlineExamAnswer([
                    'submission_id' => $lockedSubmission->id,
                    'question_id' => $question->id,
                ]);
                $answer->fill($payload + ['answer_revision' => $revision])->save();
            }

            $lockedSubmission->update([
                'last_activity_at' => now(),
            ]);

            return [
                'status' => $status,
                'submission_id' => $lockedSubmission->id,
                'question_id' => $question->id,
                'answer_revision' => $answer->answer_revision,
                'answer_updated_at' => optional($answer->fresh()->updated_at)->toIso8601String(),
                'expires_at' => optional($lockedSubmission->expires_at)->toDateTimeString(),
                'server_time' => now()->toDateTimeString(),
            ];
        });

        return response()->json($responseData, in_array($responseData['status'], ['stale', 'conflict'], true) ? 409 : 200);
    }

    public function heartbeat($submissionId)
    {
        $submission = $this->findStudentSubmissionOrFail((int) $submissionId);
        $this->authorize('view', $submission);

        if ($submission->status === OnlineExamSubmission::STATUS_IN_PROGRESS && $submission->isExpired()) {
            try {
                $this->submitBySubmission($submission, 'timeout');
            } catch (InvalidArgumentException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => get_phrase('We could not submit your exam because one saved answer could not be validated. Your saved answers have been preserved. Please contact the examiner or administrator if the problem continues.'),
                ], 422);
            }
            $submission = $submission->fresh();
            return response()->json([
                'status' => 'expired',
                'server_time' => now()->toDateTimeString(),
                'expires_at' => optional($submission->expires_at)->toDateTimeString(),
                'expired' => true,
                'submission_status' => $submission->status,
            ]);
        }

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
            ->whereKey((int) $request->input('submission_id'))
            ->firstOrFail();

        if ($submission->status !== OnlineExamSubmission::STATUS_IN_PROGRESS) {
            return $submission->isResultVisible()
                ? redirect()->route('student.online_exam.result', $submission->id)
                : redirect()->back()->withErrors(['submission' => get_phrase('This submission has already been received and is awaiting marking or release.')]);
        }

        try {
            return $this->submitBySubmission($submission, 'manual');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors([
                'submission' => get_phrase('We could not submit your exam because one saved answer could not be validated. Your saved answers have been preserved. Please contact the examiner or administrator if the problem continues.'),
            ]);
        }
    }

    public function timeoutSubmit($submissionId)
    {
        $submission = $this->findStudentSubmissionOrFail((int) $submissionId);
        $this->authorize('submit', $submission);

        if ($submission->status !== OnlineExamSubmission::STATUS_IN_PROGRESS) {
            return $submission->isResultVisible()
                ? redirect()->route('student.online_exam.result', $submission->id)
                : redirect()->back()->withErrors(['submission' => get_phrase('This attempt has already been finalized.')]);
        }

        try {
            return $this->submitBySubmission($submission, 'timeout');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors([
                'submission' => get_phrase('We could not submit your exam because one saved answer could not be validated. Your saved answers have been preserved. Please contact the examiner or administrator if the problem continues.'),
            ]);
        }
    }

    public function examResult($submission_id)
    {
        $submission = $this->findStudentSubmissionOrFail((int) $submission_id);
        // A student owns the submission even while its result is withheld.
        // Keep ownership protection, but render a state-aware status page
        // instead of treating the normal pending-release state as forbidden.
        $this->authorize('view', $submission);

        if ($submission->submitted_at && !$submission->isResultVisible()) {
            return view('student.online_exam.submitted', [
                'submission' => $submission->load('exam'),
            ]);
        }

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
            ->with(['student', 'exam.questions', 'answerRows'])
            ->withCount('proctoringEvents')
            ->orderByDesc('submitted_at')
            ->paginate(30);

        return view('admin.online_exam.submissions', compact('exam', 'submissions'));
    }

    public function results(Request $request, $exam_id)
    {
        $exam = $this->findExamOrFail((int) $exam_id);
        $this->authorize('markAnswers', $exam);

        $submissions = OnlineExamSubmission::where('online_exam_id', $exam->id)
            ->where('school_id', $this->school_id)
            ->with(['student', 'exam.questions', 'answerRows'])
            ->orderByDesc('submitted_at')
            ->paginate(30);

        $answersNeedingMarking = OnlineExamAnswer::query()
            ->with(['submission.student', 'question'])
            ->whereHas('submission', function ($q) use ($exam) {
                $q->where('online_exam_id', $exam->id)->where('school_id', $this->school_id);
            })
            ->whereHas('question', function ($q) {
                \App\Support\OnlineExams\OnlineExamMarking::manualQuestions($q);
            })
            ->whereHas('submission', fn ($q) => $q->whereIn('status', ['submitted', 'timed_out', 'pending_manual_marking']))
            ->where(fn ($q) => \App\Support\OnlineExams\OnlineExamMarking::responses($q))
            ->where(fn ($q) => \App\Support\OnlineExams\OnlineExamMarking::unmarked($q))
            ->get();

        $highlightSubmissionId = (int) $request->query('submission', 0);
        return view('admin.online_exam.results', compact('exam', 'submissions', 'answersNeedingMarking', 'highlightSubmissionId'));
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
        $answer = OnlineExamAnswer::findOrFail((int) $answerId);
        $this->markSubmissionAnswer($answer, $request->validated());
        return redirect()->back()->with('success', get_phrase('Answer marked'));
    }

    public function finalizeResult($submissionId)
    {
        $submission = OnlineExamSubmission::findOrFail((int) $submissionId);
        $this->finalizeSubmission($submission, 'administrator');
        return redirect()->back()->with('success', get_phrase('Result finalized'));
    }

    public function publishResult(OnlineExamSubmission $submission)
    {
        abort_unless(Auth::user() && (int) Auth::user()->role_id === 2, 403, 'Only an administrator may publish official results.');
        $changed = DB::transaction(function () use ($submission) {
            $locked = OnlineExamSubmission::whereKey($submission->id)->lockForUpdate()->with('exam')->firstOrFail();
            $this->assertStaffSubmission($locked);
            $this->authorize('grade', $locked);
            if ($locked->status === OnlineExamSubmission::STATUS_RESULT_PUBLISHED) return false;
            abort_if($locked->status !== OnlineExamSubmission::STATUS_FINALIZED, 422, 'Only finalized results can be released.');
            $reviewState = $locked->result_review_state ?: 'pending_review';
            abort_if($reviewState !== 'pending_review', 422, 'This result is not awaiting Admin review.');
            if (($locked->exam->result_release_policy ?? 'immediate') === 'after_exam_end') {
                $end = $locked->exam->scheduledEndAt();
                abort_if(!$end || now($locked->exam->scheduleTimezone())->lt($end), 422, 'This result cannot be published before the exam ends.');
            }
            $updated = $locked->update(['status' => OnlineExamSubmission::STATUS_RESULT_PUBLISHED, 'result_review_state' => 'published']);
            abort_unless($updated && $locked->refresh()->status === OnlineExamSubmission::STATUS_RESULT_PUBLISHED, 422, 'The result could not be released. Please try again.');
            return true;
        });
        $persisted = OnlineExamSubmission::whereKey($submission->id)->value('status') === OnlineExamSubmission::STATUS_RESULT_PUBLISHED;
        if (!$persisted) {
            return redirect()->back()->withErrors(['result' => get_phrase('The result was not released. No success was recorded.')]);
        }
        if ($changed) {
            \App\Support\OnlineExams\OnlineExamResultNotifier::resultAvailable($submission->fresh());
            $published = $submission->fresh()->load('exam');
            OnlineExamPortalNotifier::create($published->exam->school_id, $published->student_id, 'result_published', 'Result Available', 'Your result for ' . $published->exam->title . ' is now available.', route('student.online_exam.result', $published->id), Auth::id(), $published->exam->id, $published->id, 'result-published:' . $published->id);
            AuditLog::record('update', 'Online Exams', "Published result for submission #{$submission->id}");
        }
        return redirect()->back()->with('success', get_phrase('Result published'));
    }

    public function returnResultForCorrection(Request $request, OnlineExamSubmission $submission)
    {
        abort_unless(Auth::user() && (int) Auth::user()->role_id === 2, 403, 'Only an administrator may return results.');
        $reason = trim((string) $request->input('reason', ''));
        DB::transaction(function () use ($submission) {
            $locked = OnlineExamSubmission::whereKey($submission->id)->lockForUpdate()->with('exam')->firstOrFail();
            $this->assertStaffSubmission($locked);
            $this->authorize('grade', $locked);
            abort_unless($locked->status === OnlineExamSubmission::STATUS_FINALIZED && $locked->result_review_state === 'pending_review', 422, 'Only results awaiting review can be returned.');
            $locked->update(['status' => OnlineExamSubmission::STATUS_PENDING_MANUAL, 'result_review_state' => 'returned_for_correction']);
        });
        $submission->load('exam');
        OnlineExamPortalNotifier::teacher('marking_returned', 'Marking Returned', 'Marking for "' . $submission->exam->title . '" was returned for correction.' . ($reason ? ' Reason: ' . $reason : ''), $submission->exam, Auth::id(), 'marking-returned:' . $submission->id);
        return redirect()->back()->with('success', get_phrase('Marking returned for correction.'));
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
                return $locked;
            }

            $now = now();
            if ($locked->expires_at && $now->gte($locked->expires_at)) {
                $locked->timeout_at = $locked->timeout_at ?: $now;
                $submittedVia = 'timeout';
            }

            $answers = OnlineExamAnswer::where('submission_id', $locked->id)
                ->with('question')
                ->get();

            foreach ($answers as $answer) {
                $question = $answer->question;
                abort_unless($question && (int) $question->online_exam_id === (int) $locked->online_exam_id, 422, 'Answer question does not belong to this exam.');
                if (\App\Support\OnlineExams\OnlineExamMarking::isAutomatic($question)) {
                    $correct = $this->isObjectiveAnswerCorrect($question, $answer);
                    $answer->update(['is_correct' => $correct, 'awarded_marks' => $correct ? $question->marks : 0]);
                }
            }
            $locked->load(['exam.questions', 'answerRows']);
            $summary = \App\Support\OnlineExams\OnlineExamMarking::summary($locked);
            $objectiveScore = $summary['objective_score'];
            $manualScore = $summary['manual_score'];
            $score = $summary['score'];
            $passed = $summary['pending'] ? null : $score >= (float) $locked->exam->pass_mark;
            $nextStatus = $summary['pending'] ? OnlineExamSubmission::STATUS_PENDING_MANUAL : OnlineExamSubmission::STATUS_FINALIZED;

            $locked->update([
                'objective_score' => $objectiveScore,
                'manual_score' => $manualScore,
                'score' => $score,
                'passed' => $passed,
                'submitted_at' => $now,
                'submitted_via' => $submittedVia,
                'status' => $nextStatus,
                // Completing the attempt only makes the result ready. The
                // governance review state changes when staff explicitly
                // submits the completed marking for Admin review.
                'result_review_state' => 'not_ready',
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
        if ($submission->isFinalized()) return;
        $submission->load(['exam.questions', 'answerRows']);
        $summary = \App\Support\OnlineExams\OnlineExamMarking::summary($submission);
        $submission->update([
            'objective_score' => $summary['objective_score'], 'manual_score' => $summary['manual_score'],
            'score' => $summary['score'], 'passed' => null,
            'status' => $summary['pending'] ? OnlineExamSubmission::STATUS_PENDING_MANUAL : OnlineExamSubmission::STATUS_SUBMITTED,
            'result_review_state' => 'not_ready',
        ]);
    }

    private function isObjectiveAnswerCorrect(OnlineExamQuestion $question, OnlineExamAnswer $answer): bool
    {
        if ($question->question_schema_version !== null) {
            $contract = \App\Support\OnlineExams\QuestionContract::normalize($question, true);
            $marking = $contract['marking'];
            $payload = \App\Support\OnlineExams\AnswerContract::fromStored($answer, $question);
            if ($contract['type'] === 'multiple_select') {
                $expected = array_values(array_unique(array_map('strval', $marking['correct_option_ids'] ?? [])));
                $given = array_values(array_unique(array_map('strval', $payload['selected_option_ids'] ?? [])));
                sort($expected); sort($given);
                return $expected === $given;
            }
            if ($contract['type'] === 'numeric') {
                $value = (float) ($payload['value'] ?? 0);
                $target = (float) ($marking['target'] ?? 0);
                $tolerance = max(0.0, (float) ($marking['tolerance'] ?? 0));
                return abs($value - $target) <= $tolerance + 1e-12;
            }
            if ($contract['type'] === 'fill_blank') {
                return QuestionContract::fillBlankCorrect($contract, $payload);
            }
            if ($contract['type'] === 'matching') return QuestionContract::matchingCorrect($contract, $payload);
            if ($contract['type'] === 'ordering') return QuestionContract::orderingCorrect($contract, $payload);
        }
        $expected = \App\Support\OnlineExams\AnswerKey::forQuestion($question);
        $given = strtolower(trim((string) $answer->selected_option));
        return $expected !== '' && $expected === $given;
    }

    private function assertSubmissionMarkable(OnlineExamSubmission $submission): void
    {
        abort_if(!in_array($submission->status, [OnlineExamSubmission::STATUS_SUBMITTED, OnlineExamSubmission::STATUS_TIMED_OUT, OnlineExamSubmission::STATUS_PENDING_MANUAL], true), 422, 'Only submitted attempts may be marked.');
    }

    private function finalizeSubmission(OnlineExamSubmission $submission, string $via): void
    {
        $changed = DB::transaction(function () use ($submission) {
            $locked = OnlineExamSubmission::whereKey($submission->id)->lockForUpdate()->with('exam')->firstOrFail();
            $this->assertStaffSubmission($locked);
            $this->authorize('grade', $locked);
            if ($locked->isFinalized()) return false;
            $this->assertSubmissionMarkable($locked);
            $this->recomputeSubmissionScore($locked);
            abort_if($locked->status === OnlineExamSubmission::STATUS_PENDING_MANUAL, 422, 'Finalize is blocked until all answered manual questions are marked.');
            $locked->update(['status' => OnlineExamSubmission::STATUS_FINALIZED,
                'result_review_state' => 'pending_review',
                'passed' => (float) $locked->score >= (float) $locked->exam->pass_mark]);
            return true;
        });
        if ($changed) {
            AuditLog::record('update', 'Online Exams', "Finalized result for submission #{$submission->id} via {$via}");
            \App\Support\OnlineExams\OnlineExamResultNotifier::resultAvailable($submission->fresh());
        }
    }

    private function assertStaffSubmission(OnlineExamSubmission $submission): void
    {
        abort_unless((int) $submission->school_id === (int) $this->school_id
            && $submission->exam && (int) $submission->exam->school_id === (int) $this->school_id, 404);
    }

    private function markSubmissionAnswer(OnlineExamAnswer $answer, array $validated): void
    {
        $changed = DB::transaction(function () use ($answer, $validated) {
            // All marking and finalization writers use the autosave parent lock first.
            $submission = OnlineExamSubmission::whereKey($answer->submission_id)->lockForUpdate()->with('exam')->firstOrFail();
            $this->assertStaffSubmission($submission);
            $this->assertSubmissionMarkable($submission);
            $locked = OnlineExamAnswer::whereKey($answer->id)->where('submission_id', $submission->id)->lockForUpdate()->with('question')->firstOrFail();
            $locked->setRelation('submission', $submission);
            $this->authorize('mark', $locked);
            abort_unless((int) $validated['answer_id'] === (int) $locked->id
                && $locked->question && (int) $locked->question->online_exam_id === (int) $submission->online_exam_id, 422, 'Answer must belong to this submission exam.');
            abort_if(\App\Support\OnlineExams\OnlineExamMarking::isAutomatic($locked->question), 422, 'Automatic marks cannot be overridden.');
            abort_unless(\App\Support\OnlineExams\OnlineExamMarking::hasResponse($locked), 422, 'Unanswered questions contribute zero without manual marking.');
            $mark = (float) $validated['awarded_marks'];
            abort_if($mark < 0 || $mark > (float) $locked->question->marks, 422, 'Mark is outside question bounds.');
            $comment = array_key_exists('teacher_comment', $validated) ? $validated['teacher_comment'] : $locked->teacher_comment;
            $identical = \App\Support\OnlineExams\OnlineExamMarking::isManuallyMarked($locked)
                && (float) $locked->awarded_marks === $mark && $locked->teacher_comment === $comment
                && (int) $locked->marked_by === (int) Auth::id();
            if (!$identical) $locked->update(['awarded_marks' => $mark, 'marked_by' => Auth::id(), 'marked_at' => now(), 'teacher_comment' => $comment]);
            $this->recomputeSubmissionScore($submission);
            return !$identical;
        });
        if ($changed) AuditLog::record('update', 'Online Exams', "Marked answer #{$answer->id}");
    }

    private function publishExam(OnlineExam $exam): void
    {
        DB::transaction(function () use ($exam) {
            $locked = OnlineExam::whereKey($exam->id)->lockForUpdate()->firstOrFail();
            if ($locked->is_published) {
                return;
            }
            $errors = $locked->publicationReadinessErrors();
            if (!empty($errors)) {
                abort(422, implode(' ', $errors));
            }
            $locked->update(['is_published' => 1, 'workflow_state' => 'published', 'reviewed_by' => Auth::id(), 'reviewed_at' => now(), 'updater_id' => Auth::id()]);
        });
    }

    private function publicationReadinessFailure(Request $request, array $errors)
    {
        $messages = array_merge([get_phrase('Exam cannot be published yet.')], $errors);
        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'error',
                'message' => $messages[0],
                'errors' => ['readiness' => $messages],
            ], 422);
        }

        return redirect()->back()->withErrors(['readiness' => $errors])->withInput();
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
        $programmeIds = Schema::hasTable('teacher_programme_assignments')
            ? TeacherProgrammeAssignment::where('teacher_id', $teacherId)
                ->where('school_id', $this->school_id)
                ->pluck('programme_id')->filter()->map(fn($id) => (int) $id)->all()
            : [];

        if (!$assignedClassIds && !$programmeIds) {
            return collect();
        }

        return Subject::where('school_id', $this->school_id)
            ->where(function ($query) use ($assignedClassIds, $programmeIds) {
                if ($assignedClassIds) {
                    $query->whereIn('class_id', $assignedClassIds);
                }
                if ($programmeIds) {
                    $query->orWhereIn('programme_id', $programmeIds);
                }
            })
            ->orderBy('name')
            ->get();
    }

    private function teacherAssignableProgrammes(int $teacherId)
    {
        if (!Schema::hasTable('teacher_programme_assignments')) {
            return collect();
        }

        $programmeIds = TeacherProgrammeAssignment::where('teacher_id', $teacherId)
            ->where('school_id', $this->school_id)->pluck('programme_id');

        return Programme::where('school_id', $this->school_id)
            ->where('is_active', 1)
            ->whereIn('id', $programmeIds)
            ->orderBy('name')
            ->get();
    }

    private function academicSessionsForSelection()
    {
        $sessions = Session::where('school_id', $this->school_id)
            ->orderByDesc('status')->orderByDesc('id')->get();

        $human = $sessions->filter(function ($session) {
            $title = trim((string) $session->session_title);
            return $title !== '' && !preg_match('/^Session\s+[a-f0-9]{8,}$/i', $title);
        });

        return $human->isNotEmpty() ? $human : $sessions;
    }

    private function canonicalizeBankAnswer(array $data): ?string
    {
        $type = (string) ($data['type'] ?? '');
        $raw = $type === 'true_false' ? ($data['correct_answer_tf'] ?? $data['correct_ans'] ?? null) : ($data['correct_ans'] ?? null);
        $key = \App\Support\OnlineExams\AnswerKey::normalize($type, $raw, [
            'a' => $data['option_a'] ?? null,
            'b' => $data['option_b'] ?? null,
            'c' => $data['option_c'] ?? null,
            'd' => $data['option_d'] ?? null,
        ]);

        if (in_array($type, ['mcq', 'true_false'], true) && $key === null) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'correct_ans' => $type === 'mcq'
                    ? 'Select a valid correct option (A, B, C or D).'
                    : 'Select True or False as the correct answer.',
            ]);
        }

        return $key;
    }

    private function snapshotStorageType(QuestionBank $bankQuestion): string
    {
        if ($bankQuestion->question_schema_version === null) {
            return (string) $bankQuestion->type;
        }

        $type = QuestionContract::normalize($bankQuestion)['type'];
        return match ($type) {
            'multiple_select' => 'mcq',
            'numeric', 'matching', 'ordering' => 'short',
            default => $type,
        };
    }

    private function structuredBankFields(array $data): array
    {
        $structured = \App\Support\OnlineExams\QuestionContract::authoring(
            (string) $data['type'],
            (string) $data['question'],
            (array) (($data['type'] ?? null) === 'fill_blank' ? ($data['structured_blanks'] ?? []) : (($data['type'] ?? null) === 'matching' ? ($data['structured_pairs'] ?? []) : (($data['type'] ?? null) === 'ordering' ? ($data['structured_order_items'] ?? []) : ($data['structured_options'] ?? [])))),
            [
                'correct_option_ids' => (array) ($data['correct_option_ids'] ?? []),
                'target' => $data['numeric_target'] ?? null,
                'tolerance' => $data['numeric_tolerance'] ?? 0,
                'case_sensitive' => !empty($data['case_sensitive']),
                'trim_whitespace' => array_key_exists('trim_whitespace', $data) ? !empty($data['trim_whitespace']) : true,
            ],
            $data['marks']
        );

        return [
            'type' => $structured['storage_type'],
            'correct_ans' => null,
            'question_schema_version' => $structured['schema_version'],
            'question_config' => $structured['question_config'],
            'marking_config' => $structured['marking_config'],
        ];
    }

    private function questionBankMetadataPayload(array $data): array
    {
        if (!Schema::hasColumn('question_banks', 'programme_id')) return [];
        return ['programme_id'=>$data['programme_id']??null,'session_id'=>$data['session_id']??null,'topic_id'=>$data['topic_id']??null,'subtopic_id'=>$data['subtopic_id']??null,'status'=>$data['status']??'active'];
    }

    /** Remove inactive authoring groups and unused active slots before wildcard validation. */
    private function normalizeBankStructuredOptions(Request $request): void
    {
        $type = (string) $request->input('type', '');
        $active = match ($type) {
            'multiple_select' => 'structured_options',
            'fill_blank' => 'structured_blanks',
            'matching' => 'structured_pairs',
            'ordering' => 'structured_order_items',
            default => null,
        };
        foreach (['structured_options', 'structured_pairs', 'structured_order_items', 'structured_blanks'] as $group) {
            if ($group !== $active) $request->request->remove($group);
        }
        if (!$active) return;
        $inputRows = (array) $request->input($active, []);
        if ($active === 'structured_blanks') {
            $inputRows = array_values(array_filter(array_map(static function ($row) {
                if (!is_array($row)) return null;
                $answers = array_values(array_filter(array_map(static fn ($answer) => trim((string) $answer), (array) ($row['accepted_answers'] ?? [])), static fn ($answer) => $answer !== ''));
                if (!$answers) return null;
                $row['accepted_answers'] = $answers;
                return $row;
            }, $inputRows)));
        }
        $rows = array_values(array_filter($inputRows, static function ($row) use ($active) {
            if (!is_array($row)) return false;
            if ($active === 'structured_options') return trim((string) ($row['label'] ?? '')) !== '';
            if ($active === 'structured_blanks') return collect((array) ($row['accepted_answers'] ?? []))->contains(fn ($answer) => trim((string) $answer) !== '');
            foreach ($row as $value) {
                if (is_array($value)) {
                    if (collect($value)->contains(fn ($item) => trim((string) $item) !== '')) return true;
                } elseif (trim((string) $value) !== '') return true;
            }
            return false;
        }));
        if ($active === 'structured_options') {
            $correctIds = array_map('strval', (array) $request->input('correct_option_ids', []));
            foreach ($inputRows as $row) {
                if (is_array($row) && in_array((string) ($row['id'] ?? ''), $correctIds, true) && trim((string) ($row['label'] ?? '')) === '') $rows[] = $row;
            }
        }
        $request->request->set($active, $rows);
    }

    private function validateQuestionBankAcademicMetadata(array $data, $teacher = null): void
    {
        if (!empty($data['programme_id'])) {
            abort_unless(Programme::where('school_id', $this->school_id)->whereKey($data['programme_id'])->where('is_active', 1)->exists(), 422, 'The selected Programme is not available in this school.');
        }
        if (!empty($data['session_id'])) {
            abort_unless(Session::where('school_id', $this->school_id)->whereKey($data['session_id'])->exists(), 422, 'The selected Academic Period is not available in this school.');
        }
        if (!empty($data['subject_id'])) {
            abort_unless(Subject::where('school_id', $this->school_id)->whereKey($data['subject_id'])->exists(), 422, 'The selected Course is not available in this school.');
        }
        if (array_key_exists('status', $data) && !in_array($data['status'], ['draft', 'active', 'retired', 'archived'], true)) {
            abort(422, 'The selected lifecycle state is invalid.');
        }
        if (!empty($data['subject_id']) && !empty($data['programme_id'])) {
            abort_unless(Subject::where('school_id', $this->school_id)->whereKey($data['subject_id'])->where('programme_id', $data['programme_id'])->exists(), 422, 'The selected Course is not part of the selected Programme.');
        }
        if ($teacher && !empty($data['programme_id'])) {
            abort_unless(TeacherProgrammeAssignment::where('school_id', $this->school_id)->where('teacher_id', $teacher->id)->where('programme_id', $data['programme_id'])->exists(), 403);
        }
        $topic = !empty($data['topic_id']) ? QuestionTopic::where('school_id',$this->school_id)->where('subject_id',$data['subject_id'])->whereNull('parent_id')->find($data['topic_id']) : null;
        abort_if(!empty($data['topic_id']) && !$topic, 422, 'The selected Topic is not available for this Course.');
        if (!empty($data['subtopic_id'])) {
            abort_unless($topic && QuestionTopic::where('id',$data['subtopic_id'])->where('school_id',$this->school_id)->where('subject_id',$data['subject_id'])->where('parent_id',$topic->id)->exists(), 422, 'The selected Subtopic does not belong to the selected Topic.');
        }
        $tagIds = array_values(array_unique(array_map('intval', (array)($data['tag_ids'] ?? []))));
        abort_if(count($tagIds) !== count((array)($data['tag_ids'] ?? [])), 422, 'Duplicate tags are not allowed.');
        if ($tagIds && QuestionTag::where('school_id',$this->school_id)->whereIn('id',$tagIds)->count() !== count($tagIds)) abort(422, 'One or more tags are not available in this school.');
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

    private function teacherQuestionBankQuery($user, array $assignedClassIds)
    {
        $assignedSubjectIds = empty($assignedClassIds)
            ? collect()
            : Subject::where('school_id', $this->school_id)
                ->whereIn('class_id', $assignedClassIds)
                ->pluck('id');

        return QuestionBank::forSchool($this->school_id)
            ->where(function ($query) use ($user, $assignedSubjectIds) {
                $query->where('created_by', $user->id)->orWhereNull('created_by');
                if ($assignedSubjectIds->isNotEmpty()) {
                    $query->orWhere(function ($assigned) use ($assignedSubjectIds) {
                        $assigned->whereIn('subject_id', $assignedSubjectIds)
                            ->whereExists(function ($creator) {
                                $creator->selectRaw('1')->from('users')
                                    ->whereColumn('users.id', 'question_banks.created_by')
                                    ->where('users.role_id', '!=', 3);
                            });
                    });
                }
            });
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
        $classIds = Enrollment::where('user_id', $user->id)
            ->where('school_id', $this->school_id)
            ->pluck('class_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $sessionIds = Enrollment::where('user_id', $user->id)
            ->where('school_id', $this->school_id)
            ->pluck('session_id')->filter()->map(fn($id) => (int) $id)->unique()->values()->all();
        $programmeId = Schema::hasTable('student_profiles')
            ? (int) (StudentProfile::where('user_id', $user->id)->where('school_id', $this->school_id)->value('programme_id') ?? 0)
            : 0;

        return OnlineExam::forSchool($this->school_id)
            ->published()
            ->where(function ($query) use ($classIds) {
                $query->whereNull('class_id');
                if ($classIds) {
                    $query->orWhereIn('class_id', $classIds);
                }
            })
            ->where(function ($query) use ($programmeId) {
                $query->whereNull('programme_id');
                if ($programmeId) $query->orWhere('programme_id', $programmeId);
            })
            ->where(function ($query) use ($sessionIds) {
                $query->whereNull('session_id');
                if ($sessionIds) $query->orWhereIn('session_id', $sessionIds);
            })
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
