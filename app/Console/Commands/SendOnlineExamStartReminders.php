<?php

namespace App\Console\Commands;

use App\Models\OnlineExam;
use App\Models\OnlineExamNotification;
use App\Support\OnlineExams\OnlineExamAnnouncementNotifier;
use Illuminate\Console\Command;

/**
 * Sends the 24-hour, 1-hour and 15-minute reminders for published, upcoming
 * online exams — same shape as App\Console\Commands\SendLiveClassReminders.
 * Registered in App\Console\Kernel::schedule() to run every five minutes,
 * but the schedule only fires anything if something on the server actually
 * invokes `php artisan schedule:run` once a minute (a cron entry). This
 * command can also be run directly at any time for testing:
 *
 *   php artisan online-exams:send-start-reminders
 */
class SendOnlineExamStartReminders extends Command
{
    protected $signature = 'online-exams:send-start-reminders';

    protected $description = 'Send 24-hour, 1-hour and 15-minute reminders for upcoming published online exams';

    /**
     * How wide a net each run casts around the exact 24h/1h/15m mark. Must
     * be at least as large as the gap between scheduler runs, or an exam
     * whose exact reminder moment falls between two runs would never be
     * caught by either. At the scheduled 5-minute cadence, a smaller window
     * is used for the 15-minute reminder so it can't fire so early it
     * overlaps the 1-hour reminder's own margin.
     */
    private const WINDOW_MINUTES = 10;
    private const WINDOW_MINUTES_SHORT = 7;

    public function handle(): int
    {
        $sentFor24h = $this->sendWindow(OnlineExamNotification::TYPE_REMINDER_24H, 24 * 60, self::WINDOW_MINUTES, get_phrase('starts in 24 hours'));
        $sentFor1h = $this->sendWindow(OnlineExamNotification::TYPE_REMINDER_1H, 60, self::WINDOW_MINUTES, get_phrase('starts in 1 hour'));
        $sentFor15m = $this->sendWindow(OnlineExamNotification::TYPE_REMINDER_15M, 15, self::WINDOW_MINUTES_SHORT, get_phrase('starts in 15 minutes'));

        $this->info("24h reminders sent for {$sentFor24h} exam(s); 1h reminders sent for {$sentFor1h} exam(s); 15m reminders sent for {$sentFor15m} exam(s).");

        return self::SUCCESS;
    }

    private function sendWindow(string $type, int $minutesBefore, int $windowMinutes, string $windowLabel): int
    {
        $target = now()->addMinutes($minutesBefore);
        $windowStart = $target->copy()->subMinutes($windowMinutes);
        $windowEnd = $target->copy()->addMinutes($windowMinutes);

        $exams = OnlineExam::query()
            ->where('workflow_state', 'published')
            ->whereNotNull('start_datetime')
            ->whereBetween('start_datetime', [$windowStart, $windowEnd])
            ->whereDoesntHave('notifications', fn ($q) => $q->where('type', $type))
            ->get();

        $examsNotified = 0;

        foreach ($exams as $exam) {
            try {
                $recipientCount = OnlineExamAnnouncementNotifier::startingSoon($exam, $windowLabel);

                OnlineExamNotification::create([
                    'school_id' => $exam->school_id,
                    'online_exam_id' => $exam->id,
                    'type' => $type,
                    'recipient_count' => $recipientCount,
                    'sent_at' => now(),
                ]);

                $examsNotified++;
                $this->line("  #{$exam->id} \"{$exam->title}\" — {$type}: {$recipientCount} recipient(s).");
            } catch (\Throwable $e) {
                // One broken exam (bad data, mail failure, whatever) must
                // never stop the rest of the run from going out.
                report($e);
            }
        }

        return $examsNotified;
    }
}
