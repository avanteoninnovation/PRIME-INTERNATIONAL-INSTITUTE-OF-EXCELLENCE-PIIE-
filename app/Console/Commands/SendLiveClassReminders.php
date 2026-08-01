<?php

namespace App\Console\Commands;

use App\Models\LiveClass;
use App\Models\LiveClassNotification;
use App\Support\LiveClasses\LiveClassNotifier;
use Illuminate\Console\Command;

/**
 * Sends the 24-hour and 1-hour reminders for published, upcoming live
 * classes. Registered in App\Console\Kernel::schedule() to run every five
 * minutes — but the schedule only fires anything if something on the server
 * actually invokes `php artisan schedule:run` once a minute (a cron entry on
 * Linux, a Task Scheduler entry on Windows). This command can also be run
 * directly at any time for testing, independent of whether that's set up:
 *
 *   php artisan live-classes:send-reminders
 */
class SendLiveClassReminders extends Command
{
    protected $signature = 'live-classes:send-reminders';

    protected $description = 'Send 24-hour and 1-hour reminders for upcoming published live classes';

    /**
     * How wide a net each run casts around the exact 24h/1h mark. Must be at
     * least as large as the gap between scheduler runs, or a class whose
     * exact reminder moment falls between two runs would never be caught by
     * either. At the scheduled 5-minute cadence, 10 minutes gives margin
     * without meaningfully widening when a reminder actually goes out.
     */
    private const WINDOW_MINUTES = 10;

    public function handle(): int
    {
        $sentFor24h = $this->sendWindow(LiveClassNotification::TYPE_REMINDER_24H, 24 * 60);
        $sentFor1h = $this->sendWindow(LiveClassNotification::TYPE_REMINDER_1H, 60);

        $this->info("24h reminders sent for {$sentFor24h} class(es); 1h reminders sent for {$sentFor1h} class(es).");

        return self::SUCCESS;
    }

    private function sendWindow(string $type, int $minutesBefore): int
    {
        $target = now()->addMinutes($minutesBefore);
        $windowStart = $target->copy()->subMinutes(self::WINDOW_MINUTES);
        $windowEnd = $target->copy()->addMinutes(self::WINDOW_MINUTES);

        $classes = LiveClass::query()
            ->where('is_published', 1)
            ->where('status', '!=', LiveClass::STATUS_CANCELLED)
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [$windowStart, $windowEnd])
            ->whereDoesntHave('notifications', fn ($q) => $q->where('type', $type))
            ->get();

        $classesNotified = 0;

        foreach ($classes as $liveClass) {
            try {
                $recipientCount = LiveClassNotifier::sendReminder($liveClass, $type);

                LiveClassNotification::create([
                    'school_id' => $liveClass->school_id,
                    'live_class_id' => $liveClass->id,
                    'type' => $type,
                    'recipient_count' => $recipientCount,
                    'sent_at' => now(),
                ]);

                $classesNotified++;
                $this->line("  #{$liveClass->id} \"{$liveClass->title}\" — {$type}: {$recipientCount} recipient(s).");
            } catch (\Throwable $e) {
                // One broken class (bad data, mail failure, whatever) must
                // never stop the rest of the run from going out.
                report($e);
            }
        }

        return $classesNotified;
    }
}
