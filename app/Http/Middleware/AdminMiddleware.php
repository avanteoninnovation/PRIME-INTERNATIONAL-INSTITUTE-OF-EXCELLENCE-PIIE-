<?php

namespace App\Http\Middleware;

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

        $staffRoles = [2, 3, 4, 5, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19];

        if ($user && in_array($user->role_id, $staffRoles) && $user->account_status != 'disable') {
            return $next($request);
        }

        return redirect()->route('admin.account_disableview')->with('error', 'Access denied or your account is disabled.');
    }
}
