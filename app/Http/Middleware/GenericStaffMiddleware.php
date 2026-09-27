<?php

namespace App\Http\Middleware;

use App\Support\Permissions\PortalAccessDenial;
use App\Support\Roles\SystemRole;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class GenericStaffMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && (int) $user->role_id === SystemRole::GENERIC_STAFF && $user->force_password_change) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Set up your password using the link sent to your email before logging in.');
        }

        if (!$user || (int) $user->role_id !== SystemRole::GENERIC_STAFF
            || $user->account_status === 'disable' || $user->isStaffPortalBlocked()
            || empty($user->school_id)) {
            return PortalAccessDenial::redirect($user);
        }

        return $next($request);
    }
}
