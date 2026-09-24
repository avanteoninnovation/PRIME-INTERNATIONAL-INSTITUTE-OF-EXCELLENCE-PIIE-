<?php

namespace App\Http\Middleware;

use App\Support\Permissions\PermissionService;
use Closure;
use Illuminate\Http\Request;

/**
 * RBAC Phase 3A — backend enforcement for the admin portal ('rbac' alias).
 *
 * Attached after 'admin'/'auth' on the admin route groups, so it only ever
 * sees staff that AdminMiddleware already admitted; everyone else is
 * redirected by AdminMiddleware exactly as before. It looks the current
 * route name up in the permission registry's route map: a mapped route requires
 * that permission (direct URL, AJAX, export and download alike — menus are
 * not the protection) and is refused with 403 otherwise. An unmapped route
 * is left to its existing checks.
 *
 * Routes that already carry the stricter school_admin guard (Phases 2A/2B)
 * keep that guard as their authority and its own denial behaviour.
 */
class EnforceRoutePermission
{
    public function __construct(private PermissionService $permissions)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $route = $request->route();

        if ($route && $this->hasSchoolAdminGuard($route->gatherMiddleware())) {
            return $next($request);
        }

        $required = $this->permissions->routePermission($route?->getName());

        if ($required !== null && !$this->permissions->allows($request->user(), $required)) {
            abort(403, 'You do not have permission to do that.');
        }

        return $next($request);
    }

    private function hasSchoolAdminGuard(array $middleware): bool
    {
        foreach ($middleware as $name) {
            if (is_string($name) && ($name === 'school_admin' || str_starts_with($name, 'school_admin:'))) {
                return true;
            }
        }

        return false;
    }
}
