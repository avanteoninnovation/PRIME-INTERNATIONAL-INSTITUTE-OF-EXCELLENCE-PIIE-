<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLiveClassRequest;
use App\Http\Requests\UpdateLiveClassRequest;
use App\Models\AuditLog;
use App\Models\Classes;
use App\Models\LiveClass;
use App\Models\Programme;
use App\Models\Session;
use App\Models\Subject;
use App\Models\TeacherPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LiveClassController extends Controller
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
        $this->authorize('viewAny', LiveClass::class);

        $search = trim((string) $request->input('search', ''));
        $subjectId = $request->input('subject_id');
        $platform = $request->input('platform');
        $status = $request->input('status');
        $date = $request->input('date');

        $classes = LiveClass::query()
            ->where('school_id', $this->school_id)
            ->when(!$this->canManageAll(Auth::user()), function ($q) {
                $q->where(function ($sub) {
                    $sub->where('teacher_id', Auth::id())
                        ->orWhere('created_by', Auth::id());
                });
            })
            ->when($search !== '', fn($q) => $q->where('title', 'like', "%{$search}%"))
            ->when($subjectId, fn($q) => $q->where('subject_id', $subjectId))
            ->when($platform, fn($q) => $q->where('platform', $platform))
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($date, fn($q) => $q->whereDate('start_date', $date))
            ->with(['subject', 'teacher', 'programme', 'academicSession'])
            ->orderByDesc('start_date')
            ->orderByDesc('start_time')
            ->paginate(20);

        $subjects = $this->getAllowedSubjects();
        $classList = Classes::where('school_id', $this->school_id)->orderBy('name')->get();
        $programmes = Programme::where('school_id', $this->school_id)->where('is_active', 1)->orderBy('name')->get();
        $sessions = Session::where('school_id', $this->school_id)->orderByDesc('id')->get();

        return view('admin.live_class.index', compact(
            'classes',
            'subjects',
            'classList',
            'programmes',
            'sessions',
            'search',
            'subjectId',
            'platform',
            'status',
            'date'
        ));
    }

    public function create()
    {
        $this->authorize('create', LiveClass::class);

        $liveClass = new LiveClass([
            'platform' => 'jitsi',
            'status' => LiveClass::STATUS_DRAFT,
            'timezone' => config('app.timezone', 'UTC'),
            'is_published' => false,
        ]);

        $subjects = $this->getAllowedSubjects();
        $classList = Classes::where('school_id', $this->school_id)->orderBy('name')->get();
        $programmes = Programme::where('school_id', $this->school_id)->where('is_active', 1)->orderBy('name')->get();
        $sessions = Session::where('school_id', $this->school_id)->orderByDesc('id')->get();

        return view('admin.live_class.create', compact('liveClass', 'subjects', 'classList', 'programmes', 'sessions'));
    }

    public function openModal(Request $request)
    {
        $id = $request->id;
        if ($id) {
            $liveClass = LiveClass::where('school_id', $this->school_id)->findOrFail($id);
            $this->authorize('update', $liveClass);
        } else {
            $this->authorize('create', LiveClass::class);
            $liveClass = new LiveClass([
                'platform' => 'jitsi',
                'status' => LiveClass::STATUS_DRAFT,
                'timezone' => config('app.timezone', 'UTC'),
                'is_published' => false,
            ]);
        }

        $subjects = $this->getAllowedSubjects();
        $classList = Classes::where('school_id', $this->school_id)->orderBy('name')->get();
        $programmes = Programme::where('school_id', $this->school_id)->where('is_active', 1)->orderBy('name')->get();
        $sessions = Session::where('school_id', $this->school_id)->orderByDesc('id')->get();

        return view('admin.live_class.modal', compact('liveClass', 'subjects', 'classList', 'programmes', 'sessions'));
    }

    public function store(StoreLiveClassRequest $request)
    {
        $this->authorize('create', LiveClass::class);

        $validated = $request->validated();
        $payload = $this->buildPayload($validated, null);

        $liveClass = DB::transaction(function () use ($payload) {
            $record = LiveClass::create($payload);
            AuditLog::record('create', 'Live Classes', "Scheduled live class: {$record->title}");
            return $record;
        });

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => get_phrase('Live class scheduled'),
                'redirect' => route('admin.live_classes.show', $liveClass->id),
            ]);
        }

        return redirect()->route('admin.live_classes.show', $liveClass->id)
            ->with('success', get_phrase('Live class scheduled'));
    }

    public function show(LiveClass $liveClass)
    {
        abort_unless((int) $liveClass->school_id === (int) $this->school_id, 404);
        $this->authorize('view', $liveClass);

        $liveClass->load(['subject', 'teacher', 'programme', 'academicSession', 'creator']);
        return view('admin.live_class.show', compact('liveClass'));
    }

    public function edit(LiveClass $liveClass)
    {
        abort_unless((int) $liveClass->school_id === (int) $this->school_id, 404);
        $this->authorize('update', $liveClass);

        $subjects = $this->getAllowedSubjects();
        $classList = Classes::where('school_id', $this->school_id)->orderBy('name')->get();
        $programmes = Programme::where('school_id', $this->school_id)->where('is_active', 1)->orderBy('name')->get();
        $sessions = Session::where('school_id', $this->school_id)->orderByDesc('id')->get();

        return view('admin.live_class.edit', compact('liveClass', 'subjects', 'classList', 'programmes', 'sessions'));
    }

    public function update(UpdateLiveClassRequest $request, LiveClass $liveClass)
    {
        abort_unless((int) $liveClass->school_id === (int) $this->school_id, 404);
        $this->authorize('update', $liveClass);

        $payload = $this->buildPayload($request->validated(), $liveClass);

        DB::transaction(function () use ($liveClass, $payload) {
            $liveClass->update($payload);
            AuditLog::record('update', 'Live Classes', "Updated live class: {$liveClass->title}");
        });

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => get_phrase('Live class updated'),
                'redirect' => route('admin.live_classes.show', $liveClass->id),
            ]);
        }

        return redirect()->route('admin.live_classes.show', $liveClass->id)
            ->with('success', get_phrase('Live class updated'));
    }

    public function destroy(LiveClass $liveClass)
    {
        abort_unless((int) $liveClass->school_id === (int) $this->school_id, 404);
        $this->authorize('delete', $liveClass);

        AuditLog::record('delete', 'Live Classes', "Deleted live class: {$liveClass->title}");
        $liveClass->delete();
        return redirect()->back()->with('success', get_phrase('Live class deleted'));
    }

    public function cancel(LiveClass $liveClass)
    {
        abort_unless((int) $liveClass->school_id === (int) $this->school_id, 404);
        $this->authorize('cancel', $liveClass);

        $liveClass->update([
            'status' => LiveClass::STATUS_CANCELLED,
            'updated_by' => Auth::id(),
        ]);

        AuditLog::record('update', 'Live Classes', "Cancelled live class: {$liveClass->title}");
        return redirect()->back()->with('success', get_phrase('Live class cancelled'));
    }

    public function publish(LiveClass $liveClass)
    {
        abort_unless((int) $liveClass->school_id === (int) $this->school_id, 404);
        $this->authorize('publish', $liveClass);

        $isPublished = !$liveClass->is_published;
        $nextStatus = $isPublished
            ? $this->deriveStatus($liveClass->status, $liveClass->scheduled_at, $liveClass->ends_at, true)
            : LiveClass::STATUS_DRAFT;

        $liveClass->update([
            'is_published' => $isPublished,
            'status' => $nextStatus,
            'updated_by' => Auth::id(),
        ]);

        AuditLog::record('update', 'Live Classes', ($isPublished ? 'Published' : 'Unpublished') . " live class: {$liveClass->title}");
        return redirect()->back()->with('success', get_phrase('Live class publication updated'));
    }

    public function join(LiveClass $liveClass)
    {
        abort_unless((int) $liveClass->school_id === (int) $this->school_id, 404);
        $this->authorize('join', $liveClass);

        if ((int) Auth::user()->role_id === 7 && !$this->canStudentAccessClass($liveClass)) {
            return redirect()->back()->with('error', get_phrase('You are not authorized for this class'));
        }

        if ($liveClass->shouldAllowJoin()) {
            return redirect()->away($liveClass->safe_meeting_url);
        }

        if ($liveClass->computed_status === LiveClass::STATUS_ENDED && $liveClass->safe_recording_url) {
            return redirect()->away($liveClass->safe_recording_url);
        }

        return redirect()->back()->with('error', get_phrase('Joining is not available for this class right now'));
    }

    public function studentIndex(Request $request)
    {
        $this->authorize('viewAny', LiveClass::class);

        $school_id = Auth::user()->school_id;
        $student_id = Auth::id();
        $enroll = \App\Models\Enrollment::where('user_id', $student_id)
            ->where('school_id', $school_id)
            ->first();
        $class_id = $enroll?->class_id;

        $search = trim((string) $request->input('search', ''));
        $subjectId = $request->input('subject_id');
        $platform = $request->input('platform');
        $status = $request->input('status');
        $date = $request->input('date');

        $classes = LiveClass::where('school_id', $school_id)
            ->published()
            ->where('status', '!=', LiveClass::STATUS_CANCELLED)
            ->where(function ($q) use ($class_id) {
                $q->whereNull('class_id')
                    ->orWhere('class_id', $class_id);
            })
            ->when($search !== '', fn($q) => $q->where('title', 'like', "%{$search}%"))
            ->when($subjectId, fn($q) => $q->where('subject_id', $subjectId))
            ->when($platform, fn($q) => $q->where('platform', $platform))
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($date, fn($q) => $q->whereDate('start_date', $date))
            ->with(['subject', 'teacher'])
            ->orderByDesc('start_date')
            ->orderByDesc('start_time')
            ->paginate(18);

        $subjects = Subject::where('school_id', $school_id)->orderBy('name')->get();

        return view('student.live_class.index', compact('classes', 'subjects', 'search', 'subjectId', 'platform', 'status', 'date'));
    }

    private function buildPayload(array $validated, ?LiveClass $existing): array
    {
        $enabledPlatforms = $this->getEnabledPlatforms();
        if (!in_array($validated['platform'], $enabledPlatforms, true)) {
            throw ValidationException::withMessages([
                'platform' => get_phrase('Selected platform is disabled by administrator settings.'),
            ]);
        }

        $scheduledAt = Carbon::parse($validated['start_date'] . ' ' . $validated['start_time'], $validated['timezone'] ?? config('app.timezone', 'UTC'));
        $endsAt = Carbon::parse($validated['start_date'] . ' ' . $validated['end_time'], $validated['timezone'] ?? config('app.timezone', 'UTC'));

        $meetingUrl = $validated['meeting_url'] ?? null;
        if (($validated['platform'] ?? 'jitsi') === 'jitsi' && empty($meetingUrl)) {
            $base = rtrim((string) get_settings('live_class_jitsi_base_url'), '/');
            if ($base === '') {
                $base = 'https://meet.jit.si';
            }
            $room = Str::slug(($validated['title'] ?? 'class') . '-' . Str::random(8));
            $meetingUrl = $base . '/' . $room;
        }

        $isPublished = array_key_exists('is_published', $validated)
            ? (bool) $validated['is_published']
            : (bool) ($existing?->is_published ?? false);

        $statusInput = $validated['status'] ?? ($existing?->status ?? LiveClass::STATUS_DRAFT);
        $status = $this->deriveStatus($statusInput, $scheduledAt, $endsAt, $isPublished);

        return [
            'school_id' => $this->school_id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'subject_id' => $validated['subject_id'] ?? null,
            'class_id' => $validated['class_id'] ?? null,
            'programme_id' => $validated['programme_id'] ?? null,
            'academic_session_id' => $validated['academic_session_id'] ?? null,
            'teacher_id' => $validated['teacher_id'] ?? Auth::id(),
            'platform' => $validated['platform'],
            'meeting_url' => $meetingUrl,
            'meeting_id' => $validated['meeting_id'] ?? null,
            'meeting_password' => $validated['meeting_password'] ?? null,
            'start_date' => $validated['start_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'timezone' => $validated['timezone'] ?? config('app.timezone', 'UTC'),
            'scheduled_at' => $scheduledAt->timezone('UTC')->format('Y-m-d H:i:s'),
            'ends_at' => $endsAt->timezone('UTC')->format('Y-m-d H:i:s'),
            'status' => $status,
            'is_published' => $isPublished,
            'attendance_enabled' => !empty($validated['attendance_enabled']) ? 1 : 0,
            'recording_url' => $validated['recording_url'] ?? null,
            'created_by' => $existing?->created_by ?: Auth::id(),
            'updated_by' => Auth::id(),
        ];
    }

    private function deriveStatus(string $inputStatus, Carbon $scheduledAt, Carbon $endsAt, bool $isPublished): string
    {
        if ($inputStatus === LiveClass::STATUS_CANCELLED) {
            return LiveClass::STATUS_CANCELLED;
        }

        if (!$isPublished) {
            return LiveClass::STATUS_DRAFT;
        }

        $now = now()->timezone('UTC');
        if ($now->greaterThan($endsAt->copy()->timezone('UTC'))) {
            return LiveClass::STATUS_ENDED;
        }

        if ($now->betweenIncluded($scheduledAt->copy()->timezone('UTC'), $endsAt->copy()->timezone('UTC'))) {
            return LiveClass::STATUS_LIVE;
        }

        return LiveClass::STATUS_SCHEDULED;
    }

    private function canManageAll($user): bool
    {
        return in_array((int) $user->role_id, [1, 2, 10, 12, 14], true);
    }

    private function getAllowedSubjects()
    {
        $query = Subject::where('school_id', $this->school_id);

        if ((int) Auth::user()->role_id === 3 && !$this->canManageAll(Auth::user())) {
            $classIds = TeacherPermission::where('school_id', $this->school_id)
                ->where('teacher_id', Auth::id())
                ->pluck('class_id')
                ->unique();

            if ($classIds->isNotEmpty()) {
                $query->whereIn('class_id', $classIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query->orderBy('name')->get();
    }

    private function canStudentAccessClass(LiveClass $liveClass): bool
    {
        $enroll = \App\Models\Enrollment::where('user_id', Auth::id())
            ->where('school_id', $this->school_id)
            ->first();

        if (!$enroll) {
            return false;
        }

        if ($liveClass->class_id && (int) $liveClass->class_id !== (int) $enroll->class_id) {
            return false;
        }

        return (bool) $liveClass->is_published;
    }

    private function getEnabledPlatforms(): array
    {
        $map = [
            'jitsi' => get_settings('live_class_platform_jitsi') !== '0',
            'google_meet' => get_settings('live_class_platform_google_meet') !== '0',
            'zoom' => get_settings('live_class_platform_zoom') !== '0',
            'bigbluebutton' => get_settings('live_class_platform_bigbluebutton') === '1',
            'custom' => get_settings('live_class_platform_custom') === '1',
        ];

        $enabled = [];
        foreach ($map as $platform => $isEnabled) {
            if ($isEnabled) {
                $enabled[] = $platform;
            }
        }

        return $enabled ?: ['jitsi', 'google_meet', 'zoom'];
    }
}
