<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LeaveType;
use App\Models\Leavelist;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
    private $school_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->school_id = Auth::user()->school_id;
            return $next($request);
        });
    }

    // ── Leave Types ────────────────────────────────────────────────────────

    public function types(Request $request)
    {
        $types = LeaveType::where('school_id', $this->school_id)->orderBy('name')->get();
        return view('admin.leave.types', compact('types'));
    }

    public function typeModal()
    {
        return view('admin.leave.type_modal');
    }

    public function storeType(Request $request)
    {
        $validated = $request->validate(['name' => 'required|max:100', 'max_days' => 'required|integer|min:0']);
        $validated['school_id'] = $this->school_id;
        $validated['is_paid']   = $request->has('is_paid') ? 1 : 0;
        LeaveType::create($validated);
        return response()->json(['status' => 'success', 'message' => get_phrase('Leave type created')]);
    }

    public function destroyType($id)
    {
        LeaveType::where('school_id', $this->school_id)->findOrFail($id)->delete();
        return redirect()->back()->with('success', get_phrase('Leave type deleted'));
    }

    // ── Leave Applications ─────────────────────────────────────────────────

    public function index(Request $request)
    {
        $search   = $request->search ?? '';
        $status   = $request->status ?? '';
        $leaves   = Leavelist::where('school_id', $this->school_id)
            ->when($status, fn($q) => $q->where('status', $status))
            ->with(['user', 'leaveType'])
            ->latest()
            ->paginate(20);

        $types    = LeaveType::where('school_id', $this->school_id)->get();
        $staffList = User::where('school_id', $this->school_id)
            ->whereIn('role_id', [2, 3, 6, 7, 8, 9, 10, 11, 12])
            ->orderBy('name')->get();

        return view('admin.leave.index', compact('leaves', 'types', 'staffList', 'search', 'status'));
    }

    public function openModal(Request $request)
    {
        $id      = $request->id;
        $leave   = $id ? Leavelist::where('school_id', $this->school_id)->findOrFail($id) : null;
        $types   = LeaveType::where('school_id', $this->school_id)->get();
        $staff   = User::where('school_id', $this->school_id)
            ->whereIn('role_id', [2, 3, 6, 7, 8, 9, 10, 11, 12])
            ->orderBy('name')->get();
        return view('admin.leave.modal', compact('leave', 'types', 'staff'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'       => 'required|exists:users,id',
            'leave_type_id' => 'nullable|exists:leave_types,id',
            'from_date'     => 'required|date',
            'to_date'       => 'required|date|after_or_equal:from_date',
            'reason'        => 'nullable|string',
        ]);

        $from  = \Carbon\Carbon::parse($validated['from_date']);
        $to    = \Carbon\Carbon::parse($validated['to_date']);
        $days  = $from->diffInDays($to) + 1;

        $lt    = $validated['leave_type_id'] ? LeaveType::find($validated['leave_type_id']) : null;

        Leavelist::create(array_merge($validated, [
            'school_id'  => $this->school_id,
            'leave_type' => $lt?->name,
            'days'       => $days,
            'status'     => 'pending',
        ]));

        AuditLog::record('create', 'Leave', "Leave applied for user #{$validated['user_id']}: {$validated['from_date']} to {$validated['to_date']}");
        return response()->json(['status' => 'success', 'message' => get_phrase('Leave application submitted')]);
    }

    public function approve($id)
    {
        $leave = Leavelist::where('school_id', $this->school_id)->findOrFail($id);
        $leave->update(['status' => 'approved', 'approved_by' => Auth::id()]);
        AuditLog::record('update', 'Leave', "Approved leave #{$id}");
        return redirect()->back()->with('success', get_phrase('Leave approved'));
    }

    public function reject($id)
    {
        $leave = Leavelist::where('school_id', $this->school_id)->findOrFail($id);
        $leave->update(['status' => 'rejected', 'approved_by' => Auth::id()]);
        AuditLog::record('update', 'Leave', "Rejected leave #{$id}");
        return redirect()->back()->with('success', get_phrase('Leave rejected'));
    }

    public function destroy($id)
    {
        Leavelist::where('school_id', $this->school_id)->findOrFail($id)->delete();
        return redirect()->back()->with('success', get_phrase('Leave deleted'));
    }
}
