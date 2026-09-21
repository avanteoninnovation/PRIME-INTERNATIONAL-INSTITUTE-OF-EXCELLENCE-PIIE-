<?php

namespace App\Http\Controllers;

use App\Models\AcademicCalendar;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\OnlineExam;
use App\Models\StudentProfile;
use App\Models\TeacherPermission;
use App\Support\Permissions\OnlineExamPermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AcademicCalendarController extends Controller
{
    private $school_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->school_id = Auth::user()->school_id;
            return $next($request);
        });
    }

    public function index()
    {
        $events = AcademicCalendar::where('school_id', $this->school_id)
            ->orderBy('event_date')
            ->get();
        return view('admin.academic_calendar.index', compact('events'));
    }

    public function openModal(Request $request)
    {
        $id    = $request->id;
        $event = $id ? AcademicCalendar::where('school_id', $this->school_id)->findOrFail($id) : null;
        return view('admin.academic_calendar.modal', compact('event'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'      => 'required|max:255',
            'event_type' => 'required',
            'event_date' => 'required|date',
            'end_date'   => 'nullable|date|after_or_equal:event_date',
            'color'      => 'nullable|max:10',
            'description' => 'nullable|string',
        ]);
        $validated['school_id'] = $this->school_id;
        $validated['is_public'] = $request->has('is_public') ? 1 : 0;

        $event = AcademicCalendar::create($validated);
        AuditLog::record('create', 'Academic Calendar', "Created event: {$event->title} on {$event->event_date}");
        return response()->json(['status' => 'success', 'message' => get_phrase('Event added to calendar')]);
    }

    public function update(Request $request, $id)
    {
        $event     = AcademicCalendar::where('school_id', $this->school_id)->findOrFail($id);
        $validated = $request->validate([
            'title'      => 'required|max:255',
            'event_type' => 'required',
            'event_date' => 'required|date',
            'end_date'   => 'nullable|date',
            'color'      => 'nullable|max:10',
            'description' => 'nullable|string',
        ]);
        $validated['is_public'] = $request->has('is_public') ? 1 : 0;
        $event->update($validated);
        return response()->json(['status' => 'success', 'message' => get_phrase('Event updated')]);
    }

    public function destroy($id)
    {
        $event = AcademicCalendar::where('school_id', $this->school_id)->findOrFail($id);
        AuditLog::record('delete', 'Academic Calendar', "Deleted event: {$event->title}");
        $event->delete();
        return redirect()->back()->with('success', get_phrase('Event deleted'));
    }

    public function eventsJson()
    {
        $user = Auth::user();
        $events = AcademicCalendar::where('school_id', $this->school_id)->get()
            ->map(fn($e) => [
                'id'    => $e->id,
                'title' => $e->title,
                'start' => $e->event_date->toDateString(),
                'end'   => $e->end_date ? $e->end_date->toDateString() : null,
                'color' => $e->color,
                'extendedProps' => ['type' => $e->event_type, 'description' => $e->description],
            ]);

        $examQuery = OnlineExam::forSchool($this->school_id)
            ->published()
            ->whereNotNull('start_datetime');

        if ((int) $user->role_id === 7) {
            $enrollment = Enrollment::where('user_id', $user->id)
                ->where('school_id', $this->school_id)->get();
            $classId = $enrollment->pluck('class_id')->filter()->first();
            $sessionIds = $enrollment->pluck('session_id')->filter()->map(fn($id) => (int) $id)->all();
            $programmeId = (int) (StudentProfile::where('user_id', $user->id)
                ->where('school_id', $this->school_id)->value('programme_id') ?? 0);
            $examQuery = OnlineExam::visibleToStudent($this->school_id, $classId, $programmeId, $sessionIds)
                ->whereNotNull('start_datetime');
        } elseif ((int) $user->role_id === 3) {
            $assignedClassIds = TeacherPermission::where('teacher_id', $user->id)
                ->where('school_id', $this->school_id)
                ->pluck('class_id')->filter()->unique()->values()->all();
            $canEditAll = app(OnlineExamPermissionService::class)->has($user, 'edit_all_online_exams');
            if (!$canEditAll) {
                $examQuery->where(function ($query) use ($user, $assignedClassIds) {
                    $query->where('creator_id', $user->id)->orWhere('created_by', $user->id);
                    if ($assignedClassIds) {
                        $query->orWhereIn('class_id', $assignedClassIds);
                    }
                });
            }
        }

        $examEvents = $examQuery->get()->map(function (OnlineExam $exam) use ($user) {
            $url = match ((int) $user->role_id) {
                7 => route('student.online_exam.instructions', $exam->id),
                3 => route('teacher.online_exams.show', $exam->id),
                default => route('admin.online_exams.show', $exam->id),
            };
            return [
                'id' => 'online-exam-' . $exam->id,
                'title' => $exam->title,
                'start' => $exam->start_datetime->toIso8601String(),
                'end' => $exam->end_datetime?->toIso8601String(),
                'url' => $url,
                'color' => '#6f42c1',
                'extendedProps' => [
                    'type' => 'online_exam',
                    'description' => get_phrase('Online Exam'),
                    'exam_id' => $exam->id,
                    'status' => $exam->lifecycle_status,
                ],
            ];
        });

        $events = $events->concat($examEvents)->values();
        return response()->json($events);
    }
}
