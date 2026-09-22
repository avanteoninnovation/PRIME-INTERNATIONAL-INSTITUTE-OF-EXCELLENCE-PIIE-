<?php

namespace App\Http\Middleware;

use App\Support\Permissions\PortalAccessDenial;
use Closure;
use Illuminate\Http\Request;

/**
 * "Bursar" is this school's own name for the Accountant role (role_id 4) —
 * not a separate position. Previously this checked role_id 10, which
 * contradicted every other role_id => name mapping in the app (10 is Warden
 * per RoleHomeRoute) and had no controller, routes, or views wired to it at
 * all, so it never actually gated anything. Kept as its own middleware
 * (rather than just reusing 'accountant') so `bursar/*` routes read clearly
 * for what they are, even though the check is identical to AccountantMiddleware.
 */
class BursarMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        if ($user && $user->role_id == '4' && $user->account_status != 'disable' && !$user->isStaffPortalBlocked()) {
            return $next($request);
        }
        return PortalAccessDenial::redirect($user, 'accountant.account_disable');
    }
}
