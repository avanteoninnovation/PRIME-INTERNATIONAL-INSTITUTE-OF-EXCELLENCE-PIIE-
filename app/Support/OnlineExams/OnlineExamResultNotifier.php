<?php

namespace App\Support\OnlineExams;

use App\Mail\ApplicantNotificationEmail;
use App\Models\OnlineExamSubmission;
use Illuminate\Support\Facades\Mail;

/**
 * Emails a student once their online exam result is actually visible to
 * them (App\Models\OnlineExam::isResultVisibleFor() is the single source of
 * truth for "visible" — immediate, after_exam_end, or manual-then-finalized).
 * result_email_sent_at makes this idempotent: the same submission can pass
 * through here from submitBySubmission() (auto-graded, no manual marking
 * needed), finalizeResult() (admin finalizes after manual marking), and the
 * after_exam_end sweep command, but only ever sends once.
 *
 * Same rules as App\Support\Admissions\ApplicantNotifier: gated on SMTP
 * actually being configured, and a failed send never breaks the action that
 * triggered it — a student's exam is submitted/finalized whether or not the
 * result email got out.
 */
class OnlineExamResultNotifier
{
    public static function isConfigured(): bool
    {
        return !empty(get_settings('smtp_user'))
            && !empty(get_settings('smtp_pass'))
            && !empty(get_settings('smtp_host'))
            && !empty(get_settings('smtp_port'));
    }

    public static function resultAvailable(OnlineExamSubmission $submission): bool
    {
        if (!empty($submission->result_email_sent_at)) {
            return false;
        }

        if (!in_array($submission->status, [OnlineExamSubmission::STATUS_FINALIZED, OnlineExamSubmission::STATUS_RESULT_PUBLISHED], true)) {
            return false;
        }

        $submission->loadMissing(['exam', 'student']);
        $exam = $submission->exam;
        $student = $submission->student;

        if (!$exam || !$student || !$exam->isResultVisibleFor($submission)) {
            return false;
        }

        if (!self::isConfigured() || blank($student->email)) {
            return false;
        }

        $totalMarks = (float) ($submission->total_marks_snapshot ?: $exam->total_marks ?: 0);
        $score = (float) ($submission->effective_score ?? $submission->score ?? 0);
        $percentage = $totalMarks > 0 ? round(($score / $totalMarks) * 100, 1) : 0;

        $sent = self::send($student->email, [
            'subject'  => get_phrase('Your exam result is ready') . ': ' . $exam->title,
            'heading'  => get_phrase('Exam Result Available'),
            'greeting' => get_phrase('Dear') . ' ' . $student->name . ',',
            'paragraphs' => [
                get_phrase('Your result for the following exam is now available.'),
            ],
            'details' => array_filter([
                get_phrase('Exam')      => $exam->title,
                get_phrase('Score')     => number_format($score, 2) . ' / ' . $totalMarks,
                get_phrase('Percentage') => $percentage . '%',
                get_phrase('Outcome')   => is_null($submission->passed) ? null : ($submission->passed ? get_phrase('Pass') : get_phrase('Fail')),
            ]),
            'cta_label'   => get_phrase('View Full Result'),
            'cta_url'     => route('student.online_exam.result', $submission->id),
            'footer_note' => get_phrase('If you have questions about this result, please contact your school administration.'),
            'school_id'   => $exam->school_id,
        ]);

        if ($sent) {
            $submission->forceFill(['result_email_sent_at' => now()])->save();
        }

        return $sent;
    }

    private static function send(?string $to, array $data): bool
    {
        if (blank($to)) {
            return false;
        }

        try {
            Mail::to($to)->send(new ApplicantNotificationEmail($data));

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }
}
