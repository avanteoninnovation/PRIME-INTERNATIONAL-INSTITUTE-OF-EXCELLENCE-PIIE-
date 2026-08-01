<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The applicant-guard counterpart to `guest`: keeps a signed-in applicant off
 * the login/register pages and on their dashboard.
 */
class RedirectIfApplicant
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('applicant')->check()) {
            return redirect()->route('applicant.dashboard');
        }

        return $next($request);
    }
}
