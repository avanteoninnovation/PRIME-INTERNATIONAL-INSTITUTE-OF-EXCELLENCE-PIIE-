<?php

namespace App\Http\Middleware;

use App\Support\PublicTenantResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Guards the applicant portal. Three checks, in order:
 *  1. authenticated on the "applicant" guard (never the web guard — a logged
 *     in member of staff is not an applicant and must not silently pass);
 *  2. the account has not been deactivated;
 *  3. the applicant belongs to the school this deployment serves publicly.
 *
 * (3) matters because applicants are unique per school, not globally: a
 * session created against one school must not survive a change of
 * primary_school_id and end up reading another institution's applications.
 */
class ApplicantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $applicant = Auth::guard('applicant')->user();

        if (! $applicant) {
            return redirect()->route('applicant.login')
                ->with('error', get_phrase('Please sign in to continue.'));
        }

        if (! $applicant->is_active) {
            Auth::guard('applicant')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('applicant.login')
                ->with('error', get_phrase('This account has been deactivated. Please contact the admissions office.'));
        }

        if ((int) $applicant->school_id !== (int) PublicTenantResolver::resolveSchoolId()) {
            Auth::guard('applicant')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('applicant.login')
                ->with('error', get_phrase('Please sign in again.'));
        }

        return $next($request);
    }
}
