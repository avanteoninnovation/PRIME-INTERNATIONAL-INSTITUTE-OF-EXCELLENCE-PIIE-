<?php

namespace App\Http\Controllers;

use App\Models\IntakeSession;
use App\Models\Programme;
use App\Support\PublicTenantResolver;
use Illuminate\Support\Facades\Auth;

/**
 * The public "Apply Now" landing page.
 *
 * This used to be the application itself — a single anonymous form that
 * created an Admission and then left the applicant with no way to see, finish
 * or correct anything. It is now the front door to the applicant portal: it
 * shows what is on offer and hands over to registration or sign-in, and the
 * application proper lives behind the "applicant" guard in
 * App\Http\Controllers\Applicant\*.
 *
 * The institution context is still resolved automatically — there is no
 * school selector on any public admissions page.
 */
class PublicApplicationController extends Controller
{
    public function showForm()
    {
        $schoolId = PublicTenantResolver::resolveSchoolId();

        if (! $schoolId) {
            abort(503, get_phrase('Online applications are not currently configured.'));
        }

        // Already signed in as an applicant — no reason to show them the
        // marketing page again.
        if (Auth::guard('applicant')->check()) {
            return redirect()->route('applicant.dashboard');
        }

        $programmes = Programme::where('school_id', $schoolId)
            ->where('is_active', 1)
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        $intakeSessions = IntakeSession::where('school_id', $schoolId)
            ->where('is_open', 1)
            ->orderBy('close_date')
            ->get();

        return view('frontend.apply', compact('programmes', 'intakeSessions'));
    }
}
