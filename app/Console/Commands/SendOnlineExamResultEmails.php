<?php

namespace App\Console\Commands;

use App\Models\OnlineExam;
use App\Models\OnlineExamSubmission;
use App\Support\OnlineExams\OnlineExamResultNotifier;
use Illuminate\Console\Command;

/**
 * Catches the one case OnlineExamResultNotifier's two direct call sites
 * (submitBySubmission(), finalizeResult()) can't: a "release after exam
 * end" exam whose submissions were already finalized before end_datetime
 * passed. Nothing else re-touches that submission once graded, so nothing
 * else would ever notice the moment it becomes visible — this sweep is that
 * moment. result_email_sent_at makes re-running this safe; only exams still
 * accepting the after_exam_end policy and already past their end are ever
 * even queried.
 *
 *   php artisan online-exams:send-result-emails
 */
class SendOnlineExamResultEmails extends Command
{
    protected $signature = 'online-exams:send-result-emails';

    protected $description = 'Email students whose after_exam_end results have just become visible';

    public function handle(): int
    {
        $examIds = OnlineExam::query()
            ->where('result_release_policy', 'after_exam_end')
            ->whereNotNull('end_datetime')
            ->where('end_datetime', '<=', now())
            ->pluck('id');

        if ($examIds->isEmpty()) {
            $this->info('No exams past their after_exam_end release point.');
            return self::SUCCESS;
        }

        $submissions = OnlineExamSubmission::query()
            ->whereIn('online_exam_id', $examIds)
            ->where('status', OnlineExamSubmission::STATUS_FINALIZED)
            ->whereNull('result_email_sent_at')
            ->get();

        $sent = 0;
        foreach ($submissions as $submission) {
            try {
                if (OnlineExamResultNotifier::resultAvailable($submission)) {
                    $sent++;
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->info("Result emails sent for {$sent} submission(s).");

        return self::SUCCESS;
    }
}
