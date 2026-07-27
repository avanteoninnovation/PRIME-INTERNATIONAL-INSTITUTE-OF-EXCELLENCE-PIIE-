<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StudentPortalActivationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    /**
     * @param array $data name, email, password, code, programme, intake, school_id
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    public function build()
    {
        return $this->subject('Welcome to Prime International Institute of Excellence – Student Portal Account')
            ->view('email.studentPortalActivationEmail')
            ->from(get_settings('smtp_user'), get_settings('system_title'));
    }
}
