<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Programme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProgrammeController extends Controller
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
        $search      = $request->search ?? '';
        $programmes  = Programme::where('school_id', $this->school_id)
            ->when($search, fn($q) => $q->where('name', 'like', "%$search%")->orWhere('code', 'like', "%$search%"))
            ->orderBy('name')
            ->paginate(20);
        $departments = Department::where('school_id', $this->school_id)->orderBy('name')->get();
        return view('admin.programme.list', compact('programmes', 'search', 'departments'));
    }

    public function openModal(Request $request)
    {
        $id          = $request->id;
        $programme   = $id ? Programme::findOrFail($id) : null;
        $departments = Department::where('school_id', $this->school_id)->orderBy('name')->get();
        return view('admin.programme.modal', compact('programme', 'departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'          => 'required|max:20',
            'name'          => 'required|max:255',
            'level'         => 'required',
            'duration'      => 'nullable|max:50',
            'mode'          => 'required',
            'tuition_fee'   => 'nullable|numeric|min:0',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $validated['school_id']  = $this->school_id;
        $validated['is_active']  = 1;
        $programme = Programme::create($validated);
        AuditLog::record('create', 'Programmes', "Created programme: {$programme->name}");
        return response()->json(['status' => 'success', 'message' => get_phrase('Programme created successfully')]);
    }

    public function update(Request $request, $id)
    {
        $programme = Programme::where('school_id', $this->school_id)->findOrFail($id);
        $validated = $request->validate([
            'code'          => 'required|max:20',
            'name'          => 'required|max:255',
            'level'         => 'required',
            'duration'      => 'nullable|max:50',
            'mode'          => 'required',
            'tuition_fee'   => 'nullable|numeric|min:0',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $programme->update($validated);
        AuditLog::record('update', 'Programmes', "Updated programme: {$programme->name}");
        return response()->json(['status' => 'success', 'message' => get_phrase('Programme updated successfully')]);
    }

    public function destroy($id)
    {
        $programme = Programme::where('school_id', $this->school_id)->findOrFail($id);
        AuditLog::record('delete', 'Programmes', "Deleted programme: {$programme->name}");
        $programme->delete();
        return redirect()->back()->with('success', get_phrase('Programme deleted'));
    }

    public function toggleStatus($id)
    {
        $programme = Programme::where('school_id', $this->school_id)->findOrFail($id);
        $programme->update(['is_active' => !$programme->is_active]);
        return redirect()->back()->with('success', get_phrase('Status updated'));
    }
}
