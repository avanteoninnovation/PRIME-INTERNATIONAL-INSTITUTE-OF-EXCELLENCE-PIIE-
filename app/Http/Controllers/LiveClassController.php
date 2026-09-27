<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLiveClassRequest;
use App\Http\Requests\StoreOfferingLiveClassRequest;
use App\Http\Requests\UpdateLiveClassRequest;
use App\Models\AuditLog;
use App\Models\Classes;
use App\Models\CourseOffering;
use App\Models\LiveClass;
use App\Models\LiveClassAttendance;
use App\Models\LiveClassMaterial;
use App\Models\LiveClassMeetGuest;
use App\Models\Noticeboard;
use App\Models\Programme;
use App\Models\Session;
use App\Models\Subject;
use App\Models\TeacherPermission;
use App\Models\TeacherProgrammeAssignment;
use App\Support\LiveClasses\JitsiTokenService;
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
        $access = app(\App\Support\LiveClasses\LiveClassAccessService::class);

        $search = trim((string) $request->input('search', ''));
        $subjectId = $request->input('subject_id');
        $platform = $request->input('platform');
        $status = $request->input('status');
        $date = $request->input('date');
        // Quick-filter tab: without one, the queue would otherwise grow to
        // every class ever scheduled, most of it long finished and no
        // longer actionable. "upcoming" (drafts/scheduled/live, i.e. not yet
        // ended) is the default; staff explicitly asks for "completed",
        // "cancelled" or "all" to see the rest.
        $view = $request->input('view', $status ? 'all' : 'upcoming');

        $classes = LiveClass::query()
            ->where('school_id', $this->school_id)
            ->where(function ($scope) use ($access) {
                $scope->where(function ($legacy) {
                    $legacy->whereNull('course_offering_id')
                        ->when(!$this->canManageAll(Auth::user()), fn ($query) => $query->where(function ($owner) {
                            $owner->where('teacher_id', Auth::id())->orWhere('created_by', Auth::id());
                        }));
                });
                if ($access->canViewAllOfferingClasses(Auth::user(), (int) $this->school_id)) {
                    $scope->orWhereNotNull('course_offering_id');
                } else {
                    $scope->orWhereIn('id', $access->lecturerVisibleClassIdsQuery(Auth::user(), (int) $this->school_id));
                }
            })
            ->when($search !== '', fn($q) => $q->where('title', 'like', "%{$search}%"))
            ->when($subjectId, fn($q) => $q->where('subject_id', $subjectId))
            ->when($platform, fn($q) => $q->where('platform', $platform))
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($date, fn($q) => $q->whereDate('start_date', $date))
            // The explicit status dropdown is the more precise instrument;
            // the view tab's coarse date-window logic only applies when the
            // caller hasn't already pinned an exact status.
            ->when(!$status, fn($q) => $this->applyQuickView($q, $view))
            ->with(['subject', 'teacher', 'programme', 'academicSession'])
            ->orderByDesc('start_date')
            ->orderByDesc('start_time')
            ->paginate(20);

        $subjects = $this->getAllowedSubjects();
        $classList = Classes::where('school_id', $this->school_id)->orderBy('name')->get();
        $programmes = Programme::where('school_id', $this->school_id)->where('is_active', 1)->orderBy('name')->get();
        $sessions = Session::where('school_id', $this->school_id)->orderByDesc('id')->get();
        $platformStatus = $this->platformConfigurationStatus();
        $defaultPlatform = $this->defaultPlatform();

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
            'date',
            'platformStatus',
            'defaultPlatform',
            'view'
        ));
    }

    /**
     * The four quick-filter tabs shown above the table/cards. Kept as one
     * method so the admin/teacher queue and the student listing can never
     * define "upcoming" or "completed" differently.
     */
    private function applyQuickView($query, string $view)
    {
        return match ($view) {
            'live' => $query->active(),
            'completed' => $query->where('status', '!=', LiveClass::STATUS_CANCELLED)
                ->where(function ($q) {
                    $q->where('status', LiveClass::STATUS_ENDED)
                        ->orWhere(function ($sub) {
                            $sub->whereNotNull('ends_at')->where('ends_at', '<', now());
                        });
                }),
            'cancelled' => $query->where('status', LiveClass::STATUS_CANCELLED),
            'all' => $query,
            // 'upcoming' and anything unrecognised: not yet finished and not
            // cancelled — drafts, scheduled and currently-live classes.
            default => $query->where('status', '!=', LiveClass::STATUS_CANCELLED)
                ->where(function ($q) {
                    $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                }),
        };
    }

    public function create()
    {
        $this->authorize('create', LiveClass::class);

        $liveClass = new LiveClass([
            'platform' => $this->defaultPlatform(),
            'status' => LiveClass::STATUS_DRAFT,
            'timezone' => config('app.timezone', 'UTC'),
            'is_published' => false,
        ]);

        $subjects = $this->getAllowedSubjects();
        $classList = Classes::where('school_id', $this->school_id)->orderBy('name')->get();
        $programmes = Programme::where('school_id', $this->school_id)->where('is_active', 1)->orderBy('name')->get();
        $sessions = Session::where('school_id', $this->school_id)->orderByDesc('id')->get();
        $platformStatus = $this->platformConfigurationStatus();

        return view('admin.live_class.create', compact('liveClass', 'subjects', 'classList', 'programmes', 'sessions', 'platformStatus'));
    }

    public function openModal(Request $request)
    {
        $id = $request->id;
        if ($id) {
            $liveClass = LiveClass::where('school_id', $this->school_id)->findOrFail($id);
            $this->authorizeClassManage($liveClass);
        } else {
            $this->authorize('create', LiveClass::class);
            $liveClass = new LiveClass([
                'platform' => $this->defaultPlatform(),
                'status' => LiveClass::STATUS_DRAFT,
                'timezone' => config('app.timezone', 'UTC'),
                'is_published' => false,
            ]);
        }

        $subjects = $this->getAllowedSubjects();
        $classList = Classes::where('school_id', $this->school_id)->orderBy('name')->get();
        $programmes = Programme::where('school_id', $this->school_id)->where('is_active', 1)->orderBy('name')->get();
        $sessions = Session::where('school_id', $this->school_id)->orderByDesc('id')->get();
        $platformStatus = $this->platformConfigurationStatus();

        return view('admin.live_class.modal', compact('liveClass', 'subjects', 'classList', 'programmes', 'sessions', 'platformStatus'));
    }

    public function store(StoreLiveClassRequest $request)
    {
        $this->authorize('create', LiveClass::class);
        $this->rejectOfferingContextOnLegacyWorkflow($request);

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
        $this->rejectOfferingContextOnLegacyWorkflow($request);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'class_id' => ['nullable', 'exists:classes,id'],
            'programme_id' => ['nullable', 'exists:programmes,id'],
            'academic_session_id' => ['nullable', 'exists:sessions,id'],
            'platform' => ['nullable', 'in:jitsi,google_meet,zoom'],
        ]);

        $platform = $validated['platform'] ?? $this->defaultPlatform();
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
        $this->authorizeClassView($liveClass);

        $liveClass->load(['subject', 'teacher', 'programme', 'academicSession', 'creator']);
        return view('admin.live_class.show', compact('liveClass'));
    }

    public function edit(LiveClass $liveClass)
    {
        abort_unless((int) $liveClass->school_id === (int) $this->school_id, 404);
        $this->authorizeClassManage($liveClass);

        $subjects = $this->getAllowedSubjects();
        $classList = Classes::where('school_id', $this->school_id)->orderBy('name')->get();
        $programmes = Programme::where('school_id', $this->school_id)->where('is_active', 1)->orderBy('name')->get();
        $sessions = Session::where('school_id', $this->school_id)->orderByDesc('id')->get();
        $platformStatus = $this->platformConfigurationStatus();

        return view('admin.live_class.edit', compact('liveClass', 'subjects', 'classList', 'programmes', 'sessions', 'platformStatus'));
    }

    public function update(UpdateLiveClassRequest $request, LiveClass $liveClass)
    {
        abort_unless((int) $liveClass->school_id === (int) $this->school_id, 404);
        $this->authorizeClassManage($liveClass);

        if ($liveClass->course_offering_id !== null) {
            $validated = $request->validated();
            $payload = $this->buildPayload($validated, $liveClass);
            if (! array_key_exists('teacher_id', $validated)) {
                $payload['teacher_id'] = $liveClass->teacher_id;
            }
            $meetingFields = array_intersect_key($payload, array_flip([
                'title', 'description', 'teacher_id', 'platform', 'meeting_url', 'meeting_id',
                'meeting_password', 'scheduled_at', 'ends_at', 'start_date', 'start_time',
                'end_time', 'timezone', 'status', 'is_published', 'attendance_enabled', 'recording_url',
            ]));
            try {
                app(\App\Support\LiveClasses\LiveClassService::class)
                    ->updateOfferingMeeting(Auth::user(), (int) $liveClass->id, $meetingFields);
            } catch (\DomainException $exception) {
                throw ValidationException::withMessages(['teacher_id' => get_phrase($exception->getMessage())]);
            }

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
        $this->authorizeClassManage($liveClass);
        abort_if($liveClass->course_offering_id !== null, 403, get_phrase('Offering-backed Live Classes are preserved; cancel the meeting instead.'));

        AuditLog::record('delete', 'Live Classes', "Deleted live class: {$liveClass->title}");
        $liveClass->delete();
        return redirect()->back()->with('success', get_phrase('Live class deleted'));
    }

    public function cancel(LiveClass $liveClass)
    {
        abort_unless((int) $liveClass->school_id === (int) $this->school_id, 404);
        $this->authorizeClassManage($liveClass);

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
        $this->authorizeClassManage($liveClass);

        $isPublished = !$liveClass->is_published;
        $nextStatus = $isPublished
            ? $this->deriveStatus($liveClass->status, $liveClass->scheduled_at, $liveClass->ends_at, true)
            : LiveClass::STATUS_DRAFT;

        DB::transaction(function () use ($liveClass, $isPublished, $nextStatus): void {
            $liveClass->update([
                'is_published' => $isPublished,
                'status' => $nextStatus,
                'updated_by' => Auth::id(),
            ]);

            if ($isPublished) {
                $this->createStudentLiveClassNotice($liveClass, 'published');
            }

            AuditLog::record('update', 'Live Classes', ($isPublished ? 'Published' : 'Unpublished') . " live class: {$liveClass->title}");
        });

        return redirect()->back()->with('success', get_phrase('Live class publication updated'));
    }

    public function join(LiveClass $liveClass)
    {
        abort_unless((int) $liveClass->school_id === (int) $this->school_id, 404);
        $access = app(\App\Support\LiveClasses\LiveClassAccessService::class);
        if ($access->isOfferingBacked($liveClass)) {
            $user = Auth::user();
            $isStudent = (int) $user->role_id === 7;
            $canJoin = $isStudent
                ? $access->canStudentJoin($user, $liveClass)
                : ($access->canTenantAdminJoin($user, $liveClass)
                    || $access->canLecturerJoin($user, $liveClass));
            if (! $canJoin) {
                if ($isStudent && ! $access->confirmedStudentRegistration($user, $liveClass)) {
                    return redirect()->back()->with('error', get_phrase('A confirmed registration for this Course Offering is required.'));
                }
                return redirect()->back()->with('error', get_phrase('Joining is not available for this meeting right now.'));
            }
            if (! $liveClass->safe_meeting_url) {
                return redirect()->back()->with('error', get_phrase('The meeting provider link is unavailable.'));
            }
        } else {
            $this->authorize('join', $liveClass);

            if ((int) Auth::user()->role_id === 7 && !$this->canStudentAccessClass($liveClass)) {
                return redirect()->back()->with('error', get_phrase('You are not authorized for this class'));
            }
        }

        $offeringBacked = $access->isOfferingBacked($liveClass);
        if (($offeringBacked && $access->withinJoinWindow($liveClass)) || (!$offeringBacked && $liveClass->shouldAllowJoin())) {
            $attendance = $this->recordJoin($liveClass);

            if ($this->shouldRenderEmbeddedMeeting($liveClass)) {
                $isModerator = $offeringBacked
                    ? ($access->canLecturerHost(Auth::user(), $liveClass) || $access->canTenantAdmin(Auth::user(), $liveClass, 'live_classes.manage_all'))
                    : Auth::user()->can('update', $liveClass);
                $jitsiJwt = JitsiTokenService::generate($liveClass, Auth::user(), $isModerator);
                $meetingUrl = $liveClass->safe_meeting_url;

                return view('admin.live_class.meeting_room', [
                    'liveClass' => $liveClass,
                    'meetingUrl' => $meetingUrl,
                    'attendanceId' => $attendance?->id,
                    'isModerator' => $isModerator,
                    'jitsiJwt' => $jitsiJwt,
                    'jitsiConfigured' => JitsiTokenService::isConfigured(),
                    'displayName' => Auth::user()->name,
                    // Everything after the domain: just the room slug on
                    // plain meet.jit.si, "{appId}/{room}" on 8x8 JaaS — the
                    // IFrame API's `roomName` option needs the full path,
                    // unlike the JWT's `room` claim (see JitsiTokenService).
                    'jitsiDomain' => parse_url($meetingUrl, PHP_URL_HOST),
                    'jitsiRoomPath' => trim((string) parse_url($meetingUrl, PHP_URL_PATH), '/'),
                ]);
            }

            if ($liveClass->platform === 'google_meet') {
                // Google Meet events created by this app are always owned by
                // one single, school-wide Google account (services.google_meet.
                // refresh_token — see createGoogleMeetUrl()), never the
                // individual teacher's own Google identity. Whoever opens the
                // link while signed into a *different* Google account in
                // their browser is not recognised as host and lands on
                // Meet's "Ask to join" knock screen instead of being let
                // straight in — surface that before sending them away, since
                // Meet's own UI gives no hint why.
                return view('admin.live_class.google_meet_join', [
                    'liveClass' => $liveClass,
                    'meetingUrl' => $liveClass->safe_meeting_url,
                ]);
            }

            return redirect()->away($liveClass->safe_meeting_url);
        }

        if (!$offeringBacked && $liveClass->computed_status === LiveClass::STATUS_ENDED && $liveClass->safe_recording_url) {
            return redirect()->away($liveClass->safe_recording_url);
        }

        return redirect()->back()->with('error', get_phrase('Joining is not available for this class right now'));
    }

    /**
     * One attendance row per click of Join, for students only — a lecturer
     * opening their own class isn't "attending" it. Only recorded when the
     * class has attendance tracking switched on (attendance_enabled), so
     * classes nobody asked to track don't accumulate rows regardless.
     */
    private function recordJoin(LiveClass $liveClass): ?LiveClassAttendance
    {
        if (!$liveClass->attendance_enabled || (int) Auth::user()->role_id !== 7) {
            return null;
        }

        return LiveClassAttendance::create([
            'school_id' => $liveClass->school_id,
            'live_class_id' => $liveClass->id,
            'user_id' => Auth::id(),
            'role_id' => Auth::user()->role_id,
            'joined_at' => now(),
        ]);
    }

    /**
     * Beacon fired by the embedded Jitsi room on page unload/close (see
     * meeting_room.blade.php). This is the only platform where "left" is
     * knowable at all — Zoom/Google Meet/BigBlueButton open away from this
     * app, so there is nothing here to hear a departure from.
     */
    public function attendanceLeave(Request $request, LiveClass $liveClass)
    {
        abort_unless((int) $liveClass->school_id === (int) $this->school_id, 404);

        $attendanceId = $request->input('attendance_id');

        $attendance = LiveClassAttendance::where('live_class_id', $liveClass->id)
            ->where('user_id', Auth::id())
            ->when($attendanceId, fn ($q) => $q->where('id', $attendanceId))
            ->whereNull('left_at')
            ->latest('id')
            ->first();

        if ($attendance) {
            $leftAt = now();
            $attendance->update([
                'left_at' => $leftAt,
                'duration_seconds' => max(0, $attendance->joined_at->diffInSeconds($leftAt)),
            ]);
        }

        // sendBeacon expects a fast, body-less response; nothing downstream
        // reads this.
        return response()->noContent();
    }

    /**
     * Who joined, when, and — where knowable — for how long. Reachable only
     * by staff who could otherwise manage the class (reuses the 'update'
     * ability rather than adding a new one purely for viewing this report).
     */
    public function attendance(LiveClass $liveClass)
    {
        abort_unless((int) $liveClass->school_id === (int) $this->school_id, 404);
        $this->authorizeClassManage($liveClass);

        $records = $liveClass->attendances()->with('user')->orderBy('joined_at')->get();

        return view('admin.live_class.attendance', [
            'liveClass' => $liveClass,
            'records' => $records,
        ]);
    }

    public function attendanceExport(LiveClass $liveClass)
    {
        abort_unless((int) $liveClass->school_id === (int) $this->school_id, 404);
        $this->authorizeClassManage($liveClass);

        $records = $liveClass->attendances()->with('user')->orderBy('joined_at')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="attendance_' . $liveClass->id . '_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($records) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['#', 'Name', 'Email', 'Joined At', 'Left At', 'Duration (minutes)']);
            $records->each(function ($record, $i) use ($out) {
                fputcsv($out, [
                    $i + 1,
                    optional($record->user)->name,
                    optional($record->user)->email,
                    $record->joined_at?->format('Y-m-d H:i:s'),
                    $record->left_at?->format('Y-m-d H:i:s') ?: 'Unknown',
                    $record->duration_seconds !== null ? round($record->duration_seconds / 60, 1) : 'Unknown',
                ]);
            });
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Class materials ──────────────────────────────────────────────────

    /**
     * Materials list, rendered as a modal partial — reachable by staff who
     * can manage the class (upload form included) and by anyone who can
     * view it (see LiveClassPolicy::view, which already lets a student see
     * a published class and blocks an unpublished one). Deliberately not
     * time-gated to the class window: reviewing slides after the session is
     * exactly when students want them.
     */
    public function materials(LiveClass $liveClass)
    {
        abort_unless((int) $liveClass->school_id === (int) $this->school_id, 404);
        $access = app(\App\Support\LiveClasses\LiveClassAccessService::class);
        $this->authorizeClassView($liveClass);

        $allMaterials = $liveClass->materials()->orderByDesc('id')->get();
        $resources = $allMaterials->where('category', LiveClassMaterial::CATEGORY_RESOURCE)->values();
        $recordings = $allMaterials->where('category', LiveClassMaterial::CATEGORY_RECORDING)->values();
        $canManage = $access->isOfferingBacked($liveClass)
            ? ($access->canLecturerManageMaterials(Auth::user(), $liveClass) || $access->canTenantAdminManage(Auth::user(), $liveClass))
            : Auth::user()->can('update', $liveClass);

        return view('admin.live_class.materials', compact('liveClass', 'resources', 'recordings', 'canManage'));
    }

    /** Render the small, Offering-contextual creation entry point. */
    public function createForOffering(Request $request, int $courseOffering)
    {
        $offering = $this->tenantOfferingOrFail($courseOffering);
        $access = app(\App\Support\LiveClasses\LiveClassAccessService::class);
        $actor = $request->user();
        $isAdmin = $access->canAdminCreateForOffering($actor, $offering);
        abort_unless($isAdmin || $access->canLecturerCreateForOffering($actor, $offering), 403);

        $allocations = $access->activeManagerAllocationsForOffering($offering);
        abort_if($allocations->isEmpty(), 403, get_phrase('This Offering has no current Primary or Co Lecturer facilitator allocation.'));
        $facilitators = $allocations->pluck('lecturer')->unique('id')->values();
        if (! $isAdmin) {
            $facilitators = $facilitators->where('id', $actor->id)->values();
            abort_if($facilitators->isEmpty(), 403);
        }

        $liveClass = new LiveClass([
            'platform' => $this->defaultPlatform(),
            'status' => LiveClass::STATUS_DRAFT,
            'timezone' => config('app.timezone', 'UTC'),
            'is_published' => false,
            'course_offering_id' => $offering->id,
        ]);
        $platformStatus = $this->platformConfigurationStatus();
        return view('admin.live_class.offering_create', compact('offering', 'facilitators', 'isAdmin', 'liveClass', 'platformStatus'));
    }

    /** The route supplies the Offering; no academic/tenant identity comes from the form. */
    public function storeForOffering(StoreOfferingLiveClassRequest $request, int $courseOffering)
    {
        foreach ([
            'school_id', 'course_offering_id', 'subject_id', 'programme_id', 'academic_session_id',
            'academic_year_id', 'academic_period_id', 'created_by', 'updated_by',
        ] as $reserved) {
            if ($request->exists($reserved)) {
                throw ValidationException::withMessages([
                    $reserved => get_phrase('Offering context is derived by PIIE and cannot be submitted.'),
                ]);
            }
        }

        $offering = $this->tenantOfferingOrFail($courseOffering);
        $actor = $request->user();
        $access = app(\App\Support\LiveClasses\LiveClassAccessService::class);
        $isAdmin = $access->canAdminCreateForOffering($actor, $offering);
        $lecturerAllowed = $access->canLecturerCreateForOffering($actor, $offering);
        abort_unless($isAdmin || $lecturerAllowed, 403);

        $validated = $request->validated();
        $timezone = $validated['timezone'] ?? config('app.timezone', 'UTC');
        $scheduledAt = Carbon::parse($validated['start_date'].' '.$validated['start_time'], $timezone);
        $endsAt = Carbon::parse($validated['start_date'].' '.$validated['end_time'], $timezone);
        $allocationsForMeeting = $access->activeManagerAllocationsForOffering($offering, $scheduledAt);
        $facilitatorIds = $allocationsForMeeting->pluck('user_id')->map(fn ($id) => (int) $id)->all();

        if (! $isAdmin && array_key_exists('teacher_id', $validated)
            && (int) $validated['teacher_id'] !== (int) $actor->id) {
            throw ValidationException::withMessages(['teacher_id' => get_phrase('Lecturers may only facilitate their own Offering-backed class.')]);
        }
        $facilitatorId = $isAdmin
            ? (int) ($validated['teacher_id'] ?? (count($facilitatorIds) === 1 ? $facilitatorIds[0] : 0))
            : (int) $actor->id;
        if (! in_array($facilitatorId, $facilitatorIds, true)) {
            throw ValidationException::withMessages(['teacher_id' => get_phrase('Choose a current Primary or Co Lecturer allocated to this exact Offering and meeting date.')]);
        }

        $platform = $validated['platform'];
        if (! in_array($platform, $this->getEnabledPlatforms(), true)) {
            throw ValidationException::withMessages(['platform' => get_phrase('Selected platform is disabled by administrator settings.')]);
        }

        $meetingUrl = $validated['meeting_url'] ?? null;
        if (empty($meetingUrl) && in_array($platform, ['jitsi', 'zoom', 'google_meet'], true)) {
            $meetingUrl = $this->resolveMeetingUrl($platform, $validated['title'], $scheduledAt, $endsAt, $timezone);
        }
        if (empty($meetingUrl)) {
            throw ValidationException::withMessages(['meeting_url' => get_phrase('Enter a secure HTTPS provider URL for this platform.')]);
        }

        $published = (bool) ($validated['is_published'] ?? false);
        $status = $this->deriveStatus($validated['status'] ?? LiveClass::STATUS_DRAFT, $scheduledAt, $endsAt, $published);
        $attributes = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'teacher_id' => $facilitatorId,
            'platform' => $platform,
            'meeting_url' => $meetingUrl,
            'meeting_id' => $validated['meeting_id'] ?? null,
            'meeting_password' => $validated['meeting_password'] ?? null,
            'scheduled_at' => $scheduledAt->timezone('UTC')->format('Y-m-d H:i:s'),
            'ends_at' => $endsAt->timezone('UTC')->format('Y-m-d H:i:s'),
            'start_date' => $validated['start_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'timezone' => $timezone,
            'status' => $status,
            'is_published' => $published,
            'attendance_enabled' => (bool) ($validated['attendance_enabled'] ?? false),
            'recording_url' => $validated['recording_url'] ?? null,
        ];

        try {
            $liveClass = app(\App\Support\LiveClasses\LiveClassService::class)
                ->createForOffering($actor, (int) $offering->id, $attributes);
        } catch (\DomainException $exception) {
            throw ValidationException::withMessages(['live_class' => get_phrase($exception->getMessage())]);
        }

        if ($published || in_array($status, [LiveClass::STATUS_SCHEDULED, LiveClass::STATUS_LIVE], true)) {
            $this->createStudentLiveClassNotice($liveClass, $published ? 'published' : 'scheduled');
        }
        $routePrefix = $this->getRoutePrefix($request);
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => get_phrase('Offering-backed Live Class scheduled'),
                'redirect' => route($routePrefix.'.live_classes.show', $liveClass->id),
            ]);
        }

        return redirect()->route($routePrefix.'.live_classes.show', $liveClass->id)
            ->with('success', get_phrase('Offering-backed Live Class scheduled'));
    }

    private function tenantOfferingOrFail(int $offeringId): CourseOffering
    {
        return CourseOffering::query()->where('school_id', (int) Auth::user()->school_id)->findOrFail($offeringId);
    }

    /** Authorized delivery boundary for file and external-link materials. */
    public function accessMaterial(LiveClass $liveClass, int $material)
    {
        $class = LiveClass::query()->where('school_id', $this->school_id)->whereKey($liveClass->id)->firstOrFail();
        $this->authorizeMaterialAccess($class, false);
        $item = $class->materials()->whereKey($material)->firstOrFail();
        if ($item->isRecording()) {
            $this->authorizeMaterialAccess($class, false, true);
        }

        if (! $item->isFile()) {
            $url = filter_var($item->link_url, FILTER_VALIDATE_URL) ? $item->link_url : null;
            abort_unless($url && strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https', 404);
            return redirect()->away($url);
        }

        if ($class->course_offering_id !== null) {
            $path = app(\App\Support\LiveClasses\LiveClassAssetStorage::class)
                ->resolvePrivatePath((string) $item->stored_name, $class, (string) $item->category);
            abort_unless($path, 404, get_phrase('This protected material is unavailable.'));
            $name = basename((string) ($item->original_name ?: $item->title));
            return response()->download($path, $name, [
                'Content-Type' => $item->mime_type ?: 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        abort_unless($item->absolute_path && is_file($item->absolute_path), 404, get_phrase('This material is unavailable.'));
        return response()->download($item->absolute_path, basename((string) ($item->original_name ?: $item->title)));
    }

    /** Authorize external HEI recording discovery before redirecting. */
    public function accessRecording(LiveClass $liveClass)
    {
        $class = LiveClass::query()->where('school_id', $this->school_id)->whereKey($liveClass->id)->firstOrFail();
        abort_unless($class->course_offering_id !== null, 404);
        $this->authorizeMaterialAccess($class, false, true);
        $url = trim((string) $class->recording_url);
        abort_unless(filter_var($url, FILTER_VALIDATE_URL) && strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https', 404);
        return redirect()->away($url);
    }

    private function authorizeMaterialAccess(LiveClass $liveClass, bool $manage, bool $recording = false): void
    {
        $access = app(\App\Support\LiveClasses\LiveClassAccessService::class);
        if ($liveClass->course_offering_id === null) {
            $this->authorize($manage ? 'update' : 'view', $liveClass);
            return;
        }

        $user = Auth::user();
        $allowed = match (true) {
            (int) $user->role_id === 7 => ! $manage && ($recording
                ? $access->canStudentViewRecording($user, $liveClass)
                : $access->canStudentViewMaterials($user, $liveClass)),
            in_array((int) $user->role_id, [1, 2], true) => $manage
                ? $access->canTenantAdminManage($user, $liveClass)
                : $access->canTenantAdmin($user, $liveClass, 'live_classes.view'),
            $manage => $access->canLecturerManageMaterials($user, $liveClass),
            default => $access->canLecturerView($user, $liveClass),
        };
        abort_unless($allowed, 403, get_phrase('You are not authorized to access this Live Class asset.'));
    }

    private function authorizeClassView(LiveClass $liveClass): void
    {
        $access = app(\App\Support\LiveClasses\LiveClassAccessService::class);
        if (! $access->isOfferingBacked($liveClass)) {
            $this->authorize('view', $liveClass);
            return;
        }

        $user = Auth::user();
        $allowed = (int) $user->role_id === 7
            ? $access->canStudentViewClass($user, $liveClass)
            : ($access->canTenantAdmin($user, $liveClass, 'live_classes.view')
                || $access->canLecturerView($user, $liveClass));
        abort_unless($allowed, 403, get_phrase('You are not authorized to view this Live Class.'));
    }

    private function authorizeClassManage(LiveClass $liveClass): void
    {
        $access = app(\App\Support\LiveClasses\LiveClassAccessService::class);
        if (! $access->isOfferingBacked($liveClass)) {
            $this->authorize('update', $liveClass);
            return;
        }

        $user = Auth::user();
        $allowed = $access->canTenantAdminManage($user, $liveClass)
            || $access->canLecturerManage($user, $liveClass);
        abort_unless($allowed, 403, get_phrase('You are not authorized to manage this Live Class.'));
    }

    public function storeMaterial(Request $request, LiveClass $liveClass)
    {
        abort_unless((int) $liveClass->school_id === (int) $this->school_id, 404);
        $this->authorizeMaterialAccess($liveClass, true);

        $category = $request->input('category', LiveClassMaterial::CATEGORY_RESOURCE);
        if (!in_array($category, [LiveClassMaterial::CATEGORY_RESOURCE, LiveClassMaterial::CATEGORY_RECORDING], true)) {
            $category = LiveClassMaterial::CATEGORY_RESOURCE;
        }

        $isRecording = $category === LiveClassMaterial::CATEGORY_RECORDING;
        $allowedExtensions = $isRecording ? LiveClassMaterial::ALLOWED_RECORDING_EXTENSIONS : LiveClassMaterial::ALLOWED_EXTENSIONS;
        $maxKb = ($isRecording ? LiveClassMaterial::MAX_RECORDING_MB : LiveClassMaterial::MAX_FILE_MB) * 1024;

        $validated = $request->validate([
            'type' => ['required', 'in:file,link'],
            'title' => ['required', 'string', 'max:200'],
            'file' => [
                'required_if:type,file', 'nullable', 'file',
                'mimes:' . implode(',', $allowedExtensions),
                'max:' . $maxKb,
            ],
            'link_url' => ['required_if:type,link', 'nullable', 'url', 'starts_with:https://', 'max:500'],
        ]);

        $payload = [
            'school_id' => $this->school_id,
            'live_class_id' => $liveClass->id,
            'type' => $validated['type'],
            'category' => $category,
            'title' => $validated['title'],
            'uploaded_by' => Auth::id(),
        ];

        if ($validated['type'] === 'file') {
            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());
            abort_unless(in_array($extension, $allowedExtensions, true), 422, 'This file type is not allowed.');
            $payload += [
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize() ?: 0,
            ];

            if ($liveClass->course_offering_id !== null) {
                $storage = app(\App\Support\LiveClasses\LiveClassAssetStorage::class);
                $detectedMime = $storage->detectedMime($file, $extension);
                if (! $detectedMime) {
                    throw ValidationException::withMessages(['file' => 'The uploaded file content does not match an approved file type.']);
                }
                $payload['mime_type'] = $detectedMime;
                $key = $storage->store($file, $liveClass, $category, $extension);
                $payload['stored_name'] = $key;
                try {
                    DB::transaction(fn () => LiveClassMaterial::create($payload));
                } catch (\Throwable $exception) {
                    $storage->deletePrivate($key, $liveClass, $category);
                    throw $exception;
                }
            } else {
                $destination = public_path($isRecording ? LiveClassMaterial::RECORDING_UPLOAD_DIR : LiveClassMaterial::UPLOAD_DIR);
                if (!is_dir($destination)) mkdir($destination, 0755, true);
                $storedAs = ($isRecording ? 'lcr' : 'lcm') . $liveClass->id . '_' . uniqid() . '.' . $extension;
                $payload['stored_name'] = $storedAs;
                $file->move($destination, $storedAs);
                try {
                    DB::transaction(fn () => LiveClassMaterial::create($payload));
                } catch (\Throwable $exception) {
                    $path = $destination.DIRECTORY_SEPARATOR.$storedAs;
                    if (is_file($path)) @unlink($path);
                    throw $exception;
                }
            }
        } else {
            $payload['link_url'] = $validated['link_url'];
            LiveClassMaterial::create($payload);
        }

        AuditLog::record('create', 'Live Classes', ($isRecording ? 'Recording' : 'Material') . " added to live class: {$liveClass->title}");

        return redirect()->back()->with('success', get_phrase($isRecording ? 'Recording added' : 'Material added'));
    }

    public function destroyMaterial(LiveClassMaterial $material)
    {
        $liveClass = LiveClass::query()->where('school_id', $this->school_id)->whereKey($material->live_class_id)->firstOrFail();
        $material = $liveClass->materials()->whereKey($material->id)->firstOrFail();
        $this->authorizeMaterialAccess($liveClass, true);

        if ($material->isFile()) {
            if ($liveClass->course_offering_id !== null) {
                $deleted = app(\App\Support\LiveClasses\LiveClassAssetStorage::class)
                    ->deletePrivate((string) $material->stored_name, $liveClass, (string) $material->category);
                abort_unless($deleted, 404, get_phrase('This protected material is unavailable.'));
            } elseif ($material->absolute_path && is_file($material->absolute_path)) {
                @unlink($material->absolute_path);
            }
        }

        $material->delete();

        AuditLog::record('delete', 'Live Classes', "Material removed from live class: {$liveClass->title}");

        return redirect()->back()->with('success', get_phrase('Material removed'));
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

        if (strcasecmp($meetingHost, $configuredHost) !== 0) {
            return false;
        }

        // meet.jit.si (Jitsi's free public server) refuses to stay embedded
        // past 5 minutes ("only meant for demo purposes") and never
        // validates our JWT, so embedding it gains nothing and actively
        // breaks longer classes — send it to its own tab instead, same as
        // Zoom/Google Meet. Only a real configured domain (self-hosted or
        // 8x8 JaaS) gets the in-app embed.
        if (strcasecmp($meetingHost, 'meet.jit.si') === 0) {
            return false;
        }

        return true;
    }

    public function studentIndex(Request $request)
    {
        $this->authorize('viewAny', LiveClass::class);
        $access = app(\App\Support\LiveClasses\LiveClassAccessService::class);

        $school_id = Auth::user()->school_id;
        $enroll = \App\Models\Enrollment::where('user_id', Auth::id())
            ->where('school_id', $school_id)
            ->first();
        $class_id = $enroll?->class_id;

        $search = trim((string) $request->input('search', ''));
        $subjectId = $request->input('subject_id');
        $platform = $request->input('platform');
        $status = $request->input('status');
        $date = $request->input('date');
        $view = $request->input('view', $status ? 'all' : 'upcoming');

        $classes = LiveClass::where('school_id', $school_id)
            ->published()
            ->where('status', '!=', LiveClass::STATUS_CANCELLED)
            ->where(function ($scope) use ($class_id, $enroll, $school_id, $access) {
                $scope->where(function ($legacy) use ($class_id, $enroll, $school_id) {
                    $legacy->whereNull('course_offering_id')
                        ->where(function ($q) use ($class_id) { $q->whereNull('class_id')->orWhere('class_id', $class_id); })
                        ->where(function ($q) use ($enroll) { $q->whereNull('academic_session_id'); if (!empty($enroll?->session_id)) $q->orWhere('academic_session_id', $enroll->session_id); })
                        ->where(function ($q) use ($class_id, $school_id) { $q->whereNull('subject_id')->orWhereHas('subject', function ($sub) use ($class_id, $school_id) { $sub->where('school_id', $school_id)->where(function ($c) use ($class_id) { $c->whereNull('class_id'); if ($class_id) $c->orWhere('class_id', $class_id); }); }); });
                })->orWhere(function ($offering) use ($access, $school_id) {
                    $offering->whereIn('course_offering_id', $access->confirmedOfferingIdsQuery(Auth::user(), (int) $school_id))
                        ->where(function ($lifecycle) {
                            $lifecycle->whereHas('courseOffering', fn ($query) => $query->whereIn('status', [
                                \App\Models\CourseOffering::STATUS_OPEN,
                                \App\Models\CourseOffering::STATUS_IN_PROGRESS,
                            ]))->orWhere(function ($historical) {
                                $historical->where(function ($classState) {
                                    $classState->where('status', LiveClass::STATUS_ENDED)
                                        ->orWhere(function ($elapsed) {
                                            $elapsed->whereNotNull('ends_at')->where('ends_at', '<=', now());
                                        });
                                })->whereHas('courseOffering', fn ($query) => $query->where('status', \App\Models\CourseOffering::STATUS_COMPLETED));
                            });
                        });
                });
            })
            ->when($search !== '', fn($q) => $q->where('title', 'like', "%{$search}%"))
            ->when($subjectId, fn($q) => $q->where('subject_id', $subjectId))
            ->when($platform, fn($q) => $q->where('platform', $platform))
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($date, fn($q) => $q->whereDate('start_date', $date))
            ->when(!$status, fn($q) => $this->applyQuickView($q, $view))
            ->with(['subject', 'teacher'])
            ->orderByDesc('start_date')
            ->orderByDesc('start_time')
            ->paginate(18);

        $subjects = Subject::where('school_id', $school_id)->orderBy('name')->get();

        return view('student.live_class.index', compact('classes', 'subjects', 'search', 'subjectId', 'platform', 'status', 'date', 'view'));
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
            'meeting_id' => $validated['meeting_id'] ?? ($existing?->meeting_id ?? null),
            'meeting_password' => $validated['meeting_password'] ?? ($existing?->meeting_password ?? null),
            'start_date' => $validated['start_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'timezone' => $validated['timezone'] ?? config('app.timezone', 'UTC'),
            'scheduled_at' => $scheduledAt->timezone('UTC')->format('Y-m-d H:i:s'),
            'ends_at' => $endsAt->timezone('UTC')->format('Y-m-d H:i:s'),
            'status' => $status,
            'is_published' => $isPublished,
            'attendance_enabled' => !empty($validated['attendance_enabled']) ? 1 : 0,
            'recording_url' => $validated['recording_url'] ?? ($existing?->recording_url ?? null),
            'created_by' => $existing?->created_by ?: Auth::id(),
            'updated_by' => Auth::id(),
        ];
    }

    /** Legacy/K12 forms cannot attach an HEI Offering by supplying a raw ID. */
    private function rejectOfferingContextOnLegacyWorkflow(Request $request): void
    {
        if ($request->exists('course_offering_id')) {
            throw ValidationException::withMessages([
                'course_offering_id' => get_phrase('Offering-backed Live Classes must be created through the Course Offering workflow.'),
            ]);
        }
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

            // Programme-linked (HEI) subjects have no class_id, so the
            // class_id filter below must not silently exclude them. Only
            // restrict them once this school has actually started assigning
            // teachers to programmes (see TeacherProgrammeAssignment) —
            // until then, fail open the same way class-based access always
            // did before TeacherPermission existed.
            $schoolHasConfiguredProgrammeAssignments = TeacherProgrammeAssignment::where('school_id', $this->school_id)->exists();

            $programmeIds = $schoolHasConfiguredProgrammeAssignments
                ? TeacherProgrammeAssignment::where('school_id', $this->school_id)
                    ->where('teacher_id', Auth::id())
                    ->pluck('programme_id')
                    ->unique()
                : collect();

            if ($classIds->isNotEmpty() || $schoolHasConfiguredProgrammeAssignments) {
                $query->where(function ($q) use ($classIds, $schoolHasConfiguredProgrammeAssignments, $programmeIds) {
                    $q->whereIn('class_id', $classIds);

                    if ($schoolHasConfiguredProgrammeAssignments) {
                        $q->orWhereIn('programme_id', $programmeIds);
                    } else {
                        $q->orWhereNull('class_id');
                    }
                });
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

    /**
     * Calls a meeting provider (Zoom / Google Meet). If the provider cannot be reached at all —
     * network, DNS, TLS certificate verification — the request fails as a normal validation
     * error on meeting_url (input kept, nothing saved) instead of an HTTP 500. The failure is
     * logged server-side with its class and school only: no tokens, secrets or response bodies.
     * A reachable provider that refuses the request still returns null (existing behaviour).
     */
    private function callMeetingProvider(string $provider, callable $call): ?string
    {
        try {
            return $call();
        } catch (\Illuminate\Http\Client\ConnectionException | \Illuminate\Http\Client\RequestException | \GuzzleHttp\Exception\TransferException $e) {
            \Illuminate\Support\Facades\Log::warning("Live class: {$provider} API could not be reached", [
                'exception' => get_class($e),
                'school_id' => auth()->user()->school_id ?? null,
                'user_id' => auth()->id(),
            ]);

            throw ValidationException::withMessages([
                'meeting_url' => get_phrase($provider . ' could not be reached right now, so the class was not saved. Please try again shortly, or paste a meeting link to schedule it now.'),
            ]);
        }
    }

    /**
     * The error for "no meeting link came back": not configured (setup message), or configured
     * but the provider refused / failed / answered without a link (HTTP 401/403/404/429/5xx,
     * expired or invalid credentials, malformed response). Nothing is saved; logged without secrets.
     */
    private function meetingLinkFailure(string $platform, string $label, string $notConfiguredMessage): ValidationException
    {
        if (!$this->platformIsConfigured($platform)) {
            return ValidationException::withMessages(['meeting_url' => get_phrase($notConfiguredMessage)]);
        }

        \Illuminate\Support\Facades\Log::warning("Live class: {$label} API returned no meeting link", [
            'school_id' => auth()->user()->school_id ?? null,
            'user_id' => auth()->id(),
        ]);

        return ValidationException::withMessages([
            'meeting_url' => get_phrase($label . ' did not create a meeting link, so the class was not saved. The service may be temporarily unavailable, or its connection settings may need attention from the system administrator. Please try again shortly, or paste a meeting link to schedule it now.'),
        ]);
    }

    private function resolveMeetingUrl(string $platform, string $title, Carbon $scheduledAt, Carbon $endsAt, string $timezone): string
    {
        if ($platform === 'jitsi') {
            return $this->generateMeetingUrl('jitsi', $title);
        }

        if ($platform === 'zoom') {
            $url = $this->callMeetingProvider('Zoom', fn () => $this->createZoomMeetingUrl($title, $scheduledAt, $endsAt, $timezone));
            if (!empty($url)) {
                return $url;
            }

            throw $this->meetingLinkFailure('zoom', 'Zoom', 'Zoom API is not configured. Add ZOOM_ACCOUNT_ID, ZOOM_CLIENT_ID, and ZOOM_CLIENT_SECRET in your .env file.');
        }

        if ($platform === 'google_meet') {
            $url = $this->callMeetingProvider('Google Meet', fn () => $this->createGoogleMeetUrl($title, $scheduledAt, $endsAt, $timezone));
            if (!empty($url)) {
                return $url;
            }

            throw $this->meetingLinkFailure('google_meet', 'Google Meet', 'Google Meet API is not configured. Add GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, and GOOGLE_REFRESH_TOKEN in your .env file.');
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

        $tokenResponse = Http::asForm()->connectTimeout(10)->timeout(20)
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
        $meetingResponse = Http::withToken($accessToken)->connectTimeout(10)->timeout(20)
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

        $tokenResponse = Http::asForm()->connectTimeout(10)->timeout(20)->post('https://oauth2.googleapis.com/token', [
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

        // Guests configured in admin/live-classes/meet-guests — added as
        // attendees so a teacher signed into one of these addresses is a
        // named, recognised guest on the invite (and is emailed a calendar
        // invite via sendUpdates=all below), instead of every joiner being
        // an anonymous link-holder the way this event used to be created.
        $guestEmails = LiveClassMeetGuest::forSchool($this->school_id)->pluck('email');
        $attendees = $guestEmails->map(fn (string $email) => ['email' => $email])->values()->all();

        $eventResponse = Http::withToken($accessToken)->connectTimeout(10)->timeout(20)
            ->acceptJson()
            ->post('https://www.googleapis.com/calendar/v3/calendars/' . urlencode($calendarId) . '/events?conferenceDataVersion=1&sendUpdates=all', [
                'summary' => $title,
                'start' => [
                    'dateTime' => $scheduledAt->copy()->setTimezone($timezone)->toIso8601String(),
                    'timeZone' => $timezone,
                ],
                'end' => [
                    'dateTime' => $endsAt->copy()->setTimezone($timezone)->toIso8601String(),
                    'timeZone' => $timezone,
                ],
                'attendees' => $attendees,
                'guestsCanSeeOtherGuests' => true,
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

        if ($liveClass->course_offering_id !== null) {
            $recipientIds = \App\Support\LiveClasses\LiveClassEligibility::eligibleStudentUserIds($liveClass);
            \App\Support\Notifications\NotificationService::notifyMany(
                $recipientIds,
                (int) $liveClass->school_id,
                'Live Class '.($eventType === 'published' ? 'published' : 'scheduled').': '.$liveClass->title,
                'A Live Class is available. Open it through PIIE to check your current access.',
                route('student.live_classes.join', $liveClass->id),
                'live_class_'.$eventType
            );
            return;
        }

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

    /**
     * The platform pre-selected for a brand-new class.
     *
     * Google Meet is the intended default — but only once it can actually
     * create a meeting. Defaulting to it before GOOGLE_CLIENT_ID/SECRET/
     * REFRESH_TOKEN are set in .env would make every new class fail
     * validation the moment it's saved (see resolveMeetingUrl()), so the
     * fallback to Jitsi — the one platform that never needs external
     * credentials — holds until Google Meet is actually configured.
     */
    /**
     * Admin-only list of emails added as attendees on every Google Meet
     * event this school creates (see createGoogleMeetUrl()) — lets an admin
     * grow the "recognised guest" list without ever touching .env.
     */
    public function meetGuests()
    {
        $this->authorize('create', LiveClass::class);
        abort_unless((int) Auth::user()->role_id === 2, 403);

        $guests = LiveClassMeetGuest::forSchool($this->school_id)->orderBy('email')->get();

        return view('admin.live_class.meet_guests', compact('guests'));
    }

    public function storeMeetGuest(Request $request)
    {
        $this->authorize('create', LiveClass::class);
        abort_unless((int) Auth::user()->role_id === 2, 403);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:191'],
            'label' => ['nullable', 'string', 'max:191'],
        ]);

        $exists = LiveClassMeetGuest::forSchool($this->school_id)
            ->where('email', $validated['email'])
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', get_phrase('That email is already on the list'));
        }

        LiveClassMeetGuest::create([
            'school_id' => $this->school_id,
            'email' => $validated['email'],
            'label' => $validated['label'] ?? null,
            'created_by' => Auth::id(),
        ]);

        AuditLog::record('create', 'Live Classes', "Added Google Meet guest: {$validated['email']}");

        return redirect()->back()->with('success', get_phrase('Guest email added'));
    }

    public function destroyMeetGuest($id)
    {
        $this->authorize('create', LiveClass::class);
        abort_unless((int) Auth::user()->role_id === 2, 403);

        $guest = LiveClassMeetGuest::forSchool($this->school_id)->findOrFail((int) $id);
        $email = $guest->email;
        $guest->delete();

        AuditLog::record('delete', 'Live Classes', "Removed Google Meet guest: {$email}");

        return redirect()->back()->with('success', get_phrase('Guest email removed'));
    }

    private function defaultPlatform(): string
    {
        return $this->platformIsConfigured('google_meet') ? 'google_meet' : 'jitsi';
    }

    /**
     * Whether a platform has the credentials it needs to create a real
     * meeting. Jitsi/BigBlueButton/custom need none of this — only Zoom and
     * Google Meet call out to an external API (see resolveMeetingUrl()).
     */
    private function platformIsConfigured(string $platform): bool
    {
        if ($platform === 'google_meet') {
            return (string) config('services.google_meet.client_id') !== ''
                && (string) config('services.google_meet.client_secret') !== ''
                && (string) config('services.google_meet.refresh_token') !== '';
        }

        if ($platform === 'zoom') {
            return (string) config('services.zoom.account_id') !== ''
                && (string) config('services.zoom.client_id') !== ''
                && (string) config('services.zoom.client_secret') !== '';
        }

        return true;
    }

    /** Configured-state per platform, for labelling <select> options in the views. */
    private function platformConfigurationStatus(): array
    {
        return [
            'jitsi' => true,
            'google_meet' => $this->platformIsConfigured('google_meet'),
            'zoom' => $this->platformIsConfigured('zoom'),
            'bigbluebutton' => true,
            'custom' => true,
        ];
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
