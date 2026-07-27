<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ProcurementRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProcurementController extends Controller
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
        $status   = $request->status ?? '';
        $requests = ProcurementRequest::where('school_id', $this->school_id)
            ->when($status, fn($q) => $q->where('status', $status))
            ->with(['requester', 'approver'])
            ->latest()
            ->paginate(20);
        return view('admin.procurement.index', compact('requests', 'status'));
    }

    public function openModal(Request $request)
    {
        $id  = $request->id;
        $req = $id ? ProcurementRequest::where('school_id', $this->school_id)->findOrFail($id) : null;
        return view('admin.procurement.modal', compact('req'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'          => 'required|max:255',
            'description'    => 'nullable|string',
            'quantity'       => 'required|integer|min:1',
            'estimated_cost' => 'nullable|numeric|min:0',
            'vendor'         => 'nullable|max:150',
        ]);
        $validated['school_id']    = $this->school_id;
        $validated['requested_by'] = Auth::id();
        $validated['status']       = 'submitted';
        $r = ProcurementRequest::create($validated);
        AuditLog::record('create', 'Procurement', "Raised request: {$r->title}");
        return response()->json(['status' => 'success', 'message' => get_phrase('Request submitted')]);
    }

    public function updateStatus(Request $request, $id)
    {
        $req = ProcurementRequest::where('school_id', $this->school_id)->findOrFail($id);
        $request->validate(['status' => 'required|in:draft,submitted,approved,ordered,received,rejected']);
        $oldStatus = $req->status;
        $req->update(['status' => $request->status, 'approved_by' => Auth::id()]);

        $action = ['approved' => 'approve', 'rejected' => 'reject'][$request->status] ?? 'update';

        AuditLog::record($action, 'Procurement', "Request \"{$req->title}\" status: {$oldStatus} → {$req->status}", [
            'record_type' => ProcurementRequest::class,
            'record_id'   => $req->id,
            'old_values'  => ['status' => $oldStatus],
            'new_values'  => ['status' => $req->status],
        ]);
        return redirect()->back()->with('success', get_phrase('Status updated'));
    }

    public function destroy($id)
    {
        $req = ProcurementRequest::where('school_id', $this->school_id)->findOrFail($id);
        AuditLog::record('delete', 'Procurement', "Deleted request: {$req->title}");
        $req->delete();
        return redirect()->back()->with('success', get_phrase('Request deleted'));
    }

    public function exportCsv(Request $request)
    {
        $status = $request->status ?? '';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="procurement_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($status) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['#', 'Title', 'Description', 'Quantity', 'Estimated cost', 'Vendor', 'Requested by', 'Status']);
            ProcurementRequest::where('school_id', $this->school_id)
                ->when($status, fn($q) => $q->where('status', $status))
                ->with(['requester', 'approver'])
                ->latest()
                ->get()
                ->each(function ($r, $i) use ($out) {
                    fputcsv($out, [
                        $i+1,
                        $r->title,
                        $r->description,
                        $r->quantity,
                        $r->estimated_cost,
                        $r->vendor,
                        optional($r->requester)->name,
                        ucfirst($r->status),
                    ]);
                });
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}
