<?php

namespace App\Support;

use App\Mail\StudentPortalActivationEmail;
use App\Models\IntakeSession;
use App\Models\Programme;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Shared "send the student their portal login email" helper, used by both
 * the Admission → Student conversion flow and the admin-triggered resend
 * action, so the mail-building logic (and the SMTP-configured guard) only
 * lives in one place. Never persists or logs the plaintext password passed
 * in — callers are responsible for having already hashed/stored it.
 */
class StudentPortalActivation
{
    public static function sendActivationEmail(User $student, string $plainPassword, ?int $programmeId = null, ?int $intakeSessionId = null): bool
    {
        if (empty(get_settings('smtp_user')) || !get_settings('smtp_pass') || !get_settings('smtp_host') || !get_settings('smtp_port')) {
            return false;
        }

        $programme = $programmeId ? Programme::find($programmeId) : null;
        $intake    = $intakeSessionId ? IntakeSession::find($intakeSessionId) : null;

        Mail::to($student->email)->send(new StudentPortalActivationEmail([
            'name'      => $student->name,
            'email'     => $student->email,
            'password'  => $plainPassword,
            'code'      => $student->code,
            'programme' => $programme->name ?? null,
            'intake'    => $intake->name ?? null,
            'school_id' => $student->school_id,
        ]));

        return true;
    }
}
