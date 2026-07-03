<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FeeStructure;
use App\Models\Classes;
use App\Models\Programme;
use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeeStructureController extends Controller
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
        $structures  = FeeStructure::where('school_id', $this->school_id)
            ->when($search, fn($q) => $q->where('name', 'like', "%$search%"))
            ->orderBy('name')
            ->paginate(20);

        $classes     = Classes::where('school_id', $this->school_id)->orderBy('name')->get();
        $programmes  = Programme::where('school_id', $this->school_id)->orderBy('name')->get();
        $sessions    = Session::where('status', 1)->get();

        return view('admin.fee_structure.list', compact('structures', 'search', 'classes', 'programmes', 'sessions'));
    }

    public function openModal(Request $request)
    {
        $id         = $request->id;
        $structure  = $id ? FeeStructure::where('school_id', $this->school_id)->findOrFail($id) : null;
        $classes    = Classes::where('school_id', $this->school_id)->orderBy('name')->get();
        $programmes = Programme::where('school_id', $this->school_id)->orderBy('name')->get();
        $sessions   = Session::where('status', 1)->get();
        return view('admin.fee_structure.modal', compact('structure', 'classes', 'programmes', 'sessions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|max:150',
            'fee_type'      => 'required',
            'amount'        => 'required|numeric|min:0',
            'is_mandatory'  => 'nullable',
            'per_semester'  => 'nullable',
            'class_id'      => 'nullable|exists:classes,id',
            'programme_id'  => 'nullable|exists:programmes,id',
            'session_id'    => 'nullable|exists:sessions,id',
        ]);
        $validated['school_id']    = $this->school_id;
        $validated['is_mandatory'] = $request->has('is_mandatory') ? 1 : 0;
        $validated['per_semester'] = $request->has('per_semester') ? 1 : 0;

        $fs = FeeStructure::create($validated);
        AuditLog::record('create', 'Fee Structures', "Created fee: {$fs->name} — {$fs->amount}");
        return response()->json(['status' => 'success', 'message' => get_phrase('Fee structure created')]);
    }

    public function update(Request $request, $id)
    {
        $structure = FeeStructure::where('school_id', $this->school_id)->findOrFail($id);
        $validated = $request->validate([
            'name'         => 'required|max:150',
            'fee_type'     => 'required',
            'amount'       => 'required|numeric|min:0',
            'class_id'     => 'nullable|exists:classes,id',
            'programme_id' => 'nullable|exists:programmes,id',
            'session_id'   => 'nullable|exists:sessions,id',
        ]);
        $validated['is_mandatory'] = $request->has('is_mandatory') ? 1 : 0;
        $validated['per_semester'] = $request->has('per_semester') ? 1 : 0;
        $structure->update($validated);
        return response()->json(['status' => 'success', 'message' => get_phrase('Fee structure updated')]);
    }

    public function destroy($id)
    {
        $structure = FeeStructure::where('school_id', $this->school_id)->findOrFail($id);
        AuditLog::record('delete', 'Fee Structures', "Deleted fee: {$structure->name}");
        $structure->delete();
        return redirect()->back()->with('success', get_phrase('Fee structure deleted'));
    }
}
