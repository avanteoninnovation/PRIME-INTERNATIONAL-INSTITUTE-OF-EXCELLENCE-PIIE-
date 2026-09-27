<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GenericStaffPasswordSetupMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $staffName, public string $setupUrl)
    {
    }

    public function build(): self
    {
        $fromAddress = trim((string) (get_settings('smtp_user') ?: config('mail.from.address')));
        $fromName = trim((string) (get_settings('system_title') ?: config('mail.from.name') ?: 'PIIE'));

        $mail = $this->subject('Set up your PIIE staff account')
            ->view('email.genericStaffPasswordSetup');

        if ($fromAddress !== '') {
            $mail->from($fromAddress, $fromName);
        }

        return $mail;
    }
}
