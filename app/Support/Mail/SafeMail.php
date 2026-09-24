<?php

namespace App\Support\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Sends a notification e-mail that follows an already-completed business action
 * (account created, invoice issued, school approved…).
 *
 * If the mail provider fails — wrong SMTP credentials, server down, network — the
 * completed action must not turn into an HTTP 500 (the user would believe it failed
 * and retry, creating duplicates). Only mail-TRANSPORT failures are caught:
 * programming errors (e.g. a broken Mailable) still surface normally. The failure
 * is logged server-side without the recipient address, the message contents or
 * any credential; existing "resend" actions remain the retry path.
 */
final class SafeMail
{
    /** @return bool true when handed to the mailer, false when delivery failed (and was logged). */
    public static function send($to, Mailable $mailable, string $purpose = 'notification'): bool
    {
        try {
            Mail::to($to)->send($mailable);

            return true;
        } catch (TransportExceptionInterface $e) {
            Log::warning('Mail delivery failed; the completed action was kept', [
                'purpose' => $purpose,
                'mailable' => get_class($mailable),
                'exception' => get_class($e),
                'user_id' => auth()->id(),
                'school_id' => auth()->user()->school_id ?? null,
            ]);

            return false;
        }
    }
}
