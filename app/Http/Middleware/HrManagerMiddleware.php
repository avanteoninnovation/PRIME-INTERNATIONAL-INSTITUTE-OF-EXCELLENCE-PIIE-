<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HrManagerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        if ($user && in_array($user->role_id, [2, 15]) && $user->account_status != 'disable') {
            return $next($request);
        }
        return redirect()->route('login')->with('error', 'Access denied.');
    }
}
