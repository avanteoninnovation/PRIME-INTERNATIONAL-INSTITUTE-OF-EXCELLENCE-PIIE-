<?php

namespace App\Listeners\Audit;

use App\Models\AuditLog;
use App\Support\Audit\ClientInfo;
use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        $request = request();
        $client  = ClientInfo::fromRequest($request);

        // Only the attempted identifier is ever recorded — never the password.
        $identifier = $event->credentials['email'] ?? $event->credentials['username'] ?? null;

        AuditLog::$loggedThisRequest = true;

        try {
            AuditLog::create([
                'school_id'   => $event->user->school_id ?? null,
                'user_id'     => $event->user->id ?? 0,
                'user_name'   => $event->user->name ?? $identifier,
                'role_id'     => $event->user->role_id ?? null,
                'role_name'   => AuditLog::roleName($event->user->role_id ?? null),
                'action'      => 'LOGIN_FAILED',
                'event_type'  => 'AUTH',
                'module'      => 'Authentication',
                'route_name'  => \Illuminate\Support\Facades\Route::currentRouteName(),
                'url'         => $request->fullUrl(),
                'method'      => $request->method(),
                'description' => 'Failed login attempt for ' . ($identifier ?? 'unknown user'),
                'record_type' => $event->user ? get_class($event->user) : null,
                'record_id'   => $event->user->id ?? null,
                'ip_address'  => $request->ip(),
                'user_agent'  => $request->userAgent(),
                'device_type' => $client['device_type'],
                'browser'     => $client['browser'],
                'platform'    => $client['platform'],
                'status'      => 'failed',
                'created_at'  => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
