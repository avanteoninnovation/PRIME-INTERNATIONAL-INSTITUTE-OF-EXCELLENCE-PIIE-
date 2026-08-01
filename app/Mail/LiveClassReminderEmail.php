<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LiveClassReminderEmail extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;

    /**
     * @param array $data student_name, class_title, subject, teacher_name,
     *                    date, time, join_url, window_label, school_id
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
        return $this->subject(get_phrase('Reminder') . ': ' . $this->data['class_title'] . ' ' . $this->data['window_label'])
            ->view('email.liveClassReminder')
            ->from(get_settings('smtp_user'), get_settings('system_title'));
    }
}
