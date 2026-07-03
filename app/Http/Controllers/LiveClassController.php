<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Classes;
use App\Models\LiveClass;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
        $search   = $request->search ?? '';
        $classes  = LiveClass::where('school_id', $this->school_id)
            ->when($search, fn($q) => $q->where('title', 'like', "%$search%"))
            ->with(['subject', 'teacher'])
            ->orderByDesc('scheduled_at')
            ->paginate(20);

        $subjects  = Subject::where('school_id', $this->school_id)->orderBy('name')->get();
        $classList = Classes::where('school_id', $this->school_id)->orderBy('name')->get();

        return view('admin.live_class.index', compact('classes', 'subjects', 'classList', 'search'));
    }

    public function openModal(Request $request)
    {
        $id         = $request->id;
        $liveClass  = $id ? LiveClass::where('school_id', $this->school_id)->findOrFail($id) : null;
        $subjects   = Subject::where('school_id', $this->school_id)->orderBy('name')->get();
        $classList  = Classes::where('school_id', $this->school_id)->orderBy('name')->get();
        return view('admin.live_class.modal', compact('liveClass', 'subjects', 'classList'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'        => 'required|max:255',
            'subject_id'   => 'nullable|exists:subjects,id',
            'class_id'     => 'nullable|exists:classes,id',
            'platform'     => 'required|in:jitsi,zoom,google_meet,teams,other',
            'meeting_url'  => 'nullable|url|max:500',
            'scheduled_at' => 'required|date',
            'ends_at'      => 'nullable|date',
        ]);

        $validated['school_id']  = $this->school_id;
        $validated['teacher_id'] = Auth::id();
        $validated['status']     = 'scheduled';

        // Auto-generate Jitsi URL if platform is jitsi and no URL provided
        if ($validated['platform'] === 'jitsi' && empty($validated['meeting_url'])) {
            $slug = Str::slug(get_settings('system_title') . '-' . Str::random(8));
            $validated['meeting_url'] = "https://meet.jit.si/{$slug}";
        }

        $lc = LiveClass::create($validated);
        AuditLog::record('create', 'Live Classes', "Scheduled live class: {$lc->title}");
        return response()->json(['status' => 'success', 'message' => get_phrase('Live class scheduled')]);
    }

    public function update(Request $request, $id)
    {
        $liveClass = LiveClass::where('school_id', $this->school_id)->findOrFail($id);
        $validated = $request->validate([
            'title'        => 'required|max:255',
            'subject_id'   => 'nullable|exists:subjects,id',
            'class_id'     => 'nullable|exists:classes,id',
            'platform'     => 'required',
            'meeting_url'  => 'nullable|max:500',
            'scheduled_at' => 'required|date',
            'ends_at'      => 'nullable|date',
            'status'       => 'required|in:scheduled,live,ended,cancelled',
        ]);
        $liveClass->update($validated);
        return response()->json(['status' => 'success', 'message' => get_phrase('Live class updated')]);
    }

    public function destroy($id)
    {
        $lc = LiveClass::where('school_id', $this->school_id)->findOrFail($id);
        AuditLog::record('delete', 'Live Classes', "Deleted live class: {$lc->title}");
        $lc->delete();
        return redirect()->back()->with('success', get_phrase('Live class deleted'));
    }

    // Student: view upcoming live classes
    public function studentIndex()
    {
        $school_id  = Auth::user()->school_id;
        $student_id = Auth::id();
        $enroll     = \App\Models\Enrollment::where('user_id', $student_id)->where('school_id', $school_id)->first();
        $class_id   = $enroll?->class_id;

        $classes = LiveClass::where('school_id', $school_id)
            ->where('status', '!=', 'cancelled')
            ->where(fn($q) => $q->whereNull('class_id')->orWhere('class_id', $class_id))
            ->with(['subject', 'teacher'])
            ->orderByDesc('scheduled_at')
            ->get();

        return view('student.live_class.index', compact('classes'));
    }
}
