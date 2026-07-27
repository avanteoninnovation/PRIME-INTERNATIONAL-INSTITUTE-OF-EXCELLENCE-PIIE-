<?php

namespace App\Listeners\Audit;

use App\Models\AuditLog;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        AuditLog::record('LOGIN', 'Authentication', "{$user->name} logged in.", [
            'event_type' => 'AUTH',
            'status'     => 'success',
            'school_id'  => $user->school_id ?? null,
            'record_type' => get_class($user),
            'record_id'   => $user->id,
        ]);
    }
}
