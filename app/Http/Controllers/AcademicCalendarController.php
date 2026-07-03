<?php

namespace App\Http\Controllers;

use App\Models\AcademicCalendar;
use App\Models\AuditLog;
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
        $events = AcademicCalendar::where('school_id', $this->school_id)->get()
            ->map(fn($e) => [
                'id'    => $e->id,
                'title' => $e->title,
                'start' => $e->event_date->toDateString(),
                'end'   => $e->end_date ? $e->end_date->toDateString() : null,
                'color' => $e->color,
                'extendedProps' => ['type' => $e->event_type, 'description' => $e->description],
            ]);
        return response()->json($events);
    }
}
