<?php

namespace App\Http\Middleware;

use App\Support\Permissions\PortalAccessDenial;
use Closure;
use Illuminate\Http\Request;

class DirectorMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        if ($user && in_array($user->role_id, [2, 14]) && $user->account_status != 'disable') {
            return $next($request);
        }
        return PortalAccessDenial::redirect($user);
    }
}
