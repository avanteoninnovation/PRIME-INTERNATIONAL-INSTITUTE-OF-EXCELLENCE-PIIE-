<?php

namespace App\Http\Middleware;

use App\Support\TenantConfiguration;
use Closure;
use Illuminate\Support\Facades\App;

class ResolveTenantLocale
{
    public function handle($request, Closure $next)
    {
        App::setLocale(app(TenantConfiguration::class)->resolveLocale());

        return $next($request);
    }
}
