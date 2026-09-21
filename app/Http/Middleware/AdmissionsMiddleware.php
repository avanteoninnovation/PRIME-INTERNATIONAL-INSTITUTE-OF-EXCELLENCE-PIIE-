<?php

namespace App\Http\Middleware;

use App\Support\Permissions\PortalAccessDenial;
use Closure;
use Illuminate\Http\Request;

class AdmissionsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        if ($user && $user->role_id == '12' && $user->account_status != 'disable') {
            return $next($request);
        }
        return PortalAccessDenial::redirect($user);
    }
}
