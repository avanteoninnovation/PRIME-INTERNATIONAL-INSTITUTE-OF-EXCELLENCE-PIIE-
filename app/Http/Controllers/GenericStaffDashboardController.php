<?php

namespace App\Http\Controllers;

use App\Support\Permissions\PermissionRegistry;
use App\Support\Permissions\PermissionService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class GenericStaffDashboardController extends Controller
{
    public function __invoke(PermissionService $permissions)
    {
        $links = [];
        foreach (PermissionRegistry::routes() as $pattern => $key) {
            if ($key === null || !$permissions->allows(auth()->user(), $key)) continue;
            $route = collect(Route::getRoutes())->first(function ($candidate) use ($pattern) {
                return $candidate->getName() && Str::is($pattern, $candidate->getName())
                    && !str_contains($candidate->getName(), '{')
                    && in_array('GET', $candidate->methods(), true)
                    && !str_contains($candidate->uri(), '{');
            });
            if ($route) $links[$key] = ['label' => PermissionRegistry::permissions()[$key]['label'] ?? $key, 'url' => route($route->getName())];
        }

        return view('staff.dashboard', ['links' => array_values($links)]);
    }
}
