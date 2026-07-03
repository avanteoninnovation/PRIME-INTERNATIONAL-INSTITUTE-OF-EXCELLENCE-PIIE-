<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Allows any institutional staff role to access admin-side routes.
 * Role IDs: 2=admin, 3=teacher, 4=accountant, 5=librarian,
 *           10=Registrar, 11=Bursar, 12=HOD, 13=Admissions Officer,
 *           14=Director, 15=HR Manager, 16=Procurement Officer,
 *           17=Store Keeper, 18=Receptionist, 19=Examinations Officer
 */
class MultiStaffMiddleware
{
    const STAFF_ROLES = [2, 3, 4, 5, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19];

    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        if ($user && in_array($user->role_id, self::STAFF_ROLES) && $user->account_status != 'disable') {
            return $next($request);
        }
        return redirect()->route('login')->with('error', 'Access denied or your account is disabled.');
    }
}
