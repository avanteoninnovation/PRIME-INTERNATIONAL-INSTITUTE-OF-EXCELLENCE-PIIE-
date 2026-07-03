<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BursarMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        if ($user && $user->role_id == '10' && $user->account_status != 'disable') {
            return $next($request);
        }
        return redirect()->route('login')->with('error', 'Access denied or your account is disabled.');
    }
}
