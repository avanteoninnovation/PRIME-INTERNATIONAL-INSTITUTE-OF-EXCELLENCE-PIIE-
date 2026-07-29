<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\AdmissionAgent;
use App\Models\AuditLog;
use App\Models\IntakeSession;
use App\Models\Programme;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use App\Support\StudentFeeInvoiceGenerator;
use App\Support\StudentPortalActivation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PDF;

class AdmissionsController extends Controller
{
    private $school_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->school_id = Auth::user()->school_id;

            // Admissions/Intake Sessions/Agents only apply to the one school
            // the public Apply Now portal currently belongs to — block
            // direct route access for every other school, not just hide the
            // nav item (see resources/views/admin/navigation.blade.php and
            // layouts/app.blade.php for the matching nav-level gate).
            if (! is_primary_school($this->school_id)) {
                return redirect()->route('admin.dashboard')->with('error', get_phrase('Admissions is not available for your school.'));
            }

            return $next($request);
        });
    }

    // ── Applications ──────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $search     = $request->search ?? '';
        $status     = $request->status ?? '';
        $session_id = $request->session_id ?? '';
        $source     = $request->source ?? '';

        $admissions = Admission::where('school_id', $this->school_id)
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                  ->orWhere('last_name', 'like', "%$search%")
                  ->orWhere('app_number', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            }))
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($session_id, fn($q) => $q->where('intake_session_id', $session_id))
            ->when($source, fn($q) => $q->where('source', $source))
            ->with(['programme', 'intakeSession'])
            ->latest()
            ->paginate(20);

        $sessions  = IntakeSession::where('school_id', $this->school_id)->orderByDesc('id')->get();
        $programmes = Programme::where('school_id', $this->school_id)->where('is_active', 1)->orderBy('name')->get();

        // Derived portal-activation status per row (no schema duplication):
        // looked up by email against this school's student accounts only.
        $applicantEmails = $admissions->pluck('email')->filter()->unique()->values();
        $studentsByEmail = User::where('school_id', $this->school_id)
            ->where('role_id', 7)
            ->whereIn('email', $applicantEmails)
            ->get(['id', 'email', 'force_password_change'])
            ->keyBy(fn ($u) => strtolower($u->email));

        return view('admin.admissions.index', compact('admissions', 'sessions', 'programmes', 'search', 'status', 'session_id', 'source', 'studentsByEmail'));
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
        $validated['source']     = 'staff_entry';

        $admission = Admission::create($validated);
        AuditLog::record('create', 'Admissions', "New application: {$admission->app_number} — {$admission->first_name} {$admission->last_name}");
        return response()->json(['status' => 'success', 'message' => get_phrase('Application created successfully')]);
    }

    public function updateStatus(Request $request, $id)
    {
        $admission = Admission::where('school_id', $this->school_id)->findOrFail($id);
        $request->validate([
            'status'   => 'required|in:submitted,under_review,accepted,rejected,enrolled,withdrawn',
            // Optional administrator-chosen portal password when enrolling —
            // if omitted, a password is generated automatically.
            'password' => 'nullable|string|min:6',
        ]);

        $oldStatus = $admission->status;
        $admission->update([
            'status'      => $request->status,
            'reviewed_by' => Auth::id(),
            'offer_date'  => ($request->status === 'accepted') ? now()->toDateString() : $admission->offer_date,
        ]);

        // If enrolled, convert the application into a student account.
        // Gated on the status actually transitioning to 'enrolled' so a
        // retried/duplicate request (old status already 'enrolled') never
        // re-runs conversion — createStudentFromAdmission() is additionally
        // idempotent on its own for safety (see its docblock).
        if ($request->status === 'enrolled' && $oldStatus !== 'enrolled') {
            $this->createStudentFromAdmission($admission, $request->password);
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
        $school     = School::find($this->school_id);
        $pdf        = PDF::loadView('admin.admissions.offer_letter', compact('admission', 'school'));
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
        return redirect()->back()->with('success', get_phrase('Intake session created'));
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
        $old = $session->only(array_keys($validated));
        $session->update($validated);
        AuditLog::record('update', 'Admissions', "Updated intake session: {$session->name}", [
            'record_type' => IntakeSession::class,
            'record_id'   => $session->id,
            'old_values'  => $old,
            'new_values'  => $session->only(array_keys($validated)),
        ]);
        return redirect()->back()->with('success', get_phrase('Session updated'));
    }

    public function destroySession($id)
    {
        $session = IntakeSession::where('school_id', $this->school_id)->findOrFail($id);
        AuditLog::record('delete', 'Admissions', "Deleted intake session: {$session->name}", [
            'record_type' => IntakeSession::class,
            'record_id'   => $session->id,
        ]);
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
        $agent = AdmissionAgent::create($validated);
        AuditLog::record('create', 'Agents', "Created admissions agent: {$agent->name}", [
            'record_type' => AdmissionAgent::class,
            'record_id'   => $agent->id,
            'new_values'  => $validated,
        ]);
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
        $old = $agent->only(array_keys($validated));
        $agent->update($validated);
        AuditLog::record('update', 'Agents', "Updated admissions agent: {$agent->name}", [
            'record_type' => AdmissionAgent::class,
            'record_id'   => $agent->id,
            'old_values'  => $old,
            'new_values'  => $agent->only(array_keys($validated)),
        ]);
        return response()->json(['status' => 'success', 'message' => get_phrase('Agent updated')]);
    }

    public function destroyAgent($id)
    {
        $agent = AdmissionAgent::where('school_id', $this->school_id)->findOrFail($id);
        AuditLog::record('delete', 'Agents', "Deleted admissions agent: {$agent->name}", [
            'record_type' => AdmissionAgent::class,
            'record_id'   => $agent->id,
        ]);
        $agent->delete();
        return redirect()->back()->with('success', get_phrase('Agent deleted'));
    }

    // ── Exports ────────────────────────────────────────────────────────────

    public function exportApplicationsCsv(Request $request)
    {
        $search     = $request->search ?? '';
        $status     = $request->status ?? '';
        $session_id = $request->session_id ?? '';
        $source     = $request->source ?? '';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="admissions_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($search, $status, $session_id, $source) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['#', 'App number', 'Name', 'Email', 'Phone', 'Programme', 'Intake session', 'Status', 'Source', 'Offer date']);
            Admission::where('school_id', $this->school_id)
                ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%$search%")
                      ->orWhere('last_name', 'like', "%$search%")
                      ->orWhere('app_number', 'like', "%$search%")
                      ->orWhere('email', 'like', "%$search%");
                }))
                ->when($status, fn($q) => $q->where('status', $status))
                ->when($session_id, fn($q) => $q->where('intake_session_id', $session_id))
                ->when($source, fn($q) => $q->where('source', $source))
                ->with(['programme', 'intakeSession'])
                ->latest()
                ->get()
                ->each(function ($a, $i) use ($out) {
                    fputcsv($out, [
                        $i+1,
                        $a->app_number,
                        trim("{$a->first_name} {$a->last_name}"),
                        $a->email,
                        $a->phone,
                        optional($a->programme)->name,
                        optional($a->intakeSession)->name,
                        ucfirst(str_replace('_', ' ', $a->status)),
                        $a->source === 'public' ? 'Public Application' : 'Staff Entry',
                        $a->offer_date,
                    ]);
                });
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportSessionsCsv()
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="intake_sessions_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['#', 'Name', 'Open date', 'Close date', 'Application fee', 'Status']);
            IntakeSession::where('school_id', $this->school_id)
                ->orderByDesc('id')
                ->get()
                ->each(function ($s, $i) use ($out) {
                    fputcsv($out, [$i+1, $s->name, $s->open_date, $s->close_date, $s->application_fee, $s->is_open ? 'Open' : 'Closed']);
                });
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportAgentsCsv()
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="admission_agents_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['#', 'Name', 'Email', 'Phone', 'Commission %', 'Status']);
            AdmissionAgent::where('school_id', $this->school_id)
                ->orderBy('name')
                ->get()
                ->each(function ($a, $i) use ($out) {
                    fputcsv($out, [$i+1, $a->name, $a->email, $a->phone, $a->commission_pct, $a->is_active ? 'Active' : 'Inactive']);
                });
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    /**
     * Converts an accepted/enrolled Admission into a portal student account.
     *
     * Idempotent by design so a retried call (e.g. the status-update request
     * firing twice) never creates duplicate accounts, profiles, or invoices:
     *  - the User is looked up by email first; only created if missing.
     *  - the StudentProfile is upserted (updateOrCreate keyed on user_id).
     *  - the fee invoice generator dedupes internally (see its docblock).
     * Credentials are only generated/emailed the first time the User row is
     * actually created, never on a subsequent idempotent call. The account
     * always starts with force_password_change=true so the student is
     * required to set their own password before reaching the portal,
     * regardless of whether the temp password was auto-generated or chosen
     * by the enrolling admin.
     */
    private function createStudentFromAdmission(Admission $admission, ?string $chosenPassword = null): void
    {
        $existingUser = User::where('email', $admission->email)->first();

        if ($existingUser && (int) $existingUser->role_id !== 7) {
            // Email already belongs to a non-student account — do not touch
            // it automatically; leave for an admin to resolve manually.
            AuditLog::record('error', 'Admissions', "Could not convert application {$admission->app_number} to a student — email {$admission->email} already belongs to a non-student account.");
            return;
        }

        $student = $existingUser;
        $plainPassword = null;

        if (! $student) {
            $plainPassword = $chosenPassword ?: Str::random(10);
            $name = trim("{$admission->first_name} {$admission->last_name}");

            $student = User::create([
                'name'                   => $name,
                'email'                  => $admission->email,
                'password'               => Hash::make($plainPassword),
                'code'                   => student_code(),
                'role_id'                => 7, // student
                'school_id'              => $this->school_id,
                'account_status'         => 'active',
                'status'                 => 1,
                'force_password_change'  => true,
                'user_information'       => json_encode([
                    'phone'   => $admission->phone,
                    'address' => $admission->physical_address,
                ]),
            ]);

            AuditLog::record('create', 'Admissions', "Student portal account created for {$student->name} (#{$student->id}) from application {$admission->app_number}.");
        }

        StudentProfile::updateOrCreate(
            ['user_id' => $student->id],
            [
                'school_id'         => $this->school_id,
                'first_name'        => $admission->first_name,
                'last_name'         => $admission->last_name,
                'programme_id'      => $admission->programme_id,
                'intake_session_id' => $admission->intake_session_id,
                'nationality'       => $admission->nationality,
            ]
        );

        StudentFeeInvoiceGenerator::generateForStudent($student, $admission->programme_id, $this->school_id);

        if ($plainPassword !== null) {
            $sent = StudentPortalActivation::sendActivationEmail($student, $plainPassword, $admission->programme_id, $admission->intake_session_id);

            if ($sent) {
                AuditLog::record('update', 'Admissions', "Student portal activation email sent to {$student->email} (#{$student->id}).");
            }
        }
    }
}
