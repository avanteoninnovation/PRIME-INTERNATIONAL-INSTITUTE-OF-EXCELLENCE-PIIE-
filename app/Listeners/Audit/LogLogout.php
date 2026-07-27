<?php

namespace App\Listeners\Audit;

use App\Models\AuditLog;
use App\Support\Audit\ClientInfo;
use Illuminate\Auth\Events\Logout;

class LogLogout
{
    /**
     * Built directly from the event's user rather than AuditLog::record(),
     * because by the time this listener runs the guard may already have
     * cleared auth()->user() — the event payload is the only reliable source.
     */
    public function handle(Logout $event): void
    {
        $user = $event->user;

        if (!$user) {
            return;
        }

        $request = request();
        $client  = ClientInfo::fromRequest($request);

        AuditLog::$loggedThisRequest = true;

        try {
            AuditLog::create([
                'school_id'   => $user->school_id ?? null,
                'user_id'     => $user->id,
                'user_name'   => $user->name,
                'role_id'     => $user->role_id ?? null,
                'role_name'   => AuditLog::roleName($user->role_id ?? null),
                'action'      => 'LOGOUT',
                'event_type'  => 'AUTH',
                'module'      => 'Authentication',
                'route_name'  => \Illuminate\Support\Facades\Route::currentRouteName(),
                'url'         => $request->fullUrl(),
                'method'      => $request->method(),
                'description' => "{$user->name} logged out.",
                'record_type' => get_class($user),
                'record_id'   => $user->id,
                'ip_address'  => $request->ip(),
                'user_agent'  => $request->userAgent(),
                'device_type' => $client['device_type'],
                'browser'     => $client['browser'],
                'platform'    => $client['platform'],
                'status'      => 'success',
                'created_at'  => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
