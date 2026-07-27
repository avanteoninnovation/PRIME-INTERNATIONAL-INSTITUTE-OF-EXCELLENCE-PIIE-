<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\AuditLog;
use App\Models\IntakeSession;
use App\Models\Programme;
use App\Support\PublicTenantResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Public, unauthenticated "Apply Now" entry point. Deliberately separate
 * from AdmissionsController (which is the authenticated staff-side
 * processing area) — this controller only ever creates a submitted
 * Admission record; it has no access to review/status/export actions.
 *
 * The institution/school context is resolved automatically via
 * PublicTenantResolver — there is no school selector on this form and the
 * applicant is never asked to choose one.
 */
class PublicApplicationController extends Controller
{
    public function showForm()
    {
        $schoolId = PublicTenantResolver::resolveSchoolId();

        if (! $schoolId) {
            abort(503, 'Online applications are not currently configured.');
        }

        $programmes = Programme::where('school_id', $schoolId)->where('is_active', 1)->orderBy('name')->get();
        $intakeSessions = IntakeSession::where('school_id', $schoolId)->where('is_open', 1)->orderBy('name')->get();

        return view('frontend.apply', compact('programmes', 'intakeSessions'));
    }

    public function submit(Request $request)
    {
        $schoolId = PublicTenantResolver::resolveSchoolId();

        if (! $schoolId) {
            abort(503, 'Online applications are not currently configured.');
        }

        // Honeypot: a hidden field real applicants never fill in. Bots that
        // blindly fill every field trip this silently — no error shown, so
        // the bot gets no signal that it was rejected.
        if ($request->filled('website')) {
            return redirect()->route('apply.form')->with('success', get_phrase('Application submitted successfully.'));
        }

        $validated = $request->validate([
            'first_name'        => 'required|string|max:100',
            'last_name'         => 'required|string|max:100',
            'email'             => 'required|email|max:150',
            'phone'             => 'required|string|max:20',
            'dob'               => 'nullable|date|before:today',
            'gender'            => 'nullable|in:Male,Female,Others',
            'nationality'       => 'nullable|string|max:80',
            'programme_id'      => ['required', Rule::exists('programmes', 'id')->where('school_id', $schoolId)],
            'intake_session_id' => ['nullable', Rule::exists('intake_sessions', 'id')->where('school_id', $schoolId)],
            'qualifications'    => 'nullable|string|max:2000',
            'documents'         => 'nullable|array|max:5',
            'documents.*'       => 'file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $documentPaths = [];
        if ($request->hasFile('documents')) {
            $destination = public_path('assets/uploads/admissions');
            if (! is_dir($destination)) {
                mkdir($destination, 0755, true);
            }

            foreach ($request->file('documents') as $file) {
                // uniqid() without more_entropy is microtime-based and can
                // collide when several files land in the same request loop
                // (notably on Windows, where its clock resolution is
                // coarser) — a collision here silently overwrites an
                // already-moved document. More entropy is enough to make
                // that practically impossible.
                $filename = uniqid('doc_', true) . '.' . $file->getClientOriginalExtension();
                $file->move($destination, $filename);
                $documentPaths[] = $filename;
            }
        }

        $admission = Admission::create([
            'school_id'         => $schoolId,
            'app_number'        => 'APP-' . strtoupper(uniqid()),
            'intake_session_id' => $validated['intake_session_id'] ?? null,
            'programme_id'      => $validated['programme_id'],
            'first_name'        => $validated['first_name'],
            'last_name'         => $validated['last_name'],
            'email'             => $validated['email'],
            'phone'             => $validated['phone'],
            'dob'               => $validated['dob'] ?? null,
            'gender'            => $validated['gender'] ?? null,
            'nationality'       => $validated['nationality'] ?? null,
            'qualifications'    => $validated['qualifications'] ?? null,
            'documents'         => $documentPaths,
            'status'            => 'submitted',
            'source'            => 'public',
        ]);

        AuditLog::record('create', 'Admissions', "Public application submitted: {$admission->app_number} — {$admission->first_name} {$admission->last_name}", [
            'event_type'  => 'DATA',
            'record_type' => Admission::class,
            'record_id'   => $admission->id,
            'school_id'   => $schoolId,
        ]);

        return redirect()->route('apply.form')->with('success', get_phrase('Application submitted successfully. Our admissions team will review it and contact you.'));
    }
}
