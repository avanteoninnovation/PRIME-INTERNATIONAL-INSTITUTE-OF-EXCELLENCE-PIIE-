<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Programme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Support\Export\ExcelExportService;
use PDF;

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

    private function levelRule(): string
    {
        return 'required|in:' . implode(',', array_merge(Programme::LEVELS, Programme::LEVELS_LEGACY));
    }

    private function modeRule(): string
    {
        return 'required|in:' . implode(',', array_merge(Programme::MODES, Programme::MODES_LEGACY));
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
        $programme   = $id ? Programme::where('school_id', $this->school_id)->findOrFail($id) : null;
        $departments = Department::where('school_id', $this->school_id)->orderBy('name')->get();
        return view('admin.programme.modal', compact('programme', 'departments'));
    }

    public function store(Request $request)
    {
        Log::info('ProgrammeController@store called', ['payload' => $request->all(), 'user_id' => Auth::id()]);
        $validated = $request->validate([
            'code'          => 'required|max:20',
            'name'          => 'required|max:255',
            'level'         => $this->levelRule(),
            'duration'      => 'nullable|max:50',
            'mode'          => $this->modeRule(),
            'tuition_fee'   => 'nullable|numeric|min:0',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $validated['school_id']  = $this->school_id;
        $validated['is_active']  = 1;
        $programme = Programme::create($validated);
        AuditLog::record('create', 'Programmes', "Created programme: {$programme->name}");
        Log::info('Programme created', ['id' => $programme->id, 'attrs' => $programme->toArray()]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => get_phrase('Programme created successfully')]);
        }

        return redirect()->route('admin.programmes.index')->with('success', get_phrase('Programme created successfully'));
    }

    public function update(Request $request, $id)
    {
        $programme = Programme::where('school_id', $this->school_id)->findOrFail($id);
        $validated = $request->validate([
            'code'          => 'required|max:20',
            'name'          => 'required|max:255',
            'level'         => $this->levelRule(),
            'duration'      => 'nullable|max:50',
            'mode'          => $this->modeRule(),
            'tuition_fee'   => 'nullable|numeric|min:0',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $programme->update($validated);
        AuditLog::record('update', 'Programmes', "Updated programme: {$programme->name}");

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => get_phrase('Programme updated successfully')]);
        }

        return redirect()->route('admin.programmes.index')->with('success', get_phrase('Programme updated successfully'));
    }

    public function destroy($id)
    {
        $programme = Programme::where('school_id', $this->school_id)->findOrFail($id);

        if ($programme->subjects()->exists() || $programme->admissions()->exists() || $programme->studentProfiles()->exists()) {
            return redirect()->back()->with('error', get_phrase('This programme has related courses, admissions, or students and cannot be deleted'));
        }

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

    public function exportCsv(Request $request)
    {
        $search = $request->search ?? '';
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="programmes_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($search) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['#', 'Code', 'Name', 'Level', 'Mode', 'Duration', 'Tuition Fee', 'Status']);
            Programme::where('school_id', $this->school_id)
                ->when($search, fn($q) => $q->where('name', 'like', "%$search%")->orWhere('code', 'like', "%$search%"))
                ->orderBy('name')
                ->get()
                ->each(function ($p, $i) use ($out) {
                    fputcsv($out, csv_safe_row([$i+1, $p->code, $p->name, $p->level, ucfirst($p->mode), $p->duration, $p->tuition_fee, $p->is_active ? 'Active' : 'Inactive']));
                });
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function filteredProgrammes(string $search)
    {
        return Programme::where('school_id', $this->school_id)
            ->when($search, fn($q) => $q->where('name', 'like', "%$search%")->orWhere('code', 'like', "%$search%"))
            ->orderBy('name')
            ->get();
    }

    /** Genuine .xlsx export (PhpSpreadsheet — see App\Support\Export\ExcelExportService), alongside the existing CSV export above. */
    public function exportExcel(Request $request)
    {
        $programmes = $this->filteredProgrammes($request->search ?? '');

        $rows = $programmes->map(fn ($p, $i) => [
            $i + 1,
            $p->code,
            $p->name,
            $p->level,
            $p->mode,
            $p->duration,
            $p->tuition_fee,
            $p->activeStudentCount(),
            $p->is_active ? 'Active' : 'Inactive',
        ])->values();

        return ExcelExportService::download(
            'programmes_' . date('Y-m-d'),
            ['#', 'Code', 'Name', 'Level', 'Mode', 'Duration', 'Tuition Fee (UGX)', 'Students', 'Status'],
            $rows
        );
    }

    /** Printable / PDF programme list (dompdf — same package already used for transcripts and admission offer letters). ?inline=1 streams it in the browser for printing rather than forcing a download. */
    public function printPdf(Request $request)
    {
        $programmes = $this->filteredProgrammes($request->search ?? '');
        $pdf = PDF::loadView('admin.programme.pdf', ['programmes' => $programmes]);
        $filename = 'programmes_' . date('Y-m-d') . '.pdf';

        return $request->boolean('inline') ? $pdf->stream($filename) : $pdf->download($filename);
    }
}
