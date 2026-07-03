<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\AdmissionAgent;
use App\Models\AuditLog;
use App\Models\IntakeSession;
use App\Models\Programme;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PDF;

class AdmissionsController extends Controller
{
    private $school_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->school_id = Auth::user()->school_id;
            return $next($request);
        });
    }

    // ── Applications ──────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $search     = $request->search ?? '';
        $status     = $request->status ?? '';
        $session_id = $request->session_id ?? '';

        $admissions = Admission::where('school_id', $this->school_id)
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                  ->orWhere('last_name', 'like', "%$search%")
                  ->orWhere('app_number', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            }))
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($session_id, fn($q) => $q->where('intake_session_id', $session_id))
            ->with(['programme', 'intakeSession'])
            ->latest()
            ->paginate(20);

        $sessions  = IntakeSession::where('school_id', $this->school_id)->orderByDesc('id')->get();
        $programmes = Programme::where('school_id', $this->school_id)->where('is_active', 1)->orderBy('name')->get();

        return view('admin.admissions.index', compact('admissions', 'sessions', 'programmes', 'search', 'status', 'session_id'));
    }

    public function openModal(Request $request)
    {
        $id         = $request->id;
        $admission  = $id ? Admission::where('school_id', $this->school_id)->findOrFail($id) : null;
        $sessions   = IntakeSession::where('school_id', $this->school_id)->where('is_open', 1)->orderByDesc('id')->get();
        $programmes = Programme::where('school_id', $this->school_id)->where('is_active', 1)->orderBy('name')->get();
        $agents     = AdmissionAgent::where('school_id', $this->school_id)->where('is_active', 1)->orderBy('name')->get();
        return view('admin.admissions.modal', compact('admission', 'sessions', 'programmes', 'agents'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name'         => 'required|max:100',
            'last_name'          => 'required|max:100',
            'email'              => 'nullable|email|max:150',
            'phone'              => 'nullable|max:20',
            'intake_session_id'  => 'nullable|exists:intake_sessions,id',
            'programme_id'       => 'nullable|exists:programmes,id',
            'gender'             => 'nullable|max:10',
            'dob'                => 'nullable|date',
            'nationality'        => 'nullable|max:80',
            'qualifications'     => 'nullable|string',
            'agent_id'           => 'nullable|exists:admissions_agents,id',
        ]);

        $validated['school_id']  = $this->school_id;
        $validated['app_number'] = 'APP-' . strtoupper(uniqid());
        $validated['status']     = 'submitted';

        $admission = Admission::create($validated);
        AuditLog::record('create', 'Admissions', "New application: {$admission->app_number} — {$admission->first_name} {$admission->last_name}");
        return response()->json(['status' => 'success', 'message' => get_phrase('Application created successfully')]);
    }

    public function updateStatus(Request $request, $id)
    {
        $admission = Admission::where('school_id', $this->school_id)->findOrFail($id);
        $request->validate(['status' => 'required|in:submitted,under_review,accepted,rejected,enrolled,withdrawn']);

        $oldStatus = $admission->status;
        $admission->update([
            'status'      => $request->status,
            'reviewed_by' => Auth::id(),
            'offer_date'  => ($request->status === 'accepted') ? now()->toDateString() : $admission->offer_date,
        ]);

        // If enrolled, optionally create student user account
        if ($request->status === 'enrolled' && $oldStatus !== 'enrolled') {
            $this->createStudentFromAdmission($admission);
        }

        AuditLog::record('update', 'Admissions', "Updated application {$admission->app_number} status: $oldStatus → {$admission->status}");
        return redirect()->back()->with('success', get_phrase('Status updated'));
    }

    public function destroy($id)
    {
        $admission = Admission::where('school_id', $this->school_id)->findOrFail($id);
        AuditLog::record('delete', 'Admissions', "Deleted application: {$admission->app_number}");
        $admission->delete();
        return redirect()->back()->with('success', get_phrase('Application deleted'));
    }

    public function printOfferLetter($id)
    {
        $admission  = Admission::where('school_id', $this->school_id)->with(['programme', 'intakeSession'])->findOrFail($id);
        $pdf        = PDF::loadView('admin.admissions.offer_letter', compact('admission'));
        return $pdf->download("OfferLetter_{$admission->app_number}.pdf");
    }

    // ── Intake Sessions ────────────────────────────────────────────────────

    public function sessions(Request $request)
    {
        $sessions = IntakeSession::where('school_id', $this->school_id)->orderByDesc('id')->paginate(20);
        return view('admin.admissions.sessions', compact('sessions'));
    }

    public function openSessionModal(Request $request)
    {
        $id      = $request->id;
        $session = $id ? IntakeSession::where('school_id', $this->school_id)->findOrFail($id) : null;
        return view('admin.admissions.session_modal', compact('session'));
    }

    public function storeSession(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|max:100',
            'open_date'       => 'nullable|date',
            'close_date'      => 'nullable|date',
            'application_fee' => 'nullable|numeric|min:0',
        ]);
        $validated['school_id'] = $this->school_id;
        $validated['is_open']   = $request->has('is_open') ? 1 : 0;
        IntakeSession::create($validated);
        AuditLog::record('create', 'Admissions', "Created intake session: {$validated['name']}");
        return response()->json(['status' => 'success', 'message' => get_phrase('Intake session created')]);
    }

    public function updateSession(Request $request, $id)
    {
        $session   = IntakeSession::where('school_id', $this->school_id)->findOrFail($id);
        $validated = $request->validate([
            'name'            => 'required|max:100',
            'open_date'       => 'nullable|date',
            'close_date'      => 'nullable|date',
            'application_fee' => 'nullable|numeric|min:0',
        ]);
        $validated['is_open'] = $request->has('is_open') ? 1 : 0;
        $session->update($validated);
        return response()->json(['status' => 'success', 'message' => get_phrase('Session updated')]);
    }

    public function destroySession($id)
    {
        $session = IntakeSession::where('school_id', $this->school_id)->findOrFail($id);
        $session->delete();
        return redirect()->back()->with('success', get_phrase('Session deleted'));
    }

    // ── Agents ────────────────────────────────────────────────────────────

    public function agents(Request $request)
    {
        $agents = AdmissionAgent::where('school_id', $this->school_id)->orderBy('name')->paginate(20);
        return view('admin.admissions.agents', compact('agents'));
    }

    public function openAgentModal(Request $request)
    {
        $id    = $request->id;
        $agent = $id ? AdmissionAgent::where('school_id', $this->school_id)->findOrFail($id) : null;
        return view('admin.admissions.agent_modal', compact('agent'));
    }

    public function storeAgent(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|max:150',
            'email'          => 'nullable|email|max:150',
            'phone'          => 'nullable|max:20',
            'commission_pct' => 'nullable|numeric|min:0|max:100',
        ]);
        $validated['school_id'] = $this->school_id;
        $validated['is_active'] = 1;
        AdmissionAgent::create($validated);
        return response()->json(['status' => 'success', 'message' => get_phrase('Agent created')]);
    }

    public function updateAgent(Request $request, $id)
    {
        $agent     = AdmissionAgent::where('school_id', $this->school_id)->findOrFail($id);
        $validated = $request->validate([
            'name'           => 'required|max:150',
            'email'          => 'nullable|email|max:150',
            'phone'          => 'nullable|max:20',
            'commission_pct' => 'nullable|numeric|min:0|max:100',
        ]);
        $validated['is_active'] = $request->has('is_active') ? 1 : 0;
        $agent->update($validated);
        return response()->json(['status' => 'success', 'message' => get_phrase('Agent updated')]);
    }

    public function destroyAgent($id)
    {
        $agent = AdmissionAgent::where('school_id', $this->school_id)->findOrFail($id);
        $agent->delete();
        return redirect()->back()->with('success', get_phrase('Agent deleted'));
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function createStudentFromAdmission(Admission $admission): void
    {
        if (User::where('email', $admission->email)->exists()) {
            return;
        }
        $name = trim("{$admission->first_name} {$admission->last_name}");
        User::create([
            'name'           => $name,
            'email'          => $admission->email,
            'password'       => Hash::make(strtolower($admission->first_name) . '1234'),
            'role_id'        => 4, // student
            'school_id'      => $this->school_id,
            'account_status' => 'active',
            'status'         => 1,
        ]);
    }
}
