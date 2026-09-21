<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\StudentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Admin-side review queue for the Student Affairs requests students submit
 * (see StudentController::requestsIndex()/storeRequest()) — transfer
 * applications, complaints, fee-discount appeals. Kept as its own small
 * controller rather than folded into AdminController (already one of the
 * largest controllers in the app), matching the same precedent
 * TranscriptController already set for a similarly-scoped admin feature.
 */
class StudentRequestController extends Controller
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
        $status = trim((string) $request->input('status', ''));
        $type = trim((string) $request->input('type', ''));

        $requests = StudentRequest::forSchool($this->school_id)
            ->with('student', 'transferToProgramme')
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->latest('id')
            ->paginate(20)
            ->appends($request->all());

        return view('admin.student_requests.index', [
            'requests' => $requests,
            'types' => StudentRequest::TYPES,
            'transferTypes' => StudentRequest::TRANSFER_TYPES,
            'transferReasons' => StudentRequest::TRANSFER_REASONS,
            'status' => $status,
            'type' => $type,
        ]);
    }

    public function update(Request $request, $id)
    {
        $studentRequest = StudentRequest::forSchool($this->school_id)->findOrFail((int) $id);

        $validated = $request->validate([
            'status' => ['required', Rule::in([StudentRequest::STATUS_APPROVED, StudentRequest::STATUS_REJECTED])],
            'admin_response' => ['nullable', 'string'],
        ]);

        $studentRequest->update([
            'status' => $validated['status'],
            'admin_response' => $validated['admin_response'] ?? null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        AuditLog::record('update', 'Student Affairs', "Reviewed student request #{$studentRequest->id} as {$validated['status']}");

        return redirect()->back()->with('message', get_phrase('Request updated.'));
    }
}
