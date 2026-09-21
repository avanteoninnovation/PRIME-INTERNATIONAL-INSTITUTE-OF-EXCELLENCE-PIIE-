<?php

namespace App\Http\Middleware;

use App\Support\Permissions\PortalAccessDenial;
use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        // 9 = Registrar (RegistrarMiddleware) — added so logging in doesn't
        // dead-end at admin.dashboard; no Registrar-specific view exists
        // yet, so this is shared Admin access, not a scoped Registrar one.
        $staffRoles = [2, 3, 4, 5, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19];

        if ($user && in_array($user->role_id, $staffRoles) && $user->account_status != 'disable' && !$user->isStaffPortalBlocked()) {
            return $next($request);
        }

        return PortalAccessDenial::redirect($user, 'admin.account_disableview');
    }
}
