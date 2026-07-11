<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLiveClassRequest;
use App\Http\Requests\UpdateLiveClassRequest;
use App\Models\AuditLog;
use App\Models\Classes;
use App\Models\LiveClass;
use App\Models\Noticeboard;
use App\Models\Programme;
use App\Models\Session;
use App\Models\Subject;
use App\Models\TeacherPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
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
            $this->createStudentLiveClassNotice($record, 'scheduled');
            return $record;
        });

        if ($request->expectsJson() || $request->ajax()) {
            $routePrefix = $this->getRoutePrefix($request);
            return response()->json([
                'status' => 'success',
                'message' => get_phrase('Live class scheduled'),
                'redirect' => route($routePrefix . '.live_classes.show', $liveClass->id),
            ]);
        }

        $routePrefix = $this->getRoutePrefix($request);
        return redirect()->route($routePrefix . '.live_classes.show', $liveClass->id)
            ->with('success', get_phrase('Live class scheduled'));
    }

    public function meetNow(Request $request)
    {
        $this->authorize('create', LiveClass::class);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'class_id' => ['nullable', 'exists:classes,id'],
            'programme_id' => ['nullable', 'exists:programmes,id'],
            'academic_session_id' => ['nullable', 'exists:sessions,id'],
            'platform' => ['nullable', 'in:jitsi,google_meet,zoom'],
        ]);

        $platform = $validated['platform'] ?? 'jitsi';
        if (!in_array($platform, $this->getEnabledPlatforms(), true)) {
            throw ValidationException::withMessages([
                'platform' => get_phrase('Selected platform is disabled by administrator settings.'),
            ]);
        }

        $subject = null;
        if (!empty($validated['subject_id'])) {
            $subject = Subject::where('id', $validated['subject_id'])
                ->where('school_id', $this->school_id)
                ->first();

            if (!$subject) {
                throw ValidationException::withMessages([
                    'subject_id' => get_phrase('Selected course is invalid for this school.'),
                ]);
            }
        }

        $classId = $validated['class_id'] ?? null;
        if ($classId) {
            $classExists = Classes::where('id', $classId)
                ->where('school_id', $this->school_id)
                ->exists();

            if (!$classExists) {
                throw ValidationException::withMessages([
                    'class_id' => get_phrase('Selected class is invalid for this school.'),
                ]);
            }
        }

        if ($subject && $subject->class_id && $classId && (int) $subject->class_id !== (int) $classId) {
            throw ValidationException::withMessages([
                'subject_id' => get_phrase('Selected course does not belong to the selected class.'),
            ]);
        }

        if (!$classId && $subject && $subject->class_id) {
            $classId = (int) $subject->class_id;
        }

        $sessionId = $validated['academic_session_id'] ?? null;
        if ($sessionId) {
            $sessionExists = Session::where('id', $sessionId)
                ->where('school_id', $this->school_id)
                ->exists();

            if (!$sessionExists) {
                throw ValidationException::withMessages([
                    'academic_session_id' => get_phrase('Selected session is invalid for this school.'),
                ]);
            }
        }

        $programmeId = $validated['programme_id'] ?? null;
        if ($programmeId) {
            $programmeExists = Programme::where('id', $programmeId)
                ->where('school_id', $this->school_id)
                ->exists();

            if (!$programmeExists) {
                throw ValidationException::withMessages([
                    'programme_id' => get_phrase('Selected programme is invalid for this school.'),
                ]);
            }
        }

        $now = now();
        $endsAt = $now->copy()->addHour();
        $title = trim((string) ($validated['title'] ?? 'Instant Live Class ' . $now->format('H:i')));
        $meetingUrl = $this->resolveMeetingUrl(
            $platform,
            $title,
            $now,
            $endsAt,
            config('app.timezone', 'UTC')
        );

        $payload = [
            'school_id' => $this->school_id,
            'title' => $title,
            'description' => 'Instant meeting created by ' . (Auth::user()->name ?? 'staff'),
            'subject_id' => $subject?->id,
            'class_id' => $classId,
            'programme_id' => $programmeId,
            'academic_session_id' => $sessionId,
            'teacher_id' => Auth::id(),
            'platform' => $platform,
            'meeting_url' => $meetingUrl,
            'meeting_id' => null,
            'meeting_password' => null,
            'start_date' => $now->format('Y-m-d'),
            'start_time' => $now->format('H:i'),
            'end_time' => $endsAt->format('H:i'),
            'timezone' => config('app.timezone', 'UTC'),
            'scheduled_at' => $now->timezone('UTC')->format('Y-m-d H:i:s'),
            'ends_at' => $endsAt->timezone('UTC')->format('Y-m-d H:i:s'),
            'status' => LiveClass::STATUS_LIVE,
            'is_published' => 1,
            'attendance_enabled' => 1,
            'recording_url' => null,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ];

        $liveClass = DB::transaction(function () use ($payload) {
            $record = LiveClass::create($payload);
            AuditLog::record('create', 'Live Classes', "Started instant live class: {$record->title}");
            $this->createStudentLiveClassNotice($record, 'published');
            return $record;
        });

        $routePrefix = $this->getRoutePrefix($request);
        return redirect()->route($routePrefix . '.live_classes.join', $liveClass->id);
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
            $routePrefix = $this->getRoutePrefix($request);
            return response()->json([
                'status' => 'success',
                'message' => get_phrase('Live class updated'),
                'redirect' => route($routePrefix . '.live_classes.show', $liveClass->id),
            ]);
        }

        $routePrefix = $this->getRoutePrefix($request);
        return redirect()->route($routePrefix . '.live_classes.show', $liveClass->id)
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

        if ($isPublished) {
            $this->createStudentLiveClassNotice($liveClass, 'published');
        }

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
            if ($this->shouldRenderEmbeddedMeeting($liveClass)) {
                return view('admin.live_class.meeting_room', [
                    'liveClass' => $liveClass,
                    'meetingUrl' => $liveClass->safe_meeting_url,
                ]);
            }

            return redirect()->away($liveClass->safe_meeting_url);
        }

        if ($liveClass->computed_status === LiveClass::STATUS_ENDED && $liveClass->safe_recording_url) {
            return redirect()->away($liveClass->safe_recording_url);
        }

        return redirect()->back()->with('error', get_phrase('Joining is not available for this class right now'));
    }

    private function shouldRenderEmbeddedMeeting(LiveClass $liveClass): bool
    {
        if ($liveClass->platform !== 'jitsi') {
            return false;
        }

        $meetingUrl = $liveClass->safe_meeting_url;
        if (empty($meetingUrl)) {
            return false;
        }

        $meetingHost = parse_url($meetingUrl, PHP_URL_HOST);
        if (empty($meetingHost)) {
            return false;
        }

        $base = rtrim((string) get_settings('live_class_jitsi_base_url'), '/');
        if ($base === '') {
            $base = 'https://meet.jit.si';
        }

        $configuredHost = parse_url($base, PHP_URL_HOST);
        if (empty($configuredHost)) {
            $configuredHost = 'meet.jit.si';
        }

        return strcasecmp($meetingHost, $configuredHost) === 0;
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
            ->where(function ($q) use ($enroll) {
                $q->whereNull('academic_session_id');
                if (!empty($enroll?->session_id)) {
                    $q->orWhere('academic_session_id', $enroll->session_id);
                }
            })
            ->where(function ($q) use ($class_id, $school_id) {
                $q->whereNull('subject_id')
                    ->orWhereHas('subject', function ($subQ) use ($class_id, $school_id) {
                        $subQ->where('school_id', $school_id)
                            ->where(function ($clsQ) use ($class_id) {
                                $clsQ->whereNull('class_id');
                                if (!empty($class_id)) {
                                    $clsQ->orWhere('class_id', $class_id);
                                }
                            });
                    });
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

        $meetingUrl = $validated['meeting_url'] ?? ($existing?->meeting_url ?? null);
        if (empty($meetingUrl)) {
            $platform = $validated['platform'] ?? 'jitsi';
            $meetingUrl = $this->resolveMeetingUrl(
                $platform,
                $validated['title'] ?? 'class',
                $scheduledAt,
                $endsAt,
                $validated['timezone'] ?? config('app.timezone', 'UTC')
            );
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

    private function getRoutePrefix(Request $request): string
    {
        return $request->routeIs('teacher.*') ? 'teacher' : 'admin';
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

        if ($liveClass->academic_session_id && (int) $liveClass->academic_session_id !== (int) $enroll->session_id) {
            return false;
        }

        if ($liveClass->subject_id) {
            $subject = Subject::where('id', $liveClass->subject_id)
                ->where('school_id', $this->school_id)
                ->first();

            if (!$subject) {
                return false;
            }

            if ($subject->class_id && (int) $subject->class_id !== (int) $enroll->class_id) {
                return false;
            }
        }

        return (bool) $liveClass->is_published;
    }

    private function generateMeetingUrl(string $platform, string $title): string
    {
        if ($platform === 'jitsi') {
            $base = rtrim((string) get_settings('live_class_jitsi_base_url'), '/');
            if ($base === '') {
                $base = 'https://meet.jit.si';
            }

            return $base . '/' . Str::slug($title . '-' . Str::random(8));
        }

        return '';
    }

    private function resolveMeetingUrl(string $platform, string $title, Carbon $scheduledAt, Carbon $endsAt, string $timezone): string
    {
        if ($platform === 'jitsi') {
            return $this->generateMeetingUrl('jitsi', $title);
        }

        if ($platform === 'zoom') {
            $url = $this->createZoomMeetingUrl($title, $scheduledAt, $endsAt, $timezone);
            if (!empty($url)) {
                return $url;
            }

            throw ValidationException::withMessages([
                'meeting_url' => get_phrase('Zoom API is not configured. Add ZOOM_ACCOUNT_ID, ZOOM_CLIENT_ID, and ZOOM_CLIENT_SECRET in your .env file.'),
            ]);
        }

        if ($platform === 'google_meet') {
            $url = $this->createGoogleMeetUrl($title, $scheduledAt, $endsAt, $timezone);
            if (!empty($url)) {
                return $url;
            }

            throw ValidationException::withMessages([
                'meeting_url' => get_phrase('Google Meet API is not configured. Add GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, and GOOGLE_REFRESH_TOKEN in your .env file.'),
            ]);
        }

        throw ValidationException::withMessages([
            'platform' => get_phrase('Automatic link generation is supported only for Jitsi, Zoom API, or Google Meet API.'),
        ]);
    }

    private function createZoomMeetingUrl(string $title, Carbon $scheduledAt, Carbon $endsAt, string $timezone): ?string
    {
        $accountId = (string) config('services.zoom.account_id');
        $clientId = (string) config('services.zoom.client_id');
        $clientSecret = (string) config('services.zoom.client_secret');

        if ($accountId === '' || $clientId === '' || $clientSecret === '') {
            return null;
        }

        $tokenResponse = Http::asForm()
            ->withBasicAuth($clientId, $clientSecret)
            ->post('https://zoom.us/oauth/token', [
                'grant_type' => 'account_credentials',
                'account_id' => $accountId,
            ]);

        if (!$tokenResponse->successful()) {
            return null;
        }

        $accessToken = (string) $tokenResponse->json('access_token');
        if ($accessToken === '') {
            return null;
        }

        $duration = max(1, $scheduledAt->diffInMinutes($endsAt));
        $meetingResponse = Http::withToken($accessToken)
            ->acceptJson()
            ->post('https://api.zoom.us/v2/users/me/meetings', [
                'topic' => $title,
                'type' => 2,
                'start_time' => $scheduledAt->copy()->timezone('UTC')->toIso8601String(),
                'duration' => $duration,
                'timezone' => $timezone,
                'settings' => [
                    'join_before_host' => true,
                    'waiting_room' => false,
                ],
            ]);

        if (!$meetingResponse->successful()) {
            return null;
        }

        $joinUrl = (string) $meetingResponse->json('join_url');
        return $joinUrl !== '' ? $joinUrl : null;
    }

    private function createGoogleMeetUrl(string $title, Carbon $scheduledAt, Carbon $endsAt, string $timezone): ?string
    {
        $clientId = (string) config('services.google_meet.client_id');
        $clientSecret = (string) config('services.google_meet.client_secret');
        $refreshToken = (string) config('services.google_meet.refresh_token');
        $calendarId = (string) config('services.google_meet.calendar_id', 'primary');

        if ($clientId === '' || $clientSecret === '' || $refreshToken === '') {
            return null;
        }

        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (!$tokenResponse->successful()) {
            return null;
        }

        $accessToken = (string) $tokenResponse->json('access_token');
        if ($accessToken === '') {
            return null;
        }

        $eventResponse = Http::withToken($accessToken)
            ->acceptJson()
            ->post('https://www.googleapis.com/calendar/v3/calendars/' . urlencode($calendarId) . '/events?conferenceDataVersion=1', [
                'summary' => $title,
                'start' => [
                    'dateTime' => $scheduledAt->copy()->setTimezone($timezone)->toIso8601String(),
                    'timeZone' => $timezone,
                ],
                'end' => [
                    'dateTime' => $endsAt->copy()->setTimezone($timezone)->toIso8601String(),
                    'timeZone' => $timezone,
                ],
                'conferenceData' => [
                    'createRequest' => [
                        'requestId' => (string) Str::uuid(),
                        'conferenceSolutionKey' => [
                            'type' => 'hangoutsMeet',
                        ],
                    ],
                ],
            ]);

        if (!$eventResponse->successful()) {
            return null;
        }

        $hangoutLink = (string) $eventResponse->json('hangoutLink');
        if ($hangoutLink !== '') {
            return $hangoutLink;
        }

        $entryPoints = (array) $eventResponse->json('conferenceData.entryPoints', []);
        foreach ($entryPoints as $entryPoint) {
            $entryUri = (string) ($entryPoint['uri'] ?? '');
            if ($entryUri !== '') {
                return $entryUri;
            }
        }

        return null;
    }

    private function createStudentLiveClassNotice(LiveClass $liveClass, string $eventType): void
    {
        $liveClass->loadMissing(['subject', 'classRoom', 'academicSession']);

        $classInfo = $liveClass->class_id
            ? ('Class: ' . (optional($liveClass->classRoom)->name ?: ('ID ' . $liveClass->class_id)))
            : 'Class: All classes';
        $subjectName = optional($liveClass->subject)->name ?: 'All courses';
        $sessionInfo = $liveClass->academic_session_id
            ? ('Session: ' . (optional($liveClass->academicSession)->session_title ?: ('ID ' . $liveClass->academic_session_id)))
            : 'Session: All sessions';
        $actionLabel = $eventType === 'published' ? 'published' : 'scheduled';

        $noticeTitle = 'Live Class ' . ucfirst($actionLabel) . ': ' . $liveClass->title;
        $noticeBody = "A live class has been {$actionLabel}.\n"
            . "Course: {$subjectName}\n"
            . "{$classInfo}\n"
            . "{$sessionInfo}\n"
            . "Date: " . optional($liveClass->start_date)->format('Y-m-d') . "\n"
            . "Time: {$liveClass->start_time} - {$liveClass->end_time}\n"
            . "Join Link: " . ($liveClass->meeting_url ?: 'TBD');

        $sessionId = (int) get_school_settings($this->school_id)->value('running_session');
        if ($sessionId === 0) {
            $sessionId = (int) Session::where('school_id', $this->school_id)->max('id');
        }

        Noticeboard::create([
            'notice_title' => $noticeTitle,
            'notice' => $noticeBody,
            'start_date' => optional($liveClass->start_date)->format('Y-m-d') ?: now()->format('Y-m-d'),
            'start_time' => (string) ($liveClass->start_time ?: ''),
            'end_date' => optional($liveClass->start_date)->format('Y-m-d') ?: now()->format('Y-m-d'),
            'end_time' => (string) ($liveClass->end_time ?: ''),
            'status' => 1,
            'show_on_website' => 0,
            'image' => '',
            'school_id' => $this->school_id,
            'session_id' => $sessionId > 0 ? $sessionId : 0,
        ]);
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
