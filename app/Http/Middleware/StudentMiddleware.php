<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StudentMiddleware
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

        if ($user->role_id == '7' && $user->account_status != 'disable') {
            if ($user->force_password_change && !$request->routeIs('student.password') && !$request->routeIs('logout')) {
                return redirect()->route('student.password', 'edit')->with('warning', get_phrase('You must change your temporary password before continuing.'));
            }

            return $next($request);
        }else{
            return redirect()->route('student.account_disable')->with('error', 'Access denied or your account is disabled.');
        }
    }
}
