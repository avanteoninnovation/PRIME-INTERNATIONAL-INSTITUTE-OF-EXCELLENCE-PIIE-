<?php

namespace App\Support\LiveClasses;

use App\Mail\LiveClassReminderEmail;
use App\Models\LiveClass;
use App\Models\Noticeboard;
use App\Models\Session;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Sends a single reminder pass (24h-before or 1h-before) for one class: one
 * in-app Noticeboard entry, plus one email per eligible student.
 *
 * Never called twice for the same (class, reminder type) — the caller
 * (App\Console\Commands\SendLiveClassReminders) checks LiveClassNotification
 * first and only calls this when nothing has been sent yet; recording that a
 * reminder went out is this class's last step, not a guard of its own, so
 * that a partial failure here is visible rather than silently marked "sent".
 */
class LiveClassNotifier
{
    public const WINDOW_LABELS = [
        'reminder_24h' => 'starts in 24 hours',
        'reminder_1h' => 'starts in 1 hour',
    ];

    public static function isMailConfigured(): bool
    {
        return !empty(get_settings('smtp_user'))
            && !empty(get_settings('smtp_pass'))
            && !empty(get_settings('smtp_host'))
            && !empty(get_settings('smtp_port'));
    }

    /** @return int number of students actually emailed */
    public static function sendReminder(LiveClass $liveClass, string $type): int
    {
        $liveClass->loadMissing(['subject', 'teacher', 'classRoom', 'academicSession']);

        $windowLabel = get_phrase(self::WINDOW_LABELS[$type] ?? 'is starting soon');
        $studentIds = LiveClassEligibility::eligibleStudentUserIds($liveClass);

        self::createNotice($liveClass, $windowLabel);

        if ($studentIds->isEmpty() || !self::isMailConfigured()) {
            return 0;
        }

        $joinUrl = route('student.live_classes.join', $liveClass->id);
        $sent = 0;

        User::whereIn('id', $studentIds)
            ->where('role_id', 7)
            ->whereNotNull('email')
            ->chunkById(100, function ($students) use ($liveClass, $windowLabel, $joinUrl, &$sent) {
                foreach ($students as $student) {
                    try {
                        Mail::to($student->email)->send(new LiveClassReminderEmail([
                            'student_name' => $student->name,
                            'class_title' => $liveClass->title,
                            'subject' => optional($liveClass->subject)->name,
                            'teacher_name' => optional($liveClass->teacher)->name,
                            'date' => optional($liveClass->start_date)->format('d M Y'),
                            'time' => $liveClass->start_time
                                ? \Illuminate\Support\Carbon::parse($liveClass->start_time)->format('H:i')
                                : '',
                            'join_url' => $joinUrl,
                            'window_label' => $windowLabel,
                            'school_id' => $liveClass->school_id,
                        ]));

                        $sent++;
                    } catch (\Throwable $e) {
                        // One bad address must not stop the rest of the batch.
                        report($e);
                    }
                }
            });

        return $sent;
    }

    /**
     * Mirrors LiveClassController::createStudentLiveClassNotice() — same
     * school-wide Noticeboard entry style already used for "scheduled" and
     * "published" events, so a reminder reads as one more entry in the same
     * feed rather than a different kind of notification.
     */
    private static function createNotice(LiveClass $liveClass, string $windowLabel): void
    {
        $subjectName = optional($liveClass->subject)->name ?: get_phrase('All courses');
        $classInfo = $liveClass->class_id
            ? (get_phrase('Class') . ': ' . (optional($liveClass->classRoom)->name ?: ('ID ' . $liveClass->class_id)))
            : get_phrase('Class') . ': ' . get_phrase('All classes');

        $noticeTitle = get_phrase('Live Class Reminder') . ': ' . $liveClass->title . ' ' . $windowLabel;
        $noticeBody = get_phrase('This class') . " {$windowLabel}.\n"
            . get_phrase('Course') . ": {$subjectName}\n"
            . "{$classInfo}\n"
            . get_phrase('Date') . ': ' . optional($liveClass->start_date)->format('Y-m-d') . "\n"
            . get_phrase('Time') . ": {$liveClass->start_time} - {$liveClass->end_time}\n"
            . get_phrase('Join Link') . ': ' . ($liveClass->meeting_url ?: 'TBD');

        $sessionId = (int) get_school_settings($liveClass->school_id)->value('running_session');
        if ($sessionId === 0) {
            $sessionId = (int) Session::where('school_id', $liveClass->school_id)->max('id');
        }

        Noticeboard::create([
            'notice_title' => $noticeTitle,
            'notice' => $noticeBody,
            'start_date' => optional($liveClass->start_date)->format('Y-m-d') ?: now()->format('Y-m-d'),
            'start_time' => (string) ($liveClass->start_time ?: ''),
            'end_date' => optional($liveClass->start_date)->format('Y-m-d') ?: now()->format('Y-m-d'),
            'end_time' => (string) ($liveClass->end_time ?: ''),
            'status' => 1,
            'show_on_website' => 0,
            'image' => '',
            'school_id' => $liveClass->school_id,
            'session_id' => $sessionId > 0 ? $sessionId : 0,
        ]);
    }
}
