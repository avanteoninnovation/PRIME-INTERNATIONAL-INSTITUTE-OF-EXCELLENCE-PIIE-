<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class AdminPermission
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
        if(empty(Auth()->user()->menu_permission) && Auth()->user() ){
            return $next($request);
        }else{
            $user_permitted_routes = json_decode(Auth()->user()->menu_permission, true);

            $current_route = Route::currentRouteName();

            if ($this->isPermitted($current_route, $user_permitted_routes) && Auth()->user()->menu_permission != 'null') {
                return $next($request);
            }else{
                return redirect()->back();
            }
        }
    }

    private function isPermitted(?string $currentRoute, $permittedRoutes): bool
    {
        if (!is_array($permittedRoutes) || empty($currentRoute)) {
            return false;
        }

        if (in_array($currentRoute, $permittedRoutes, true)) {
            return true;
        }

        if (str_starts_with($currentRoute, 'admin.online_exams.')) {
            return in_array('admin.online_exams', $permittedRoutes, true)
                || in_array('admin.online_exams.index', $permittedRoutes, true);
        }

        if ($currentRoute === 'admin.online_exams') {
            return in_array('admin.online_exams.index', $permittedRoutes, true);
        }

        if ($currentRoute === 'admin.online_exams.index') {
            return in_array('admin.online_exams', $permittedRoutes, true);
        }

        return false;
    }
}
